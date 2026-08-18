<?php

declare(strict_types=1);

namespace Moe\Marketing\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Moe\Core\Base\BaseService;
use Moe\Finance\Models\Wallet;
use Moe\Marketing\Models\CommissionLedger;

class CommissionService extends BaseService
{
    /**
     * Recognize komisi untuk satu order yang baru saja jadi paid/completed.
     *
     * Mengadopsi logika gross-profit dari app:
     *   grossProfit = (Σ retail_price·qty) − (Σ supplier_base_price·qty) − discount
     *   commission  = round(grossProfit · rate% , 2)
     *
     * Idempotent: kalau ledger sudah ada untuk order_id ini, skip.
     * Return null kalau tidak ada attribution atau marketing nonaktif.
     *
     * @param \Illuminate\Database\Eloquent\Model $order
     * @return \Moe\Marketing\Models\CommissionLedger|null
     */
    public function recognize(Model $order): ?CommissionLedger
    {
        $order->loadMissing(['items', 'user']);

        $customer = $order->user;
        if (! $customer || ! $customer->referred_by_user_id) {
            return null;
        }

        $userModel = config('marketing.models.user', 'App\\Models\\User');
        $marketing = $userModel::where('id', $customer->referred_by_user_id)
            ->where('role', 'marketing')
            ->where('is_active', true)
            ->first();

        if (! $marketing) {
            Log::info('[marketing] commission.recognize.skip_inactive_marketing', [
                'order_id' => $order->id,
                'marketing_id' => $customer->referred_by_user_id,
            ]);

            return null;
        }

        $gross = 0.0;
        $cost = 0.0;

        foreach ($order->items as $item) {
            $qty = (float) $item->quantity;
            $price = (float) ($item->retail_price ?? 0);
            $itemCost = (float) ($item->supplier_base_price ?? 0);

            $gross += $price * $qty;
            $cost += $itemCost * $qty;
        }

        $discount = (float) ($order->discount ?? 0);
        $grossProfit = max(0.0, $gross - $cost - $discount);

        $rate = (float) $marketing->effectiveCommissionRate();
        $commission = round($grossProfit * $rate / 100.0, 2);

        $holdDays = (int) (config('marketing.commission.hold_days', 7));

        $ledger = DB::transaction(function () use (
            $order, $marketing, $customer, $rate,
            $gross, $cost, $discount, $grossProfit, $commission, $holdDays
        ) {
            $existing = CommissionLedger::where('order_id', $order->id)->first();
            if ($existing) {
                return $existing;
            }

            return CommissionLedger::create([
                'marketing_user_id' => $marketing->id,
                'customer_user_id' => $customer->id,
                'order_id' => $order->id,
                'rate' => $rate,
                'gross_amount' => $gross,
                'cost_amount' => $cost,
                'discount_amount' => $discount,
                'gross_profit' => $grossProfit,
                'commission_amount' => $commission,
                'status' => CommissionLedger::STATUS_ON_HOLD,
                'recognized_at' => now(),
                'hold_release_at' => now()->addDays($holdDays),
                'meta' => [
                    'order_number' => $order->order_number ?? null,
                    'item_count' => $order->items->count(),
                ],
            ]);
        });

        Log::info('[marketing] commission.recognized', [
            'ledger_id' => $ledger->id,
            'order_id' => $order->id,
            'marketing_id' => $marketing->id,
            'amount' => $commission,
            'hold_release_at' => $ledger->hold_release_at->toDateTimeString(),
        ]);

        return $ledger;
    }

    /**
     * Reverse komisi untuk order yang di-refund/cancel.
     *
     *  - on_hold   → flip status ke reversed
     *  - released  → debit wallet marketing (allowNegative=true, piutang)
     *  - reversed/cancelled → no-op (idempoten)
     *
     * @param \Illuminate\Database\Eloquent\Model $order
     * @param string $reason
     * @return \Moe\Marketing\Models\CommissionLedger|null
     */
    public function reverse(Model $order, string $reason = 'order_refunded'): ?CommissionLedger
    {
        $ledger = CommissionLedger::where('order_id', $order->id)->first();
        if (! $ledger) {
            return null;
        }

        if (in_array($ledger->status, [
            CommissionLedger::STATUS_REVERSED,
            CommissionLedger::STATUS_CANCELLED,
        ], true)) {
            return $ledger;
        }

        $fromStatus = $ledger->status;
        $wasReleased = $fromStatus === CommissionLedger::STATUS_RELEASED;

        DB::transaction(function () use ($ledger, $reason, $wasReleased) {
            if ($wasReleased) {
                $this->debitMarketingWallet(
                    $ledger->marketing_user_id,
                    (float) $ledger->commission_amount,
                    "Pembalikan komisi order #{$ledger->order_id} ({$reason})"
                );
            }

            $ledger->reverse($reason);
        });

        Log::info('[marketing] commission.reversed', [
            'ledger_id' => $ledger->id,
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'wallet_debited' => $wasReleased,
            'reason' => $reason,
        ]);

        return $ledger->fresh();
    }

    /**
     * Release komisi yang sudah lewat masa hold ke wallet marketing.
     *
     * @return int jumlah ledger yang dilepas.
     */
    public function releaseDue(): int
    {
        $dueLedgers = CommissionLedger::where('status', CommissionLedger::STATUS_ON_HOLD)
            ->where('hold_release_at', '<=', now())
            ->get();

        $released = 0;

        foreach ($dueLedgers as $ledger) {
            try {
                DB::transaction(function () use ($ledger) {
                    $wallet = $this->marketingWallet($ledger->marketing_user_id);

                    $wallet->credit(
                        (float) $ledger->commission_amount,
                        'commission_credit',
                        "Commission release from Order #{$ledger->order_id}"
                    );

                    $ledger->release();
                });
                $released++;
            } catch (\Throwable $e) {
                Log::error("[marketing] Failed to release commission #{$ledger->id}: {$e->getMessage()}");
            }
        }

        return $released;
    }

    /**
     * Cek apakah ada ledger untuk order ini.
     */
    public function hasLedger(Model $order): bool
    {
        return CommissionLedger::where('order_id', $order->id)->exists();
    }

    protected function marketingWallet(int $marketingUserId): Wallet
    {
        $userModel = config('marketing.models.user', User::class);
        $walletableType = $userModel;

        return Wallet::query()
            ->where('walletable_type', $walletableType)
            ->where('walletable_id', $marketingUserId)
            ->firstOrCreate(
                ['walletable_type' => $walletableType, 'walletable_id' => $marketingUserId],
                ['balance' => 0, 'currency' => config('finance.currency', 'IDR')]
            );
    }

    protected function debitMarketingWallet(int $marketingUserId, float $amount, string $description): void
    {
        $wallet = $this->marketingWallet($marketingUserId);

        $wallet->debit($amount, 'commission_reversal', $description, allowNegative: true);
    }
}
