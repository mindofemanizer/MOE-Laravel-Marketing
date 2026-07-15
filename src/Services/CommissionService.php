<?php

declare(strict_types=1);

namespace Moe\Marketing\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Moe\Core\Base\BaseService;
use Moe\Marketing\Models\CommissionLedger;

class CommissionService extends BaseService
{
    /**
     * Recognize commission for an order.
     *
     * @param \Illuminate\Database\Eloquent\Model $order
     * @return void
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
     *
     * @param \Illuminate\Database\Eloquent\Model $order
     * @param string $reason
     * @return void
     */
    public function reverse(Model $order, string $reason): void
    {
        $ledgers = CommissionLedger::where('order_id', $order->id)->get();

        foreach ($ledgers as $ledger) {
            DB::transaction(function () use ($ledger, $reason) {
                if ($ledger->status === CommissionLedger::STATUS_RELEASED) {
                    $marketingUser = $ledger->marketingUser;

                    if ($marketingUser) {
                        $wallet = \Moe\Finance\Models\Wallet::where('walletable_type', get_class($marketingUser))
                            ->where('walletable_id', $marketingUser->id)
                            ->first();

                        if ($wallet) {
                            $wallet->debit(
                                $ledger->amount,
                                'commission_reversal',
                                "Reversal: {$reason}"
                            );
                        }
                    }
                }

                $ledger->reverse($reason);
            });
        }
    }

    /**
     * Release commissions that are due.
     *
     * @return int
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
                    $marketingUser = $ledger->marketingUser;

                    if ($marketingUser) {
                        $wallet = \Moe\Finance\Models\Wallet::where('walletable_type', get_class($marketingUser))
                            ->where('walletable_id', $marketingUser->id)
                            ->firstOrCreate(
                                ['walletable_type' => get_class($marketingUser), 'walletable_id' => $marketingUser->id],
                                ['balance' => 0, 'currency' => config('finance.currency', 'IDR')]
                            );

                        $wallet->credit(
                            $ledger->amount,
                            'commission_credit',
                            "Commission release from Order #{$ledger->order->order_number}"
                        );
                    }

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
     *
     * @param int $customerUserId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getAttributedMarketing(int $customerUserId): ?Model
    {
        return config('marketing.models.user')::find($customerUserId)?->referredBy;
    }
}
