<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\IncomeResource;
use App\Models\Income;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IncomeResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ToArrayShouldFormatOneTimeFrequencyDisplay(): void
    {
        $income = Income::factory()->oneTime()->create([
            'amount' => 50000,
            'frequency_interval' => 1,
        ]);

        $data = (new IncomeResource($income))->toArray(new Request());

        $this->assertSame('$50000.00', $data['amount_display']);
        $this->assertSame('一次性', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFormatIntervalFrequencyDisplay(): void
    {
        $income = Income::factory()->yearly()->create([
            'amount' => 120000,
            'frequency_interval' => 2,
        ]);

        $data = (new IncomeResource($income))->toArray(new Request());

        $this->assertSame('每 2 年', $data['frequency_display']);
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

        $payload = new class($frequencyType) {
            public int $id = 1;
            public string $name = '測試收入';
            public string $amount = '2000.00';
            public string $currency = 'TWD';
            public int $frequency_interval = 2;
            public ?\Illuminate\Support\Carbon $start_date;
            public ?\Illuminate\Support\Carbon $end_date = null;
            public ?string $note = null;
            public bool $is_active = true;
            public \Illuminate\Support\Carbon $created_at;
            public \Illuminate\Support\Carbon $updated_at;

            public function __construct(public object $frequency_type)
            {
                $this->start_date = now();
                $this->created_at = now();
                $this->updated_at = now();
            }

            public function getMonthlyAmount(): string
            {
                return '600.00';
            }
        };

        $data = (new IncomeResource($payload))->toArray(new Request());

        $this->assertSame('每週', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFormatDefaultIntervalFrequencyDisplay(): void
    {
        $income = Income::factory()->monthly()->create([
            'frequency_interval' => 1,
        ]);

        $data = (new IncomeResource($income))->toArray(new Request());

        $this->assertSame('每月', $data['frequency_display']);
    }

    #[Test]
    public function ToArrayShouldFormatMonthlyIntervalFrequencyDisplay(): void
    {
        $income = Income::factory()->monthly()->create([
            'frequency_interval' => 2,
        ]);

        $data = (new IncomeResource($income))->toArray(new Request());

        $this->assertSame('每 2 月', $data['frequency_display']);
    }
}
