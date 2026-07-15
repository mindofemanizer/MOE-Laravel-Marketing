<?php

namespace Moe\Marketing\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moe\Core\Base\BaseService;
use Moe\Finance\Models\WalletTransaction;
use Moe\Finance\Services\WalletService;
use Moe\Marketing\Models\CommissionLedger;
use Moe\Marketing\Models\MarketingAttributionLog;

class CommissionService extends BaseService
{
    /**
     * Recognize commission for an order.
     */
    public function recognize(Model $order): void
    {
        $orderId = $order->id;
        $customerUserId = $order->user_id;
        $marketingUser = $this->getAttributedMarketing($customerUserId);

        if (! $marketingUser) {
            return;
        }

        $commissionRate = (float) $marketingUser->effectiveCommissionRate();

        DB::transaction(function () use ($order, $orderId, $marketingUser, $commissionRate) {
            foreach ($order->items as $item) {
                $grossProfit = $item->subtotal - ($item->cost_snapshot * $item->quantity);
                $commissionAmount = $grossProfit * $commissionRate / 100;

                if ($commissionAmount <= 0) {
                    continue;
                }

                CommissionLedger::create([
                    'marketing_user_id' => $marketingUser->id,
                    'customer_user_id' => $order->user_id,
                    'order_id' => $orderId,
                    'order_item_id' => $item->id,
                    'amount' => $commissionAmount,
                    'rate' => $commissionRate,
                    'status' => CommissionLedger::STATUS_ON_HOLD,
                    'release_due_at' => now()->addDays(config('marketing.commission.hold_days', 7)),
                    'notes' => "Commission from Order #{$order->order_number}",
                ]);
            }
        });
    }

    /**
     * Reverse commission for a cancelled/refunded order.
     */
    public function reverse(Model $order, string $reason): void
    {
        $ledgers = CommissionLedger::where('order_id', $order->id)->get();

        foreach ($ledgers as $ledger) {
            DB::transaction(function () use ($ledger, $reason) {
                if ($ledger->status === CommissionLedger::STATUS_RELEASED) {
                    // Already released → debit back from wallet
                    $walletService = app(WalletService::class);
                    $marketingUser = $ledger->marketingUser;

                    $walletService->debit(
                        $ledger->amount,
                        'commission_reversal',
                        "Reversal: {$reason}"
                    );
                }

                $ledger->reverse($reason);
            });
        }
    }

    /**
     * Release commissions that are due.
     */
    public function releaseDue(): int
    {
        $dueLedgers = CommissionLedger::where('status', CommissionLedger::STATUS_ON_HOLD)
            ->where('release_due_at', '<=', now())
            ->get();

        $released = 0;

        foreach ($dueLedgers as $ledger) {
            try {
                DB::transaction(function () use ($ledger) {
                    $walletService = app(WalletService::class);
                    $marketingUser = $ledger->marketingUser;

                    $walletService->credit(
                        $ledger->amount,
                        'commission_credit',
                        "Commission release from Order #{$ledger->order->order_number}"
                    );

                    $ledger->release();
                });
                $released++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to release commission #{$ledger->id}: {$e->getMessage()}");
            }
        }

        return $released;
    }

    /**
     * Get attributed marketing user for a customer.
     */
    protected function getAttributedMarketing(int $customerUserId): ?Model
    {
        return config('marketing.models.user')::find($customerUserId)?->referredBy;
    }
}
