<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum UserRole: string
{
    use EnumTrait;

    case ADMIN_ROLE = 'admin';
    case USER_ROLE = 'user';
}
