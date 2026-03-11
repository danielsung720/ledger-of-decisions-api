<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\CashFlowItemResource;
use App\Models\CashFlowItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashFlowItemResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ToArrayShouldFormatOneTimeFrequencyDisplay(): void
    {
        $item = CashFlowItem::factory()->oneTime()->create([
            'amount' => 50000,
            'frequency_interval' => 1,
        ]);

        $data = (new CashFlowItemResource($item))->toArray(new Request());

        $this->assertSame('$50000.00', $data['amount_display']);
        $this->assertSame('一次性', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFormatIntervalFrequencyDisplay(): void
    {
        $item = CashFlowItem::factory()->monthly()->create([
            'amount' => 12000,
            'frequency_interval' => 3,
        ]);

        $data = (new CashFlowItemResource($item))->toArray(new Request());

        $this->assertSame('每 3 月', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFallbackToFrequencyLabelForUnknownFrequencyType(): void
    {
        $frequencyType = new class {
            public string $value = 'weekly';

            public function label(): string
            {
                return '每週';
            }
        };

        $category = new class {
            public string $value = 'living';

            public function label(): string
            {
                return '生活';
            }
        };

        $payload = new class($frequencyType, $category) {
            public int $id = 1;
            public string $name = '測試項目';
            public string $amount = '1000.00';
            public string $currency = 'TWD';
            public int $frequency_interval = 2;
            public ?\Illuminate\Support\Carbon $start_date;
            public ?\Illuminate\Support\Carbon $end_date = null;
            public ?string $note = null;
            public bool $is_active = true;
            public \Illuminate\Support\Carbon $created_at;
            public \Illuminate\Support\Carbon $updated_at;

            public function __construct(
                public object $frequency_type,
                public object $category
            ) {
                $this->start_date = now();
                $this->created_at = now();
                $this->updated_at = now();
            }

            public function getMonthlyAmount(): string
            {
                return '500.00';
            }
        };

        $data = (new CashFlowItemResource($payload))->toArray(new Request());

        $this->assertSame('每週', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFormatDefaultIntervalFrequencyDisplay(): void
    {
        $item = CashFlowItem::factory()->monthly()->create([
            'frequency_interval' => 1,
        ]);

        $data = (new CashFlowItemResource($item))->toArray(new Request());

        $this->assertSame('每月', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFormatYearlyIntervalFrequencyDisplay(): void
    {
        $item = CashFlowItem::factory()->yearly()->create([
            'frequency_interval' => 2,
        ]);

        $data = (new CashFlowItemResource($item))->toArray(new Request());

        $this->assertSame('每 2 年', $data['frequency_display']);
    }
}
