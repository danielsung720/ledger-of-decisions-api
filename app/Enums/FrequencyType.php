<?php

declare(strict_types=1);

namespace App\Enums;

enum FrequencyType: string
{
    case Daily = 'daily';     // 每日
    case Weekly = 'weekly';   // 每週
    case Monthly = 'monthly'; // 每月
    case Yearly = 'yearly';   // 每年

    public function label(): string
    {
        return match ($this) {
            self::Daily => '每日',
            self::Weekly => '每週',
            self::Monthly => '每月',
            self::Yearly => '每年',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
