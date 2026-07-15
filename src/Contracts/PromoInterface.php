<?php

namespace Moe\Marketing\Contracts;

interface PromoInterface
{
    public function isValid(): bool;
    public function isExpired(): bool;
    public function getDiscount(float $subtotal): float;
    public function getUsageLimit(): int;
    public function getUsageCount(): int;
}
