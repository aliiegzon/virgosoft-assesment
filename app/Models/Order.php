<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\QueryBuilder\AllowedFilter;

class Order extends BaseModel
{
    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
        'locked_value',
    ];

    protected $casts = [
        'price'        => 'decimal:8',
        'amount'       => 'decimal:8',
        'locked_value' => 'decimal:8',
        'status'       => OrderStatus::class,
    ];

    /**
     * @return array
     */
    public function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('symbol'),
            AllowedFilter::exact('side'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('user_id'),
        ];
    }

    /**
     * @return array
     */
    public function allowedSorts(): array
    {
        return [
            'symbol',
            'side',
            'status',
            'price',
            'amount',
            'created_at',
        ];
    }

    /**
     * @return array
     */
    public function defaultSorts(): array
    {
        return [
            '-created_at',
        ];
    }

    /**
     * @return array
     */
    public function allowedIncludes(): array
    {
        return [
            'user',
        ];
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
