<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\MailService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestSendMailCommandTest extends TestCase
{
    #[Test]
    public function MailTestCommandShouldReturnSuccessWhenMailSent(): void
    {
        $mailService = Mockery::mock(MailService::class);
        $mailService->shouldReceive('generateCode')->once()->andReturn('123456');
        $mailService->shouldReceive('sendVerificationCode')
            ->once()
            ->with('success@example.com', '123456', 'register')
            ->andReturn(true);

        $this->app->instance(MailService::class, $mailService);

        $this->artisan('mail:test', ['email' => 'success@example.com'])
            ->assertSuccessful()
            ->expectsOutputToContain('Email sent successfully');
    }

    #[Test]
    public function MailTestCommandShouldReturnFailureWhenMailSendFails(): void
    {
        $mailService = Mockery::mock(MailService::class);
        $mailService->shouldReceive('generateCode')->once()->andReturn('654321');
        $mailService->shouldReceive('sendVerificationCode')
            ->once()
            ->with('failure@example.com', '654321', 'reset_password')
            ->andReturn(false);

        $this->app->instance(MailService::class, $mailService);

        $this->artisan('mail:test', ['email' => 'failure@example.com', '--type' => 'reset_password'])
            ->assertFailed()
            ->expectsOutputToContain('Failed to send email');
    }
}
