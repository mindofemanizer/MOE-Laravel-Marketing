<?php

declare(strict_types=1);

namespace Moe\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionLedger extends Model
{
    use SoftDeletes;

    protected $table;

    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_RELEASED = 'released';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_ON_HOLD => 'Ditahan',
        self::STATUS_RELEASED => 'Dirilis',
        self::STATUS_REVERSED => 'Dikembalikan',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'marketing_user_id',
        'customer_user_id',
        'order_id',
        'order_item_id',
        'amount',
        'rate',
        'status',
        'release_due_at',
        'released_at',
        'reversed_at',
        'reversal_reason',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'release_due_at' => 'datetime',
        'released_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('marketing.tables.commission_ledger', 'commission_ledger');
    }

    /**
     * @return BelongsTo
     */
    public function marketingUser(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'marketing_user_id');
    }

    /**
     * @return BelongsTo
     */
    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'customer_user_id');
    }

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.order', 'App\\Models\\Order'), 'order_id');
    }

    /**
     * @return BelongsTo
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.order_item', 'App\\Models\\OrderItem'), 'order_item_id');
    }

    /**
     * @return void
     */
    public function release(): void
    {
        $this->update([
            'status' => self::STATUS_RELEASED,
            'released_at' => now(),
        ]);
    }

    /**
     * @param string $reason
     * @return void
     */
    public function reverse(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversal_reason' => $reason,
        ]);
    }

    /**
     * @return void
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }
}
