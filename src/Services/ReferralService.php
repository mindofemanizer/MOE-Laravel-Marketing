<?php

declare(strict_types=1);

namespace Moe\Marketing\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cookie;
use Moe\Core\Base\BaseService;
use Moe\Marketing\Models\MarketingAttributionLog;

class ReferralService extends BaseService
{
    public const COOKIE_NAME = 'referral_slug';
    public const COOKIE_DURATION = 60 * 24 * 30; // 30 days in minutes

    /**
     * Find marketing user by referral slug.
     *
     * @param string $slug
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function findBySlug(string $slug): ?Model
    {
        return config('marketing.models.user')::where('referral_slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Set referral cookie.
     *
     * @param string $slug
     * @return void
     */
    public function setReferralCookie(string $slug): void
    {
        Cookie::queue(self::COOKIE_NAME, $slug, self::COOKIE_DURATION);
    }

    /**
     * Get referral slug from cookie or input.
     *
     * @param string|null $inputCode
     * @return string|null
     */
    public function getReferralSlug(?string $inputCode = null): ?string
    {
        return $inputCode ?? Cookie::get(self::COOKIE_NAME);
    }

    /**
     * Attribute customer to a marketing user.
     *
     * @param int $customerUserId
     * @param int $marketingUserId
     * @param string $source
     * @return bool
     */
    public function attribute(int $customerUserId, int $marketingUserId, string $source): bool
    {
        $customerUser = config('marketing.models.user')::find($customerUserId);

        if (! $customerUser || $customerUser->referred_by_user_id) {
            return false;
        }

        $customerUser->update([
            'referred_by_user_id' => $marketingUserId,
            'attribution_source' => $source,
            'attributed_at' => now(),
        ]);

        MarketingAttributionLog::create([
            'customer_user_id' => $customerUserId,
            'to_marketing_user_id' => $marketingUserId,
            'action' => MarketingAttributionLog::ACTION_ATTRIBUTE,
            'source' => $source,
            'performed_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Transfer attribution from one marketing user to another.
     *
     * @param int $customerUserId
     * @param int $newMarketingUserId
     * @param string|null $notes
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function transferAttribution(int $customerUserId, int $newMarketingUserId, ?string $notes = null): void
    {
        $customerUser = config('marketing.models.user')::findOrFail($customerUserId);
        $oldMarketingId = $customerUser->referred_by_user_id;

        $customerUser->update([
            'referred_by_user_id' => $newMarketingUserId,
            'attributed_at' => now(),
        ]);

        MarketingAttributionLog::create([
            'customer_user_id' => $customerUserId,
            'from_marketing_user_id' => $oldMarketingId,
            'to_marketing_user_id' => $newMarketingUserId,
            'action' => MarketingAttributionLog::ACTION_TRANSFER,
            'source' => 'manual',
            'performed_by' => auth()->id(),
            'notes' => $notes,
        ]);
    }

    /**
     * Get referred customers for a marketing user.
     *
     * @param int $marketingUserId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReferredCustomers(int $marketingUserId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return config('marketing.models.user')::where('referred_by_user_id', $marketingUserId)
            ->latest('attributed_at')
            ->limit($limit)
            ->get();
    }
}
