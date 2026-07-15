<?php

declare(strict_types=1);

namespace Moe\Marketing\Listeners;

use Illuminate\Support\Facades\Log;
use Moe\Commerce\Events\OrderStatusChanged;
use Moe\Marketing\Services\CommissionService;

class RecognizeCommissionOnOrderCompleted
{
    /**
     * @param \Moe\Commerce\Events\OrderStatusChanged $event
     * @return void
     */
    public function handle(OrderStatusChanged $event): void
    {
        try {
            // Handler package aktif kalau app memilih menggunakan CommissionService
            // package (bukan CommissionService lokal). Default: aktif (standalone).
            // App KiosKit men-set 'marketing.commission.use_package_handler' = true
            // supaya domain komisi di-handle package (gross-profit, hold/release/reverse).
            if (! config('marketing.commission.use_package_handler', true)) {
                return;
            }

            if ($event->newStatus !== 'completed') {
                return;
            }

            app(CommissionService::class)->recognize($event->order);
        } catch (\Throwable $e) {
            Log::error('[marketing] RecognizeCommissionOnOrderCompleted failed: '.$e->getMessage(), [
                'order_id' => $event->order?->id,
            ]);
        }
    }
}
