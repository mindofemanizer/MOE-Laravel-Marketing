<?php

declare(strict_types=1);

namespace Moe\Marketing\Contracts;

interface ReferralInterface
{
    /**
     * @return string
     */
    public function getReferralSlug(): string;

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReferredUsers(): \Illuminate\Database\Eloquent\Collection;

    /**
     * @return float
     */
    public function getReferralEarnings(): float;
}
