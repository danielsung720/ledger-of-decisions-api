<?php

declare(strict_types=1);

namespace App\Enums;

enum CacheDomainEnum: string
{
    case Statistics = 'Statistics';
    case CashFlow = 'CashFlow';
    case Expenses = 'Expenses';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
