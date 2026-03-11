<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\Intent;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IntentList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $intents = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : null);

        if ($intents === null) {
            $fail('意圖格式不正確');

            return;
        }

        foreach ($intents as $intent) {
            if (!is_string($intent) || Intent::tryFrom(trim($intent)) === null) {
                $fail('無效的意圖');

                return;
            }
        }
    }
}
