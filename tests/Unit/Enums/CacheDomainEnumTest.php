<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CacheDomainEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CacheDomainEnumTest extends TestCase
{
    #[Test]
    public function ValuesReturnsAllSupportedDomains(): void
    {
        $this->assertSame(['Statistics', 'CashFlow', 'Expenses'], CacheDomainEnum::values());
    }
}
