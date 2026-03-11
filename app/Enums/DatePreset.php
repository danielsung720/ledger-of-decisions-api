<?php

declare(strict_types=1);

namespace App\Enums;

enum DatePreset: string
{
    case Today = 'today';
    case ThisWeek = 'this_week';
    case ThisMonth = 'this_month';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
