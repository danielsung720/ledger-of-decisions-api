<?php

declare(strict_types=1);

namespace App\Rules;

use App\Enums\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CategoryList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $categories = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : null);

        if ($categories === null) {
            $fail('類別格式不正確');

            return;
        }

        foreach ($categories as $category) {
            if (!is_string($category) || Category::tryFrom(trim($category)) === null) {
                $fail('無效的類別');

                return;
            }
        }
    }
}
