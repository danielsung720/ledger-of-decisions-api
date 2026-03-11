<?php

declare(strict_types=1);

namespace App\Enums;

enum ConfidenceLevel: string
{
    case High = 'high';     // 高信心
    case Medium = 'medium'; // 中等信心
    case Low = 'low';       // 低信心

    public function label(): string
    {
        return match ($this) {
            self::High => '高',
            self::Medium => '中',
            self::Low => '低',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
