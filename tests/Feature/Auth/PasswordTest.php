<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private VerificationCodeService $verificationCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verificationCodeService = app(VerificationCodeService::class);
    }

    #[Test]
    public function forgot_password_sends_reset_code(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '如果此 Email 已註冊，我們將發送重設密碼驗證碼',
            ]);
    }

    #[Test]
    public function forgot_password_returns_success_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '如果此 Email 已註冊，我們將發送重設密碼驗證碼',
            ]);
    }

    #[Test]
    public function forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/forgot-password', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function forgot_password_respects_cooldown(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '如果此 Email 已註冊，我們將發送重設密碼驗證碼',
            ]);
    }

    #[Test]
    public function reset_password_with_valid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'oldpassword',
        ]);

        $code = $this->verificationCodeService->generate('password_reset', $user->email);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '密碼重設成功，請重新登入',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    #[Test]
    public function reset_password_fails_with_invalid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $this->verificationCodeService->generate('password_reset', $user->email);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '000000',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function reset_password_revokes_all_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $user->createToken('test_token');
        $this->assertEquals(1, $user->tokens()->count());

        $code = $this->verificationCodeService->generate('password_reset', $user->email);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $this->assertEquals(0, $user->tokens()->count());
    }

    #[Test]
    public function reset_password_requires_email(): void
    {
        $response = $this->postJson('/api/reset-password', [
            'code' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function reset_password_requires_code(): void
    {
        $response = $this->postJson('/api/reset-password', [
            'email' => 'user@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function reset_password_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $code = $this->verificationCodeService->generate('password_reset', $user->email);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function reset_password_returns_not_found_when_code_valid_but_user_deleted(): void
    {
        $user = User::factory()->create([
            'email' => 'deleted-reset@example.com',
        ]);

        $code = $this->verificationCodeService->generate('password_reset', $user->email);
        $user->delete();

        $response = $this->postJson('/api/reset-password', [
            'email' => 'deleted-reset@example.com',
            'code' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => '找不到此用戶',
            ]);
    }

    #[Test]
    public function update_password_requires_authentication(): void
    {
        $response = $this->putJson('/api/user/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $response = $this->actingAs($user)->putJson('/api/user/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '密碼更新成功',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    #[Test]
    public function update_password_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $response = $this->actingAs($user)->putJson('/api/user/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function update_password_requires_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/user/password', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function update_password_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => 'oldpassword',
        ]);

        $response = $this->actingAs($user)->putJson('/api/user/password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    }
}
