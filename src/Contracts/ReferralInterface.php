<?php

declare(strict_types=1);

namespace Moe\Marketing\Contracts;

interface ReferralInterface
{
    public function getReferralSlug(): string;
    public function getReferredUsers(): \Illuminate\Database\Eloquent\Collection;
    public function getReferralEarnings(): float;
}
