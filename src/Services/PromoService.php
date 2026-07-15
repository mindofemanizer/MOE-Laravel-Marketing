<?php

declare(strict_types=1);

namespace Moe\Marketing\Services;

use Moe\Core\Base\BaseService;
use Moe\Marketing\Models\Promo;
use Moe\Marketing\Models\PromoUsage;

class PromoService extends BaseService
{
    /**
     * Validate promo code and get discount.
     */
    public function validateAndApply(string $code, float $subtotal, int $userId): array
    {
        $promo = Promo::where('code', $code)->first();

        if (! $promo || ! $promo->isValid()) {
            return [
                'valid' => false,
                'message' => 'Kode promo tidak valid atau sudah kadaluwarsa.',
                'discount' => 0,
            ];
        }

        if ($subtotal < $promo->minimum_order) {
            return [
                'valid' => false,
                'message' => 'Minimal belanja Rp ' . number_format($promo->minimum_order, 0, ',', '.') . ' untuk menggunakan kode ini.',
                'discount' => 0,
            ];
        }

        // Check usage limit per user
        if ($promo->usage_limit_per_user) {
            $userUsage = PromoUsage::where('promo_id', $promo->id)
                ->where('user_id', $userId)
                ->count();

            if ($userUsage >= $promo->usage_limit_per_user) {
                return [
                    'valid' => false,
                    'message' => 'Kode promo sudah mencapai batas penggunaan.',
                    'discount' => 0,
                ];
            }
        }

        $discount = $promo->getDiscount($subtotal);

        return [
            'valid' => true,
            'message' => 'Kode promo berhasil diterapkan!',
            'discount' => $discount,
            'promo' => $promo,
        ];
    }

    /**
     * Record promo usage.
     */
    public function recordUsage(Promo $promo, int $orderId, int $userId, float $discountAmount): PromoUsage
    {
        return PromoUsage::create([
            'promo_id' => $promo->id,
            'order_id' => $orderId,
            'user_id' => $userId,
            'discount_amount' => $discountAmount,
        ]);
    }

    /**
     * Get active promos.
     */
    public function getActivePromos(): \Illuminate\Database\Eloquent\Collection
    {
        return Promo::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get();
    }
}
