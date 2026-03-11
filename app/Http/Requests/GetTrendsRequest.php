<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Validate trends endpoint query parameters.
 */
class GetTrendsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
