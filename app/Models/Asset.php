<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends BaseModel
{
    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];

    protected $casts = [
        'amount'        => 'decimal:8',
        'locked_amount' => 'decimal:8',
    ];

    /**
     * @return array
     */
    public function allowedFilters(): array
    {
        return [
            'symbol',
            'user_id',
        ];
    }

    /**
     * @return array
     */
    public function allowedSorts(): array
    {
        return [
            'symbol',
            'amount',
            'locked_amount',
            'created_at',
        ];
    }

    /**
     * @return array
     */
    public function defaultSorts(): array
    {
        return ['symbol'];
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
