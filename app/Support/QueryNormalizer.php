<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

final class QueryNormalizer
{
    /**
     * @param  array<string, mixed>  $query
     * @param  array<string>  $allowedKeys
     * @param  array<string>  $csvKeys
     * @param  array<string>  $intKeys
     * @param  array<string>  $boolKeys
     * @param  array<string>  $dateKeys
     * @return array<string, mixed>
     */
    public function normalize(
        array $query,
        array $allowedKeys = [],
        array $csvKeys = [],
        array $intKeys = [],
        array $boolKeys = [],
        array $dateKeys = []
    ): array {
        $normalized = [];

        foreach ($query as $key => $value) {
            if ($allowedKeys !== [] && ! in_array($key, $allowedKeys, true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($key, $csvKeys, true)) {
                $csv = $this->normalizeCsv($value);

                if ($csv === '') {
                    continue;
                }

                $normalized[$key] = $csv;

                continue;
            }

            if (in_array($key, $intKeys, true)) {
                $normalized[$key] = (int) $value;

                continue;
            }

            if (in_array($key, $boolKeys, true)) {
                $normalized[$key] = $this->normalizeBool($value);

                continue;
            }

            if (in_array($key, $dateKeys, true)) {
                $normalizedDate = $this->normalizeDate($value);

                if ($normalizedDate !== null) {
                    $normalized[$key] = $normalizedDate;
                }

                continue;
            }

            $normalized[$key] = is_string($value) ? trim($value) : $value;
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $normalizedQuery
     */
    public function hash(array $normalizedQuery): string
    {
        return sha1((string) json_encode($normalizedQuery, JSON_THROW_ON_ERROR));
    }

    private function normalizeCsv(mixed $value): string
    {
        $raw = is_array($value) ? $value : explode(',', (string) $value);
        $items = [];

        foreach ($raw as $item) {
            $trimmed = trim((string) $item);

            if ($trimmed !== '') {
                $items[] = $trimmed;
            }
        }

        $items = array_values(array_unique($items));
        sort($items, SORT_STRING);

        return implode(',', $items);
    }

    private function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function normalizeDate(mixed $value): ?string
    {
        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
