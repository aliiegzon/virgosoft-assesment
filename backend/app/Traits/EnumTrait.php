<?php

namespace App\Traits;

trait EnumTrait
{
    /**
     * @return array
     */
    public static function getAllValuesAsArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return string
     */
    public static function getAllValuesAsString(): string
    {
        return implode(',', array_column(self::cases(), 'value'));
    }
}
