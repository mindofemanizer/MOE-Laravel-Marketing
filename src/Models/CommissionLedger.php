<?php

namespace Moe\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionLedger extends Model
{
    use SoftDeletes;

    protected $table;

    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_RELEASED = 'released';
    const STATUS_REVERSED = 'reversed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUS_LABELS = [
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

    public function marketingUser()
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'marketing_user_id');
    }

    public function customerUser()
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'customer_user_id');
    }

    public function order()
    {
        return $this->belongsTo(config('marketing.models.order', 'App\\Models\\Order'), 'order_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(config('marketing.models.order_item', 'App\\Models\\OrderItem'), 'order_item_id');
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
            'reversal_reason' => $reason,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }
}
