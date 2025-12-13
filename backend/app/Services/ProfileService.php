<?php

namespace App\Services;

use App\Models\User;

class ProfileService extends BaseService
{
    /**
     * @param  User  $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  User  $user
     * @return array
     */
    public function showProfile(User $user): array
    {
        $user->load([
            'assets',
            'openOrders',
        ]);

        return [
            'id'          => $user->id,
            'balance_usd' => $user->balance,
            'assets'      => $user->assets->map(function ($asset) {
                return [
                    'symbol'        => $asset->symbol,
                    'amount'        => $asset->amount,
                    'locked_amount' => $asset->locked_amount,
                ];
            })->values(),
            'open_orders' => $user->openOrders,
        ];
    }
}
