<?php

namespace App\Enums;

enum KlineInterval: string
{
    case ONE_MINUTE = '1m';
    case FIVE_MINUTES = '5m';
    case FIFTEEN_MINUTES = '15m';
    case THIRTY_MINUTES = '30m';
    case ONE_HOUR = '1h';
    case FOUR_HOURS = '4h';
    case ONE_DAY = '1d';

    public function label(): string
    {
        return match ($this) {
            self::ONE_MINUTE => '1 минута',
            self::FIVE_MINUTES => '5 минут',
            self::FIFTEEN_MINUTES => '15 минут',
            self::THIRTY_MINUTES => '30 минут',
            self::ONE_HOUR => '1 час',
            self::FOUR_HOURS => '4 часа',
            self::ONE_DAY => '1 день',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
