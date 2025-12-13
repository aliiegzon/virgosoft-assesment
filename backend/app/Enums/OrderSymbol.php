<?php

namespace App\Enums;

use App\Traits\EnumTrait;

enum OrderSymbol: string
{
    use EnumTrait;

    case BTC = 'BTC';
    case ETH = 'ETH';
    case USDT = 'USDT';

    public function price(): string
    {
        return match ($this) {
            self::BTC  => '90370.24',
            self::ETH  => '3091.86',
            self::USDT => '1.00',
        };
    }
}
