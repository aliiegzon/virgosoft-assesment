<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy extends BasePolicy
{
    /**
     * @param User $authUser
     * @return bool
     */
    public function viewAny(User $authUser): bool
    {
        return false;
    }

    /**
     * @param User $authUser
     * @param User $model
     * @return bool
     */
    public function view(User $authUser, User $model): bool
    {
        return false;
    }

    /**
     * @param User $authUser
     * @return bool
     */
    public function create(User $authUser): bool
    {
        return false;
    }

    /**
     * @param User $authUser
     * @param User $model
     * @return bool
     */
    public function update(User $authUser, User $model): bool
    {
        return false;
    }

    /**
     * @param User $authUser
     * @param User $model
     * @return bool
     */
    public function delete(User $authUser, User $model): bool
    {
        return false;
    }
}
