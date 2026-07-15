<?php

namespace Moe\Marketing\Listeners;

use Moe\Commerce\Events\OrderStatusChanged;
use Moe\Marketing\Models\CommissionLedger;
use Moe\Marketing\Models\MarketingAttributionLog;

class RecognizeCommissionOnOrderCompleted
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->newStatus !== 'completed') {
            return;
        }

        $attribution = MarketingAttributionLog::where('order_id', $event->order->id)
            ->where('type', 'referral')
            ->first();

        if (! $attribution) {
            return;
        }

        $commissionRate = (float) config('marketing.commission.default_rate', 10) / 100;
        $commissionAmount = (float) $event->order->total * $commissionRate;

        CommissionLedger::create([
            'user_id' => $attribution->referred_user_id,
            'type' => 'referral',
            'amount' => $commissionAmount,
            'description' => "Komisi referral order {$event->order->order_number}",
            'reference_type' => 'order',
            'reference_id' => $event->order->id,
            'status' => 'pending',
        ]);
    }
}
