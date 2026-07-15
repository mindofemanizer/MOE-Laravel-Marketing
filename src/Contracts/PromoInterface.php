<?php

declare(strict_types=1);

namespace Moe\Marketing\Contracts;

interface PromoInterface
{
    /**
     * @return bool
     */
    public function isValid(): bool;

    /**
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * @param float $subtotal
     * @return float
     */
    public function getDiscount(float $subtotal): float;

    /**
     * @return int
     */
    public function getUsageLimit(): int;

    /**
     * @return int
     */
    public function getUsageCount(): int;
}
