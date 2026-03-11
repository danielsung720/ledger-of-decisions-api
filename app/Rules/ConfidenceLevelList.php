<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\ConfidenceLevel;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ConfidenceLevelList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $levels = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : null);

        if ($levels === null) {
            $fail('信心程度格式不正確');

            return;
        }

        foreach ($levels as $level) {
            if (!is_string($level) || ConfidenceLevel::tryFrom(trim($level)) === null) {
                $fail('無效的信心程度');

                return;
            }
        }
    }
}
