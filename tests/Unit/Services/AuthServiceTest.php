<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Auth\EmailOnlyDto;
use App\DTO\Auth\LoginDto;
use App\DTO\Auth\RegisterDto;
use App\DTO\Auth\ResetPasswordDto;
use App\DTO\Auth\UpdatePasswordDto;
use App\DTO\Auth\VerifyEmailDto;
use App\Models\User;
use App\Repositories\AuthRepository;
use App\Services\AuthService;
use App\Services\MailService;
use App\Services\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private VerificationCodeService&MockInterface $verificationCodeService;

    private MailService&MockInterface $mailService;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verificationCodeService = Mockery::mock(VerificationCodeService::class);
        $this->mailService = Mockery::mock(MailService::class);

        $this->service = new AuthService(
            new AuthRepository,
            $this->verificationCodeService,
            $this->mailService
        );
    }

    #[Test]
    public function RegisterShouldCreateUserAndReturnCreatedResult(): void
    {
        $this->verificationCodeService
            ->shouldReceive('generate')
            ->once()
            ->with('email_verification', 'register@example.com')
            ->andReturn('123456');

        $this->mailService
            ->shouldReceive('sendVerificationCode')
            ->once()
            ->with('register@example.com', '123456', 'register')
            ->andReturn(true);

        $result = $this->service->register(
            new RegisterDto(name: 'Register User', email: 'register@example.com', password: 'password123')
        );

        $this->assertTrue($result->success);
        $this->assertSame(201, $result->statusCode);
        $this->assertSame('註冊成功，請檢查您的信箱以驗證帳戶', $result->message);
        $this->assertDatabaseHas('users', ['email' => 'register@example.com']);
    }

    #[Test]
    public function RegisterShouldStillReturnSuccessWhenMailSendFails(): void
    {
        $this->verificationCodeService
            ->shouldReceive('generate')
            ->once()
            ->with('email_verification', 'register-fail@example.com')
            ->andReturn('654321');

        $this->mailService
            ->shouldReceive('sendVerificationCode')
            ->once()
            ->with('register-fail@example.com', '654321', 'register')
            ->andReturn(false);

        $result = $this->service->register(
            new RegisterDto(name: 'Register Mail Fail', email: 'register-fail@example.com', password: 'password123')
        );

        $this->assertTrue($result->success);
        $this->assertSame(201, $result->statusCode);
        $this->assertDatabaseHas('users', ['email' => 'register-fail@example.com']);
    }

    #[Test]
    public function LoginShouldReturnUserPayloadForVerifiedUser(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => 'password123',
        ]);

        $result = $this->service->login(
            new LoginDto(email: 'verified@example.com', password: 'password123')
        );

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('登入成功', $result->message);
        $this->assertSame($user->email, $result->data['user']['email']);
        $this->assertArrayNotHasKey('token', $result->data);
        $this->assertArrayNotHasKey('token_type', $result->data);
    }

    #[Test]
    public function LoginShouldReturnEmailNotVerifiedForUnverifiedUser(): void
    {
        User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $result = $this->service->login(
            new LoginDto(email: 'unverified@example.com', password: 'password123')
        );

        $this->assertFalse($result->success);
        $this->assertSame(403, $result->statusCode);
        $this->assertSame('email_not_verified', $result->error);
        $this->assertSame('unverified@example.com', $result->data['email']);
    }

    #[Test]
    public function ForgotPasswordShouldReturnGenericSuccessWhenCannotResend(): void
    {
        User::factory()->create(['email' => 'cooldown@example.com']);

        $this->verificationCodeService
            ->shouldReceive('canResend')
            ->once()
            ->with('password_reset', 'cooldown@example.com')
            ->andReturn(false);

        $result = $this->service->forgotPassword(
            new EmailOnlyDto(email: 'cooldown@example.com')
        );

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('如果此 Email 已註冊，我們將發送重設密碼驗證碼', $result->message);
    }

    #[Test]
    public function ResetPasswordShouldUpdatePassword(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'oldpassword',
        ]);

        $this->verificationCodeService
            ->shouldReceive('isLockedOut')
            ->once()
            ->with('password_reset', 'reset@example.com')
            ->andReturn(false);

        $this->verificationCodeService
            ->shouldReceive('verify')
            ->once()
            ->with('password_reset', 'reset@example.com', '123456')
            ->andReturn(true);

        $result = $this->service->resetPassword(
            new ResetPasswordDto(
                email: 'reset@example.com',
                code: '123456',
                password: 'newpassword123'
            )
        );

        $user->refresh();

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    #[Test]
    public function VerifyEmailShouldReturnLockedOutWhenAttemptsExceeded(): void
    {
        $this->verificationCodeService
            ->shouldReceive('isLockedOut')
            ->once()
            ->with('email_verification', 'locked@example.com')
            ->andReturn(true);

        $result = $this->service->verifyEmail(
            new VerifyEmailDto(email: 'locked@example.com', code: '123456')
        );

        $this->assertFalse($result->success);
        $this->assertSame(429, $result->statusCode);
    }

    #[Test]
    public function VerifyEmailShouldReturnRemainingAttemptsWhenCodeInvalid(): void
    {
        $this->verificationCodeService
            ->shouldReceive('isLockedOut')
            ->once()
            ->with('email_verification', 'invalid@example.com')
            ->andReturn(false);

        $this->verificationCodeService
            ->shouldReceive('verify')
            ->once()
            ->with('email_verification', 'invalid@example.com', '999999')
            ->andReturn(false);

        $this->verificationCodeService
            ->shouldReceive('getRemainingAttempts')
            ->once()
            ->with('email_verification', 'invalid@example.com')
            ->andReturn(3);

        $result = $this->service->verifyEmail(
            new VerifyEmailDto(email: 'invalid@example.com', code: '999999')
        );

        $this->assertFalse($result->success);
        $this->assertSame(422, $result->statusCode);
        $this->assertStringContainsString('剩餘 3 次', (string) $result->error);
    }

    #[Test]
    public function VerifyEmailShouldReturnNotFoundWhenUserMissingAfterValidCode(): void
    {
        $this->verificationCodeService
            ->shouldReceive('isLockedOut')
            ->once()
            ->with('email_verification', 'missing@example.com')
            ->andReturn(false);

        $this->verificationCodeService
            ->shouldReceive('verify')
            ->once()
            ->with('email_verification', 'missing@example.com', '123456')
            ->andReturn(true);

        $result = $this->service->verifyEmail(
            new VerifyEmailDto(email: 'missing@example.com', code: '123456')
        );

        $this->assertFalse($result->success);
        $this->assertSame(404, $result->statusCode);
        $this->assertSame('找不到此用戶', $result->error);
    }

    #[Test]
    public function VerifyEmailShouldMarkUserAsVerifiedWhenCodeValid(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'verify@example.com']);

        $this->verificationCodeService
            ->shouldReceive('isLockedOut')
            ->once()
            ->with('email_verification', 'verify@example.com')
            ->andReturn(false);

        $this->verificationCodeService
            ->shouldReceive('verify')
            ->once()
            ->with('email_verification', 'verify@example.com', '123456')
            ->andReturn(true);

        $result = $this->service->verifyEmail(
            new VerifyEmailDto(email: 'verify@example.com', code: '123456')
        );

        $user->refresh();
        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertNotNull($user->email_verified_at);
    }

    #[Test]
    public function ResendVerificationShouldReturnSuccessWhenUserMissing(): void
    {
        $result = $this->service->resendVerification(new EmailOnlyDto(email: 'missing@example.com'));

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
    }

    #[Test]
    public function ResendVerificationShouldReturnGenericSuccessWhenUserAlreadyVerified(): void
    {
        User::factory()->create(['email' => 'verified@example.com']);

        $result = $this->service->resendVerification(new EmailOnlyDto(email: 'verified@example.com'));

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('如果此 Email 已註冊，我們將發送驗證碼', $result->message);
    }

    #[Test]
    public function ResendVerificationShouldReturnGenericSuccessWhenCannotResend(): void
    {
        User::factory()->unverified()->create(['email' => 'cooldown-verify@example.com']);

        $this->verificationCodeService
            ->shouldReceive('canResend')
            ->once()
            ->with('email_verification', 'cooldown-verify@example.com')
            ->andReturn(false);

        $result = $this->service->resendVerification(new EmailOnlyDto(email: 'cooldown-verify@example.com'));

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('如果此 Email 已註冊，我們將發送驗證碼', $result->message);
    }

    #[Test]
    public function ResendVerificationShouldGenerateAndSendCodeWhenAllowed(): void
    {
        User::factory()->unverified()->create(['email' => 'resend@example.com']);

        $this->verificationCodeService
            ->shouldReceive('canResend')
            ->once()
            ->with('email_verification', 'resend@example.com')
            ->andReturn(true);

        $this->verificationCodeService
            ->shouldReceive('generate')
            ->once()
            ->with('email_verification', 'resend@example.com')
            ->andReturn('123456');

        $this->mailService
            ->shouldReceive('sendVerificationCode')
            ->once()
            ->with('resend@example.com', '123456', 'register')
            ->andReturn(true);

        $result = $this->service->resendVerification(new EmailOnlyDto(email: 'resend@example.com'));

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('如果此 Email 已註冊，我們將發送驗證碼', $result->message);
    }

    #[Test]
    public function ResendVerificationShouldStillSucceedWhenMailSendFails(): void
    {
        User::factory()->unverified()->create(['email' => 'resend-fail@example.com']);

        $this->verificationCodeService
            ->shouldReceive('canResend')
            ->once()
            ->with('email_verification', 'resend-fail@example.com')
            ->andReturn(true);

        $this->verificationCodeService
            ->shouldReceive('generate')
            ->once()
            ->with('email_verification', 'resend-fail@example.com')
            ->andReturn('777777');

        $this->mailService
            ->shouldReceive('sendVerificationCode')
            ->once()
            ->with('resend-fail@example.com', '777777', 'register')
            ->andReturn(false);

        $result = $this->service->resendVerification(new EmailOnlyDto(email: 'resend-fail@example.com'));

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('如果此 Email 已註冊，我們將發送驗證碼', $result->message);
    }

    #[Test]
    public function LoginShouldReturnUnauthorizedWhenCredentialsInvalid(): void
    {
        User::factory()->create([
            'email' => 'valid@example.com',
            'password' => 'correctpassword',
        ]);

        $result = $this->service->login(
            new LoginDto(email: 'valid@example.com', password: 'wrongpassword')
        );

        $this->assertFalse($result->success);
        $this->assertSame(401, $result->statusCode);
    }

    #[Test]
    public function ForgotPasswordShouldReturnSuccessWhenUserMissing(): void
    {
        $result = $this->service->forgotPassword(new EmailOnlyDto(email: 'missing@example.com'));

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
    }

    #[Test]
    public function ForgotPasswordShouldGenerateCodeWhenAllowed(): void
    {
        User::factory()->create(['email' => 'forgot@example.com']);

        $this->verificationCodeService
            ->shouldReceive('canResend')
            ->once()
            ->with('password_reset', 'forgot@example.com')
            ->andReturn(true);

        $this->verificationCodeService
            ->shouldReceive('generate')
            ->once()
            ->with('password_reset', 'forgot@example.com')
            ->andReturn('123456');

        $this->mailService
            ->shouldReceive('sendVerificationCode')
            ->once()
            ->with('forgot@example.com', '123456', 'reset_password')
            ->andReturn(true);

        $result = $this->service->forgotPassword(new EmailOnlyDto(email: 'forgot@example.com'));

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('如果此 Email 已註冊，我們將發送重設密碼驗證碼', $result->message);
    }

    #[Test]
    public function ForgotPasswordShouldStillSucceedWhenMailSendFails(): void
    {
        User::factory()->create(['email' => 'forgot-fail@example.com']);

        $this->verificationCodeService
            ->shouldReceive('canResend')
            ->once()
            ->with('password_reset', 'forgot-fail@example.com')
            ->andReturn(true);

        $this->verificationCodeService
            ->shouldReceive('generate')
            ->once()
            ->with('password_reset', 'forgot-fail@example.com')
            ->andReturn('888888');

        $this->mailService
            ->shouldReceive('sendVerificationCode')
            ->once()
            ->with('forgot-fail@example.com', '888888', 'reset_password')
            ->andReturn(false);

        $result = $this->service->forgotPassword(new EmailOnlyDto(email: 'forgot-fail@example.com'));

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('如果此 Email 已註冊，我們將發送重設密碼驗證碼', $result->message);
    }

    #[Test]
    public function ResetPasswordShouldReturnLockedOutWhenAttemptsExceeded(): void
    {
        $this->verificationCodeService
            ->shouldReceive('isLockedOut')
            ->once()
            ->with('password_reset', 'locked-reset@example.com')
            ->andReturn(true);

        $result = $this->service->resetPassword(
            new ResetPasswordDto(
                email: 'locked-reset@example.com',
                code: '123456',
                password: 'newpassword123'
            )
        );

        $this->assertFalse($result->success);
        $this->assertSame(429, $result->statusCode);
    }

    #[Test]
    public function ResetPasswordShouldReturnRemainingAttemptsWhenCodeInvalid(): void
    {
        $this->verificationCodeService
            ->shouldReceive('isLockedOut')
            ->once()
            ->with('password_reset', 'invalid-reset@example.com')
            ->andReturn(false);

        $this->verificationCodeService
            ->shouldReceive('verify')
            ->once()
            ->with('password_reset', 'invalid-reset@example.com', '000000')
            ->andReturn(false);

        $this->verificationCodeService
            ->shouldReceive('getRemainingAttempts')
            ->once()
            ->with('password_reset', 'invalid-reset@example.com')
            ->andReturn(2);

        $result = $this->service->resetPassword(
            new ResetPasswordDto(
                email: 'invalid-reset@example.com',
                code: '000000',
                password: 'newpassword123'
            )
        );

        $this->assertFalse($result->success);
        $this->assertSame(422, $result->statusCode);
        $this->assertStringContainsString('剩餘 2 次', (string) $result->error);
    }

    #[Test]
    public function ResetPasswordShouldReturnNotFoundWhenUserMissingAfterCodeVerified(): void
    {
        $this->verificationCodeService
            ->shouldReceive('isLockedOut')
            ->once()
            ->with('password_reset', 'missing-reset@example.com')
            ->andReturn(false);

        $this->verificationCodeService
            ->shouldReceive('verify')
            ->once()
            ->with('password_reset', 'missing-reset@example.com', '123456')
            ->andReturn(true);

        $result = $this->service->resetPassword(
            new ResetPasswordDto(
                email: 'missing-reset@example.com',
                code: '123456',
                password: 'newpassword123'
            )
        );

        $this->assertFalse($result->success);
        $this->assertSame(404, $result->statusCode);
    }

    #[Test]
    public function UpdatePasswordShouldPersistNewPassword(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $result = $this->service->updatePassword(
            $user,
            new UpdatePasswordDto(password: 'updatedpassword123')
        );

        $user->refresh();
        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertTrue(Hash::check('updatedpassword123', $user->password));
    }

    #[Test]
    public function UserShouldReturnResourcePayload(): void
    {
        $user = User::factory()->create(['email' => 'profile@example.com']);

        $result = $this->service->user($user);

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
        $this->assertSame('profile@example.com', $result->data['email']);
    }

    #[Test]
    public function LogoutShouldReturnSuccessPayload(): void
    {
        $result = $this->service->logout();

        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
    }
}
