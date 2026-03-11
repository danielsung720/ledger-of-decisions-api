<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\DecisionResource;
use App\Models\Decision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DecisionResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_includes_null_confidence_level_when_cleared(): void
    {
        $decision = Decision::factory()->create([
            'intent' => 'necessity',
            'confidence_level' => null,
        ]);

        $data = (new DecisionResource($decision))->toArray(new Request);

        $this->assertSame('necessity', $data['intent']);
        $this->assertNull($data['confidence_level']);
        $this->assertNull($data['confidence_level_label']);
    }
}
