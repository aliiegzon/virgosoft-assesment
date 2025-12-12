<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum OrderSide: string
{
    use EnumTrait;
    case SELL = 'sell';
    case BUY = 'buy';
}
