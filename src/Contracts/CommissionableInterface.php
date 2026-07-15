<?php

namespace Moe\Marketing\Contracts;

interface CommissionableInterface
{
    public function getCommissionRate(): float;
    public function getQualifyingAmount(): float;
    public function calculateCommission(): float;
    public function canEarnCommission(): bool;
}
