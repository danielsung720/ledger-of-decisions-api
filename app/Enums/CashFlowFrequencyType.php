<?php

declare(strict_types=1);

namespace App\Enums;

enum CashFlowFrequencyType: string
{
    case Monthly = 'monthly';   // 每月
    case Yearly = 'yearly';     // 每年
    case OneTime = 'one_time';  // 一次性

    public function label(): string
    {
        return match ($this) {
            self::Monthly => '每月',
            self::Yearly => '每年',
            self::OneTime => '一次性',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Calculate the monthly equivalent amount for a given amount.
     */
    public function toMonthlyAmount(string $amount, int $interval = 1): string
    {
        $interval = max($interval, 1); // Ensure minimum of 1 to prevent division by zero

        return match ($this) {
            self::Monthly => self::divideToScale($amount, (string) $interval),
            self::Yearly => self::divideToScale($amount, (string) (12 * $interval)),
            self::OneTime => '0.00', // One-time items don't contribute to monthly
        };
    }

    private static function divideToScale(string $amount, string $divisor, int $scale = 2): string
    {
        if (function_exists('bcdiv')) {
            $raw = bcdiv($amount, $divisor, $scale + 1);
            $rounding = '0.' . str_repeat('0', $scale) . '5';

            return bcadd($raw, $rounding, $scale);
        }

        return number_format((float) $amount / (float) $divisor, $scale, '.', '');
    }
}
