<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserPreferencesService
{
    private const DEFAULT_THEME = 'default';
    private const VALID_THEMES = ['default', 'code', 'ocean'];

    /**
     * 取得使用者偏好設定
     */
    public function get(int $userId): array
    {
        try {
            $cached = Cache::get($this->buildKey($userId));

            if ($cached === null) {
                return $this->getDefaultPreferences();
            }

            $preferences = json_decode($cached, true);

            if (!is_array($preferences)) {
                return $this->getDefaultPreferences();
            }

            return $preferences;
        } catch (\Throwable $e) {
            Log::warning('Failed to get user preferences from Redis', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $this->getDefaultPreferences();
        }
    }

    /**
     * 更新使用者偏好設定
     */
    public function update(int $userId, array $preferences): array
    {
        $current = $this->get($userId);
        $merged = array_merge($current, $preferences);

        try {
            Cache::forever(
                $this->buildKey($userId),
                json_encode($merged, JSON_THROW_ON_ERROR)
            );
        } catch (\Throwable $e) {
            Log::error('Failed to save user preferences to Redis', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            // 靜默失敗，回傳合併後的偏好設定
        }

        return $merged;
    }

    /**
     * 取得使用者主題
     */
    public function getTheme(int $userId): string
    {
        $preferences = $this->get($userId);

        return $preferences['ui_theme'] ?? self::DEFAULT_THEME;
    }

    /**
     * 設定使用者主題
     */
    public function setTheme(int $userId, string $theme): void
    {
        if (!$this->isValidTheme($theme)) {
            throw new \InvalidArgumentException("Invalid theme: {$theme}");
        }

        $this->update($userId, ['ui_theme' => $theme]);
    }

    /**
     * 驗證主題是否有效
     */
    public function isValidTheme(string $theme): bool
    {
        return in_array($theme, self::VALID_THEMES, true);
    }

    /**
     * 取得有效的主題列表
     */
    public function getValidThemes(): array
    {
        return self::VALID_THEMES;
    }

    /**
     * 取得預設偏好設定
     */
    private function getDefaultPreferences(): array
    {
        return [
            'ui_theme' => self::DEFAULT_THEME,
        ];
    }

    /**
     * 建立 Redis key
     */
    private function buildKey(int $userId): string
    {
        $app = Str::slug((string) config('app.name', 'ledger'));
        $env = strtolower((string) config('app.env', 'local'));

        return "{$app}:{$env}:user_preferences:{$userId}";
    }
}
