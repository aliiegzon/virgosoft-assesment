<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\QueryBuilder\AllowedFilter;

class Trade extends BaseModel
{
    protected $fillable = [
        'symbol',
        'buy_order_id',
        'sell_order_id',
        'price',
        'amount',
        'volume_usd',
        'fee_usd',
    ];

    protected $casts = [
        'price'      => 'decimal:8',
        'amount'     => 'decimal:8',
        'volume_usd' => 'decimal:8',
        'fee_usd'    => 'decimal:8',
    ];

    /**
     * @return BelongsTo
     */
    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    /**
     * @return BelongsTo
     */
    public function sellOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }

    /**
     * @return array
     */
    public function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('symbol'),
            AllowedFilter::exact('buy_order_id'),
            AllowedFilter::exact('sell_order_id'),
        ];
    }

    /**
     * @return array
     */
    public function allowedSorts(): array
    {
        return [
            'symbol',
            'price',
            'amount',
            'volume_usd',
            'fee_usd',
            'created_at',
        ];
    }

    /**
     * @return array
     */
    public function defaultSorts(): array
    {
        return ['-created_at'];
    }

    /**
     * @return array
     */
    public function allowedIncludes(): array
    {
        return [
            'buyOrder',
            'sellOrder',
        ];
    }
}
