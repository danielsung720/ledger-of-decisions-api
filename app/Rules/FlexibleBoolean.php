<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FlexibleBoolean implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_bool($value)) {
            return;
        }

        if (is_int($value) && in_array($value, [0, 1], true)) {
            return;
        }

        if (is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0'], true)) {
            return;
        }

        $fail('是否啟用欄位必須是布林值');
    }
}
