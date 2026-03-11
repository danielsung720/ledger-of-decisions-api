<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthRequestsToDtoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function RegisterRequestToDtoShouldMapValidatedPayload(): void
    {
        $request = RegisterRequest::create('/api/register', 'POST', [
            'name' => 'DTO User',
            'email' => 'dto-register@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toDto();

        $this->assertSame('DTO User', $dto->name);
        $this->assertSame('dto-register@example.com', $dto->email);
    }

    #[Test]
    public function LoginRequestToDtoShouldMapValidatedPayload(): void
    {
        $request = LoginRequest::create('/api/login', 'POST', [
            'email' => 'dto-login@example.com',
            'password' => 'password123',
        ]);
        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toDto();

        $this->assertSame('dto-login@example.com', $dto->email);
        $this->assertSame('password123', $dto->password);
    }

    #[Test]
    public function VerifyEmailRequestToDtoShouldMapValidatedPayload(): void
    {
        $request = VerifyEmailRequest::create('/api/verify-email', 'POST', [
            'email' => 'dto-verify@example.com',
            'code' => '123456',
        ]);
        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toDto();

        $this->assertSame('dto-verify@example.com', $dto->email);
        $this->assertSame('123456', $dto->code);
    }

    #[Test]
    public function EmailOnlyRequestsToDtoShouldMapValidatedPayload(): void
    {
        $resend = ResendVerificationRequest::create('/api/resend-verification', 'POST', [
            'email' => 'dto-resend@example.com',
        ]);
        $resend->setContainer(app());
        $resend->validateResolved();
        $this->assertSame('dto-resend@example.com', $resend->toDto()->email);

        $forgot = ForgotPasswordRequest::create('/api/forgot-password', 'POST', [
            'email' => 'dto-forgot@example.com',
        ]);
        $forgot->setContainer(app());
        $forgot->validateResolved();
        $this->assertSame('dto-forgot@example.com', $forgot->toDto()->email);
    }

    #[Test]
    public function ResetPasswordRequestToDtoShouldMapValidatedPayload(): void
    {
        $request = ResetPasswordRequest::create('/api/reset-password', 'POST', [
            'email' => 'dto-reset@example.com',
            'code' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toDto();

        $this->assertSame('dto-reset@example.com', $dto->email);
        $this->assertSame('123456', $dto->code);
        $this->assertSame('newpassword123', $dto->password);
    }

    #[Test]
    public function UpdatePasswordRequestToDtoShouldMapValidatedPayload(): void
    {
        /** @var UpdatePasswordRequest&\Mockery\MockInterface $request */
        $request = Mockery::mock(UpdatePasswordRequest::class)->makePartial();
        $request->shouldReceive('validated')->once()->andReturn([
            'password' => 'newpassword123',
        ]);

        $dto = $request->toDto();

        $this->assertSame('newpassword123', $dto->password);
    }
}
