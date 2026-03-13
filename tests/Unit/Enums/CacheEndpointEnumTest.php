<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CacheEndpointEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CacheEndpointEnumTest extends TestCase
{
    #[Test]
    public function ValuesReturnsAllSupportedEndpoints(): void
    {
        $this->assertSame(
            ['Summary', 'Trends', 'CashFlowSummary', 'CashFlowProjection', 'Index'],
            CacheEndpointEnum::values()
        );
    }
}
