<?php

declare(strict_types=1);

namespace Moe\Marketing\Contracts;

interface CommissionableInterface
{
    public function getCommissionRate(): float;
    public function getQualifyingAmount(): float;
    public function calculateCommission(): float;
    public function canEarnCommission(): bool;
}
