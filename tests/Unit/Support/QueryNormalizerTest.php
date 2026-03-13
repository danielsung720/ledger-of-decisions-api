<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\QueryNormalizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QueryNormalizerTest extends TestCase
{
    #[Test]
    public function NormalizeAppliesWhitelistTypingSortingAndCsvRules(): void
    {
        $normalizer = new QueryNormalizer;

        $normalized = $normalizer->normalize(
            query: [
                'unknown' => 'drop-me',
                'per_page' => '10',
                'page' => '2',
                'category' => 'food, transport,food',
                'start_date' => '2026/03/01',
                'enabled' => 'true',
                'empty' => '',
            ],
            allowedKeys: ['per_page', 'page', 'category', 'start_date', 'enabled', 'empty'],
            csvKeys: ['category'],
            intKeys: ['per_page', 'page'],
            boolKeys: ['enabled'],
            dateKeys: ['start_date']
        );

        $this->assertSame([
            'category' => 'food,transport',
            'enabled' => true,
            'page' => 2,
            'per_page' => 10,
            'start_date' => '2026-03-01',
        ], $normalized);
    }

    #[Test]
    public function HashReturnsStableDigestForSamePayload(): void
    {
        $normalizer = new QueryNormalizer;

        $payload = ['a' => 1, 'b' => 'two'];

        $this->assertSame($normalizer->hash($payload), $normalizer->hash($payload));
    }
}
