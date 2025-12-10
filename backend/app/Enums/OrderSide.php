<?php 

namespace App\Enums;

enum OrderSide: string
{
    case BUY = 'buy';
    case SELL = 'sell';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function array(): array
    {
        return array_combine(self::names(), self::values());
    }
}