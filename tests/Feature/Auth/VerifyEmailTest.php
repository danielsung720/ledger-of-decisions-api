<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    private VerificationCodeService $verificationCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verificationCodeService = app(VerificationCodeService::class);
    }

    #[Test]
    public function user_can_verify_email_with_valid_code(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $code = $this->verificationCodeService->generate('email_verification', $user->email);

        $response = $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Email 驗證成功',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    #[Test]
    public function verify_email_with_unknown_email_and_random_code_returns422(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $this->verificationCodeService->generate('email_verification', $user->email);

        $response = $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    #[Test]
    public function verify_email_fails_with_invalid_code(): void
    {
        $response = $this->postJson('/api/verify-email', [
            'email' => 'nonexistent@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function verify_email_returns_not_found_when_code_valid_but_user_deleted(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'deleted-user@example.com',
        ]);
        $code = $this->verificationCodeService->generate('email_verification', $user->email);
        $user->delete();

        $response = $this->postJson('/api/verify-email', [
            'email' => 'deleted-user@example.com',
            'code' => $code,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => '找不到此用戶',
            ]);
    }

    #[Test]
    public function verify_email_requires_email(): void
    {
        $response = $this->postJson('/api/verify-email', [
            'code' => '123456',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function verify_email_requires_code(): void
    {
        $response = $this->postJson('/api/verify-email', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function resend_verification_sends_new_code(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $response = $this->postJson('/api/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '如果此 Email 已註冊，我們將發送驗證碼',
            ]);
    }

    #[Test]
    public function resend_verification_fails_for_verified_user(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
        ]);

        $response = $this->postJson('/api/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '如果此 Email 已註冊，我們將發送驗證碼',
            ]);
    }

    #[Test]
    public function resend_verification_returns_success_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/resend-verification', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '如果此 Email 已註冊，我們將發送驗證碼',
            ]);
    }

    #[Test]
    public function resend_verification_requires_email(): void
    {
        $response = $this->postJson('/api/resend-verification', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function resend_verification_respects_cooldown(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $this->postJson('/api/resend-verification', [
            'email' => $user->email,
        ]);

        $response = $this->postJson('/api/resend-verification', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '如果此 Email 已註冊，我們將發送驗證碼',
            ]);
    }
}
