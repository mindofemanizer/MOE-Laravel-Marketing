<?php

declare(strict_types=1);

namespace Moe\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moe\Marketing\Contracts\PromoInterface;

class Promo extends Model implements PromoInterface
{
    use SoftDeletes;

    protected $table;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_NOMINAL = 'nominal';
    public const TYPE_FREE_SHIPPING = 'free_shipping';

    public const TYPE_LABELS = [
        self::TYPE_PERCENTAGE => 'Persentase',
        self::TYPE_NOMINAL => 'Nominal',
        self::TYPE_FREE_SHIPPING => 'Gratis Ongkir',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'discount_value',
        'minimum_order',
        'maximum_discount',
        'usage_limit',
        'usage_limit_per_user',
        'start_date',
        'end_date',
        'is_active',
        'applies_to',
        'applicable_ids',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_order' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'applicable_ids' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('marketing.tables.promos', 'promos');
    }

    /**
     * @return HasMany
     */
    public function usages(): HasMany
    {
        return $this->hasMany(PromoUsage::class);
    }

    // PromoInterface
    public function isValid(): bool
    {
        return $this->is_active
            && ! $this->isExpired()
            && ($this->usage_limit === null || $this->getUsageCount() < $this->usage_limit);
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function getDiscount(float $subtotal): float
    {
        if ($subtotal < $this->minimum_order) {
            return 0;
        }

        return match ($this->type) {
            self::TYPE_PERCENTAGE => min(
                $subtotal * $this->discount_value / 100,
                $this->maximum_discount ?? PHP_FLOAT_MAX
            ),
            self::TYPE_NOMINAL => min($this->discount_value, $subtotal),
            self::TYPE_FREE_SHIPPING => 0,
            default => 0,
        };
    }

    /**
     * @return int
     */
    public function getUsageLimit(): int
    {
        return $this->usage_limit ?? PHP_INT_MAX;
    }

    /**
     * @return int
     */
    public function getUsageCount(): int
    {
        return $this->usages()->count();
    }

    /**
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        if (! $this->is_active) {
            return 'Nonaktif';
        }
        if ($this->isExpired()) {
            return 'Kadaluwarsa';
        }

        return 'Aktif';
    }
}
