<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\VerificationCodeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerificationCodeServiceTest extends TestCase
{
    #[Test]
    public function GenerateAndVerifyShouldSucceedAndClearCachedCode(): void
    {
        $service = new VerificationCodeService();
        $type = 'email_verification';
        $email = 'success-'.uniqid().'@example.com';

        $code = $service->generate($type, $email);

        $this->assertFalse($service->canResend($type, $email));
        $this->assertTrue($service->verify($type, $email, $code));
        $this->assertSame(5, $service->getRemainingAttempts($type, $email));
    }

    #[Test]
    public function VerifyShouldIncreaseAttemptsWhenCodeMissed(): void
    {
        $service = new VerificationCodeService();
        $type = 'password_reset';
        $email = 'miss-'.uniqid().'@example.com';

        $service->generate($type, $email);

        $this->assertFalse($service->verify($type, $email, '000000'));
        $this->assertSame(4, $service->getRemainingAttempts($type, $email));
    }

    #[Test]
    public function VerifyShouldReturnFalseWhenLockedOut(): void
    {
        $service = new VerificationCodeService();
        $type = 'email_verification';
        $email = 'locked-'.uniqid().'@example.com';

        for ($i = 0; $i < 5; $i++) {
            $service->verify($type, $email, '000000');
        }

        $this->assertTrue($service->isLockedOut($type, $email));
        $this->assertFalse($service->verify($type, $email, '123456'));
    }

    #[Test]
    public function GetCooldownSecondsShouldReturnZeroWhenNoSentAtKey(): void
    {
        $service = new VerificationCodeService();

        $this->assertSame(0, $service->getCooldownSeconds('email_verification', 'none@example.com'));
    }

    #[Test]
    public function VerificationCodeShouldExpireAfterTtl(): void
    {
        $this->runWithArrayCacheStore(function (): void {
            $service = new VerificationCodeService();
            $type = 'email_verification';
            $email = 'ttl-'.uniqid().'@example.com';
            $startAt = Carbon::create(2026, 2, 17, 10, 0, 0);

            Carbon::setTestNow($startAt);
            $code = $service->generate($type, $email);

            Carbon::setTestNow($startAt->copy()->addSeconds(601));

            $this->assertFalse($service->verify($type, $email, $code));
            $this->assertSame(4, $service->getRemainingAttempts($type, $email));
        });
    }

    #[Test]
    public function CooldownShouldExpireAfterConfiguredSeconds(): void
    {
        $this->runWithArrayCacheStore(function (): void {
            $service = new VerificationCodeService();
            $type = 'email_verification';
            $email = 'cooldown-'.uniqid().'@example.com';
            $startAt = Carbon::create(2026, 2, 17, 11, 0, 0);

            Carbon::setTestNow($startAt);
            $service->generate($type, $email);

            $this->assertFalse($service->canResend($type, $email));
            $this->assertSame(60, $service->getCooldownSeconds($type, $email));

            Carbon::setTestNow($startAt->copy()->addSeconds(61));

            $this->assertTrue($service->canResend($type, $email));
            $this->assertSame(0, $service->getCooldownSeconds($type, $email));
        });
    }

    private function runWithArrayCacheStore(callable $callback): void
    {
        $originalStore = config('cache.default');
        config(['cache.default' => 'array']);
        Cache::store('array')->clear();

        try {
            $callback();
        } finally {
            Carbon::setTestNow();
            Cache::store('array')->clear();
            config(['cache.default' => $originalStore]);
        }
    }
}
