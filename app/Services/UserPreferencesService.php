<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\RedisHelper;
use Illuminate\Support\Facades\Log;

class UserPreferencesService
{
    private const DEFAULT_THEME = 'default';

    private const VALID_THEMES = ['default', 'code', 'ocean'];

    public function __construct(
        private readonly RedisHelper $redisHelper
    ) {
    }

    /**
     * 取得使用者偏好設定
     */
    public function get(int $userId): array
    {
        try {
            $newKey = $this->redisHelper->buildUserPreferencesKey($userId);
            $legacyKey = $this->redisHelper->buildLegacyUserPreferencesKey($userId);
            $cached = $this->redisHelper->get($newKey);

            if ($cached === null) {
                $cached = $this->redisHelper->get($legacyKey);

                if ($cached !== null) {
                    // Transition path: migrate legacy key payload to new key.
                    $this->redisHelper->forever($newKey, $cached);
                }
            }

            if ($cached === null) {
                return $this->getDefaultPreferences();
            }

            $preferences = json_decode($cached, true);

            if (! is_array($preferences)) {
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
            $encoded = json_encode($merged, JSON_THROW_ON_ERROR);
            $newKey = $this->redisHelper->buildUserPreferencesKey($userId);
            $legacyKey = $this->redisHelper->buildLegacyUserPreferencesKey($userId);

            // Transition path: keep legacy readers alive during rollout.
            $this->redisHelper->forever($newKey, $encoded);
            $this->redisHelper->forever($legacyKey, $encoded);
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
        if (! $this->isValidTheme($theme)) {
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
}
