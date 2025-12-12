<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class BasePolicy
{
    /**
     * @param User|null $user
     * @return ?bool
     */
    public function before(?User $user): ?bool
    {
         if (isset($user) && $user->hasRole(UserRole::ADMIN_ROLE)) {
             return true;
         }

        return null;
    }
}
