<?php

declare(strict_types=1);

namespace App\Enums;

enum CacheEndpointEnum: string
{
    case Summary = 'Summary';
    case Trends = 'Trends';
    case CashFlowSummary = 'CashFlowSummary';
    case CashFlowProjection = 'CashFlowProjection';
    case Index = 'Index';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
