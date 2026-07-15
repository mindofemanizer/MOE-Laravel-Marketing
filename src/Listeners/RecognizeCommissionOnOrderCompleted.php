<?php

declare(strict_types=1);

namespace Moe\Marketing\Listeners;

use Illuminate\Support\Facades\Log;
use Moe\Commerce\Events\OrderStatusChanged;
use Moe\Marketing\Models\CommissionLedger;
use Moe\Marketing\Models\MarketingAttributionLog;

class RecognizeCommissionOnOrderCompleted
{
    /**
     * @param \Moe\Commerce\Events\OrderStatusChanged $event
     * @return void
     */
    public function handle(OrderStatusChanged $event): void
    {
        try {
            if ($event->newStatus !== 'completed') {
                return;
            }

            $customerUserId = $event->order->user_id;

            $attribution = MarketingAttributionLog::where('customer_user_id', $customerUserId)
                ->where('action', MarketingAttributionLog::ACTION_ATTRIBUTE)
                ->latest()
                ->first();

            if (! $attribution || ! $attribution->to_marketing_user_id) {
                return;
            }

            $commissionRate = (float) config('marketing.commission.default_rate', 10);
            $commissionAmount = (float) $event->order->total * ($commissionRate / 100);

            CommissionLedger::create([
                'marketing_user_id' => $attribution->to_marketing_user_id,
                'customer_user_id' => $customerUserId,
                'order_id' => $event->order->id,
                'amount' => $commissionAmount,
                'rate' => $commissionRate,
                'status' => CommissionLedger::STATUS_ON_HOLD,
                'release_due_at' => now()->addDays(config('marketing.commission.hold_days', 7)),
                'notes' => "Komisi referral order {$event->order->order_number}",
            ]);
        } catch (\Throwable $e) {
            Log::error('[marketing] RecognizeCommissionOnOrderCompleted failed: '.$e->getMessage(), [
                'order_id' => $event->order?->id,
            ]);
        }
    }
}
