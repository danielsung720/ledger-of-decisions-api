<?php

declare(strict_types=1);

namespace App\Enums;

enum Category: string
{
    case Food = 'food';           // 飲食
    case Transport = 'transport'; // 交通
    case Training = 'training';   // 學習/訓練
    case Living = 'living';       // 生活
    case Other = 'other';         // 其他

    public function label(): string
    {
        return match ($this) {
            self::Food => '飲食',
            self::Transport => '交通',
            self::Training => '學習/訓練',
            self::Living => '生活',
            self::Other => '其他',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
