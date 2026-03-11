<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

trait HasCashFlowFilterHelpers
{
    /**
     * @return array<string>
     */
    protected function parseQueryArray(string $key): array
    {
        $rawValue = $this->input($key);

        if (is_array($rawValue)) {
            return array_values(array_filter(
                array_map(static fn (mixed $value): string => trim((string) $value), $rawValue),
                static fn (string $value): bool => $value !== ''
            ));
        }

        if (!is_string($rawValue)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $rawValue)),
            static fn (string $value): bool => $value !== ''
        ));
    }

    protected function resolvePerPage(): int
    {
        $defaultPerPage = (int) config('pagination.default_per_page', 15);
        $maxPerPage = (int) config('pagination.max_per_page', 100);

        return min(max((int) $this->input('per_page', $defaultPerPage), 1), $maxPerPage);
    }
}
