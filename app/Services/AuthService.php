<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Auth\AuthOperationResultDto;
use App\DTO\Auth\EmailOnlyDto;
use App\DTO\Auth\LoginDto;
use App\DTO\Auth\RegisterDto;
use App\DTO\Auth\ResetPasswordDto;
use App\DTO\Auth\UpdatePasswordDto;
use App\DTO\Auth\VerifyEmailDto;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Application service for authentication and account security workflows.
 */
class AuthService
{
    public function __construct(
        private readonly AuthRepository $authRepository,
        private readonly VerificationCodeService $verificationCodeService,
        private readonly MailService $mailService
    ) {
    }

    /**
     * Register user and send email verification code.
     */
    public function register(RegisterDto $payload): AuthOperationResultDto
    {
        $user = $this->authRepository->createUser($payload);
        $code = $this->verificationCodeService->generate('email_verification', $payload->email);
        $emailSent = $this->mailService->sendVerificationCode($payload->email, $code, 'register');

        if (! $emailSent) {
            Log::warning('Failed to send verification email', ['email' => $payload->email]);
        }

        return new AuthOperationResultDto(
            success: true,
            statusCode: 201,
            message: '註冊成功，請檢查您的信箱以驗證帳戶',
            data: (new UserResource($user))->resolve(),
        );
    }

    /**
     * Verify email by one-time verification code.
     */
    public function verifyEmail(VerifyEmailDto $payload): AuthOperationResultDto
    {
        if ($this->verificationCodeService->isLockedOut('email_verification', $payload->email)) {
            return new AuthOperationResultDto(
                success: false,
                statusCode: 429,
                error: '驗證次數過多，請 15 分鐘後再試',
            );
        }

        $verified = $this->verificationCodeService->verify('email_verification', $payload->email, $payload->code);
        if (! $verified) {
            $remaining = $this->verificationCodeService->getRemainingAttempts('email_verification', $payload->email);

            return new AuthOperationResultDto(
                success: false,
                statusCode: 422,
                error: "驗證碼不正確或已過期，剩餘 {$remaining} 次嘗試機會",
            );
        }

        $user = $this->authRepository->findUserByEmail($payload->email);
        if ($user === null) {
            return new AuthOperationResultDto(
                success: false,
                statusCode: 404,
                error: '找不到此用戶',
            );
        }

        $updated = $this->authRepository->markEmailVerified($user);

        return new AuthOperationResultDto(
            success: true,
            statusCode: 200,
            message: 'Email 驗證成功',
            data: (new UserResource($updated))->resolve(),
        );
    }

    /**
     * Resend email verification code with cooldown protection.
     */
    public function resendVerification(EmailOnlyDto $payload): AuthOperationResultDto
    {
        $genericMessage = '如果此 Email 已註冊，我們將發送驗證碼';
        $user = $this->authRepository->findUserByEmail($payload->email);
        if ($user === null) {
            return new AuthOperationResultDto(
                success: true,
                statusCode: 200,
                message: $genericMessage,
            );
        }

        if ($user->email_verified_at !== null) {
            return new AuthOperationResultDto(
                success: true,
                statusCode: 200,
                message: $genericMessage,
            );
        }

        if (! $this->verificationCodeService->canResend('email_verification', $payload->email)) {
            return new AuthOperationResultDto(
                success: true,
                statusCode: 200,
                message: $genericMessage,
            );
        }

        $code = $this->verificationCodeService->generate('email_verification', $payload->email);
        $emailSent = $this->mailService->sendVerificationCode($payload->email, $code, 'register');
        if (! $emailSent) {
            Log::warning('Failed to resend verification email', ['email' => $payload->email]);
        }

        return new AuthOperationResultDto(
            success: true,
            statusCode: 200,
            message: $genericMessage,
        );
    }

    /**
     * Authenticate user for session-based login flow.
     */
    public function login(LoginDto $payload): AuthOperationResultDto
    {
        $user = $this->authRepository->findUserByEmail($payload->email);
        if ($user === null || ! Hash::check($payload->password, $user->password)) {
            return new AuthOperationResultDto(
                success: false,
                statusCode: 401,
                error: 'Email 或密碼不正確',
            );
        }

        if ($user->email_verified_at === null) {
            return new AuthOperationResultDto(
                success: false,
                statusCode: 403,
                error: 'email_not_verified',
                data: ['email' => $user->email],
            );
        }

        return new AuthOperationResultDto(
            success: true,
            statusCode: 200,
            message: '登入成功',
            data: [
                'user' => (new UserResource($user))->resolve(),
            ],
        );
    }

    /**
     * Build logout response payload for session-based auth.
     */
    public function logout(): AuthOperationResultDto
    {
        return new AuthOperationResultDto(
            success: true,
            statusCode: 200,
            message: '登出成功',
        );
    }

    /**
     * Get authenticated user profile.
     */
    public function user(User $user): AuthOperationResultDto
    {
        return new AuthOperationResultDto(
            success: true,
            statusCode: 200,
            data: (new UserResource($user))->resolve(),
        );
    }

    /**
     * Start password reset flow by sending verification code.
     */
    public function forgotPassword(EmailOnlyDto $payload): AuthOperationResultDto
    {
        $genericMessage = '如果此 Email 已註冊，我們將發送重設密碼驗證碼';
        $user = $this->authRepository->findUserByEmail($payload->email);
        if ($user === null) {
            return new AuthOperationResultDto(
                success: true,
                statusCode: 200,
                message: $genericMessage,
            );
        }

        if (! $this->verificationCodeService->canResend('password_reset', $payload->email)) {
            return new AuthOperationResultDto(
                success: true,
                statusCode: 200,
                message: $genericMessage,
            );
        }

        $code = $this->verificationCodeService->generate('password_reset', $payload->email);
        $emailSent = $this->mailService->sendVerificationCode($payload->email, $code, 'reset_password');
        if (! $emailSent) {
            Log::warning('Failed to send password reset email', ['email' => $payload->email]);
        }

        return new AuthOperationResultDto(
            success: true,
            statusCode: 200,
            message: $genericMessage,
        );
    }

    /**
     * Reset password after verification code validation.
     */
    public function resetPassword(ResetPasswordDto $payload): AuthOperationResultDto
    {
        if ($this->verificationCodeService->isLockedOut('password_reset', $payload->email)) {
            return new AuthOperationResultDto(
                success: false,
                statusCode: 429,
                error: '驗證次數過多，請 15 分鐘後再試',
            );
        }

        $verified = $this->verificationCodeService->verify('password_reset', $payload->email, $payload->code);
        if (! $verified) {
            $remaining = $this->verificationCodeService->getRemainingAttempts('password_reset', $payload->email);

            return new AuthOperationResultDto(
                success: false,
                statusCode: 422,
                error: "驗證碼不正確或已過期，剩餘 {$remaining} 次嘗試機會",
            );
        }

        $user = $this->authRepository->findUserByEmail($payload->email);
        if ($user === null) {
            return new AuthOperationResultDto(
                success: false,
                statusCode: 404,
                error: '找不到此用戶',
            );
        }

        $this->authRepository->updatePassword($user, $payload->password);

        return new AuthOperationResultDto(
            success: true,
            statusCode: 200,
            message: '密碼重設成功，請重新登入',
        );
    }

    /**
     * Update password for authenticated user.
     */
    public function updatePassword(User $user, UpdatePasswordDto $payload): AuthOperationResultDto
    {
        $this->authRepository->updatePassword($user, $payload->password);

        return new AuthOperationResultDto(
            success: true,
            statusCode: 200,
            message: '密碼更新成功',
        );
    }
}
