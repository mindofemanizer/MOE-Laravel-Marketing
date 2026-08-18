<?php

declare(strict_types=1);

namespace Moe\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionLedger extends Model
{
    protected $table;

    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_RELEASED = 'released';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_ON_HOLD => 'Menunggu Pelepasan',
        self::STATUS_RELEASED => 'Sudah Cair',
        self::STATUS_REVERSED => 'Dibatalkan (Refund)',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'marketing_user_id',
        'customer_user_id',
        'order_id',
        'order_item_id',
        'amount',
        'rate',
        'gross_amount',
        'cost_amount',
        'discount_amount',
        'gross_profit',
        'commission_amount',
        'status',
        'recognized_at',
        'hold_release_at',
        'released_at',
        'reversed_at',
        'meta',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'cost_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'recognized_at' => 'datetime',
        'hold_release_at' => 'datetime',
        'released_at' => 'datetime',
        'reversed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('marketing.tables.commission_ledger', 'commission_ledger');
    }

    public function marketingUser(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'marketing_user_id');
    }

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'customer_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.order', 'App\\Models\\Order'), 'order_id');
    }

    public function release(): void
    {
        $this->update([
            'status' => self::STATUS_RELEASED,
            'released_at' => now(),
        ]);
    }

    public function reverse(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REVERSED,
            'reversed_at' => now(),
            'meta' => array_merge($this->meta ?? [], [
                'reverse_reason' => $reason,
            ]),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function isOnHold(): bool
    {
        return $this->status === self::STATUS_ON_HOLD;
    }

    public function isReleased(): bool
    {
        return $this->status === self::STATUS_RELEASED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }

    /**
     * Apakah ledger sudah lewat hold release window.
     */
    public function isReleasable(): bool
    {
        return $this->isOnHold()
            && $this->hold_release_at !== null
            && $this->hold_release_at->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }
}
