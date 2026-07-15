<?php

declare(strict_types=1);

namespace Moe\Marketing\Contracts;

interface CommissionableInterface
{
    /**
     * @return float
     */
    public function getCommissionRate(): float;

    /**
     * @return float
     */
    public function getQualifyingAmount(): float;

    /**
     * @return float
     */
    public function calculateCommission(): float;

    /**
     * @return bool
     */
    public function canEarnCommission(): bool;
}
