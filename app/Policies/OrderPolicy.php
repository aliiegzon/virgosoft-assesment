<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends BasePolicy
{
    public function viewAny(User $authUser): bool
    {
        return true;
    }

    public function view(User $authUser, Order $order): bool
    {
        return $authUser->id === $order->user_id;
    }

    public function create(User $authUser): bool
    {
        return true;
    }

    public function update(User $authUser, Order $order): bool
    {
        return $authUser->id === $order->user_id;
    }

    public function delete(User $authUser, Order $order): bool
    {
        return $authUser->id === $order->user_id;
    }
}
