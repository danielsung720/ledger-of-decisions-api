<?php

declare(strict_types=1);

namespace App\Enums;

enum Intent: string
{
    case Necessity = 'necessity';   // 必要性
    case Efficiency = 'efficiency'; // 效率
    case Enjoyment = 'enjoyment';   // 享受
    case Recovery = 'recovery';     // 恢復
    case Impulse = 'impulse';       // 衝動

    public function label(): string
    {
        return match ($this) {
            self::Necessity => '必要性',
            self::Efficiency => '效率',
            self::Enjoyment => '享受',
            self::Recovery => '恢復',
            self::Impulse => '衝動',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
