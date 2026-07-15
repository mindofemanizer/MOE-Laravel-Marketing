<?php

namespace Moe\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoUsage extends Model
{
    use SoftDeletes;

    protected $table;

    protected $fillable = [
        'promo_id',
        'order_id',
        'user_id',
        'discount_amount',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('marketing.tables.promo_usages', 'promo_usages');
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function order()
    {
        return $this->belongsTo(config('marketing.models.order', 'App\\Models\\Order'));
    }

    public function user()
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'));
    }
}
