<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Mail\VerificationCodeMail;
use App\Services\MailService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailServiceTest extends TestCase
{
    #[Test]
    public function GenerateCodeShouldReturnSixDigitNumericString(): void
    {
        $service = new MailService();
        $code = $service->generateCode();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    #[Test]
    public function SendVerificationCodeShouldReturnTrueWhenMailSent(): void
    {
        Mail::fake();
        Log::spy();

        $service = new MailService();
        $result = $service->sendVerificationCode('tester@example.com', '123456', 'register');

        $this->assertTrue($result);
        Mail::assertSent(VerificationCodeMail::class);
    }

    #[Test]
    public function SendVerificationCodeShouldReturnFalseWhenMailThrowsException(): void
    {
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new Exception('smtp down'));
        Log::spy();

        $service = new MailService();
        $result = $service->sendVerificationCode('tester@example.com', '123456', 'reset_password');

        $this->assertFalse($result);
    }
}
