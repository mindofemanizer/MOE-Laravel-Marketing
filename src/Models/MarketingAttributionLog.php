<?php

declare(strict_types=1);

namespace Moe\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingAttributionLog extends Model
{
    use SoftDeletes;

    protected $table;

    public const ACTION_ATTRIBUTE = 'attribute';
    public const ACTION_TRANSFER = 'transfer';
    public const ACTION_REMOVE = 'remove';

    protected $fillable = [
        'customer_user_id',
        'from_marketing_user_id',
        'to_marketing_user_id',
        'action',
        'source',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'performed_by' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('marketing.tables.attribution_logs', 'marketing_attribution_logs');
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
    public function fromMarketingUser(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'from_marketing_user_id');
    }

    /**
     * @return BelongsTo
     */
    public function toMarketingUser(): BelongsTo
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'to_marketing_user_id');
    }
}
