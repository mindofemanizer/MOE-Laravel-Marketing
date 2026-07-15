<?php

namespace Moe\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingAttributionLog extends Model
{
    use SoftDeletes;

    protected $table;

    const ACTION_ATTRIBUTE = 'attribute';
    const ACTION_TRANSFER = 'transfer';
    const ACTION_REMOVE = 'remove';

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

    public function customerUser()
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'customer_user_id');
    }

    public function fromMarketingUser()
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'from_marketing_user_id');
    }

    public function toMarketingUser()
    {
        return $this->belongsTo(config('marketing.models.user', 'App\\Models\\User'), 'to_marketing_user_id');
    }
}
