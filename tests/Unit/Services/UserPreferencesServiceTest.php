<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\UserPreferencesService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserPreferencesServiceTest extends TestCase
{
    private UserPreferencesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserPreferencesService::class);
        Cache::flush();
    }

    #[Test]
    public function GetReturnsDefaultPreferencesWhenNoneSet(): void
    {
        $preferences = $this->service->get(999);

        $this->assertSame('default', $preferences['ui_theme']);
    }

    #[Test]
    public function UpdateStoresPreferencesAndReturnsUpdatedValue(): void
    {
        $userId = 1;

        $result = $this->service->update($userId, ['ui_theme' => 'code']);

        $this->assertSame('code', $result['ui_theme']);
    }

    #[Test]
    public function GetReturnsStoredPreferences(): void
    {
        $userId = 2;
        $this->service->update($userId, ['ui_theme' => 'ocean']);

        $preferences = $this->service->get($userId);

        $this->assertSame('ocean', $preferences['ui_theme']);
    }

    #[Test]
    public function GetThemeReturnsDefaultWhenNotSet(): void
    {
        $theme = $this->service->getTheme(999);

        $this->assertSame('default', $theme);
    }

    #[Test]
    public function GetThemeReturnsStoredTheme(): void
    {
        $userId = 3;
        $this->service->setTheme($userId, 'code');

        $theme = $this->service->getTheme($userId);

        $this->assertSame('code', $theme);
    }

    #[Test]
    public function SetThemeUpdatesTheme(): void
    {
        $userId = 4;

        $this->service->setTheme($userId, 'ocean');

        $this->assertSame('ocean', $this->service->getTheme($userId));
    }

    #[Test]
    public function SetThemeThrowsExceptionForInvalidTheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid theme: invalid');

        $this->service->setTheme(1, 'invalid');
    }

    #[Test]
    public function IsValidThemeReturnsTrueForValidThemes(): void
    {
        $this->assertTrue($this->service->isValidTheme('default'));
        $this->assertTrue($this->service->isValidTheme('code'));
        $this->assertTrue($this->service->isValidTheme('ocean'));
    }

    #[Test]
    public function IsValidThemeReturnsFalseForInvalidThemes(): void
    {
        $this->assertFalse($this->service->isValidTheme('invalid'));
        $this->assertFalse($this->service->isValidTheme('dark'));
        $this->assertFalse($this->service->isValidTheme(''));
    }

    #[Test]
    public function GetValidThemesReturnsAllValidThemes(): void
    {
        $themes = $this->service->getValidThemes();

        $this->assertSame(['default', 'code', 'ocean'], $themes);
    }

    #[Test]
    public function UpdateMergesWithExistingPreferences(): void
    {
        $userId = 5;

        // 假設未來有其他偏好設定，update 應該合併而非覆蓋
        $this->service->update($userId, ['ui_theme' => 'code']);
        $result = $this->service->update($userId, ['ui_theme' => 'ocean']);

        $this->assertSame('ocean', $result['ui_theme']);
    }

    #[Test]
    public function PreferencesAreIsolatedBetweenUsers(): void
    {
        $this->service->setTheme(1, 'code');
        $this->service->setTheme(2, 'ocean');

        $this->assertSame('code', $this->service->getTheme(1));
        $this->assertSame('ocean', $this->service->getTheme(2));
        $this->assertSame('default', $this->service->getTheme(3));
    }
}
