<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class UserPreferencesApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        Cache::flush();
    }

    #[Test]
    public function CanGetDefaultPreferencesWhenNoneSet(): void
    {
        $response = $this->getJson('/api/user/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ui_theme' => 'default',
                ],
            ]);
    }

    #[Test]
    public function CanUpdateThemePreference(): void
    {
        $response = $this->putJson('/api/user/preferences', [
            'ui_theme' => 'code',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ui_theme' => 'code',
                ],
            ]);
    }

    #[Test]
    public function CanGetPreviouslySetTheme(): void
    {
        // 先設定主題
        $this->putJson('/api/user/preferences', [
            'ui_theme' => 'ocean',
        ]);

        // 再取得主題
        $response = $this->getJson('/api/user/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ui_theme' => 'ocean',
                ],
            ]);
    }

    #[Test]
    public function UpdateThemeValidatesThemeValue(): void
    {
        $response = $this->putJson('/api/user/preferences', [
            'ui_theme' => 'invalid-theme',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'error',
                'errors' => ['ui_theme'],
            ]);
    }

    #[Test]
    public function UpdateThemeRequiresThemeField(): void
    {
        $response = $this->putJson('/api/user/preferences', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    #[Test]
    public function GetPreferencesRequiresAuthentication(): void
    {
        // 登出
        $this->app->forgetInstance('auth');
        $this->app['auth']->forgetGuards();

        $response = $this->getJson('/api/user/preferences');

        $response->assertStatus(401);
    }

    #[Test]
    public function UpdatePreferencesRequiresAuthentication(): void
    {
        // 登出
        $this->app->forgetInstance('auth');
        $this->app['auth']->forgetGuards();

        $response = $this->putJson('/api/user/preferences', [
            'ui_theme' => 'code',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function PreferencesAreIsolatedBetweenUsers(): void
    {
        // 第一位使用者設定 code 主題
        $this->putJson('/api/user/preferences', [
            'ui_theme' => 'code',
        ]);

        // 切換到第二位使用者
        $user2 = User::factory()->create();
        $this->actingAs($user2);

        // 第二位使用者應該看到預設主題
        $response = $this->getJson('/api/user/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ui_theme' => 'default',
                ],
            ]);
    }

    #[Test]
    public function CanUpdateToOceanTheme(): void
    {
        $response = $this->putJson('/api/user/preferences', [
            'ui_theme' => 'ocean',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ui_theme' => 'ocean',
                ],
            ]);
    }

    #[Test]
    public function CanUpdateBackToDefaultTheme(): void
    {
        // 先設定為 code
        $this->putJson('/api/user/preferences', [
            'ui_theme' => 'code',
        ]);

        // 再改回 default
        $response = $this->putJson('/api/user/preferences', [
            'ui_theme' => 'default',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ui_theme' => 'default',
                ],
            ]);
    }
}
