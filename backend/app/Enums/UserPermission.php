<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum UserPermission: string
{
    use EnumTrait;

    case ADMIN_PORTAL = 'access_admin_panel';
}
