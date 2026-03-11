<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\CashFlowFrequencyType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FrequencyTypeList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $types = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : null);

        if ($types === null) {
            $fail('週期類型格式不正確');

            return;
        }

        foreach ($types as $type) {
            if (!is_string($type) || CashFlowFrequencyType::tryFrom(trim($type)) === null) {
                $fail('無效的週期類型');

                return;
            }
        }
    }
}
