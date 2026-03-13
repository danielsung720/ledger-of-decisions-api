<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\RedisHelper;

class VerificationCodeService
{
    private const CODE_LENGTH = 6;
    private const CODE_TTL_SECONDS = 600;
    private const COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900;

    public function __construct(
        private readonly RedisHelper $redisHelper
    ) {
    }

    public function generate(string $type, string $email): string
    {
        $code = $this->generateCode();
        $codeKeys = $this->getCodeKeys($type, $email);
        $sentAtKeys = $this->getSentAtKeys($type, $email);

        foreach ($codeKeys as $key) {
            $this->redisHelper->put($key, $code, now()->addSeconds(self::CODE_TTL_SECONDS));
        }
        foreach ($sentAtKeys as $key) {
            $this->redisHelper->put($key, now()->timestamp, now()->addSeconds(self::COOLDOWN_SECONDS));
        }

        return $code;
    }

    public function verify(string $type, string $email, string $code): bool
    {
        if ($this->isLockedOut($type, $email)) {
            return false;
        }

        $storedCode = $this->getFromKeys($this->getCodeKeys($type, $email));

        if ($storedCode === null || $storedCode !== $code) {
            $this->incrementAttempts($type, $email);

            return false;
        }

        $this->clearCode($type, $email);

        return true;
    }

    public function canResend(string $type, string $email): bool
    {
        foreach ($this->getSentAtKeys($type, $email) as $key) {
            if ($this->redisHelper->has($key)) {
                return false;
            }
        }

        return true;
    }

    public function getCooldownSeconds(string $type, string $email): int
    {
        $sentAt = $this->getFromKeys($this->getSentAtKeys($type, $email));

        if ($sentAt === null) {
            return 0;
        }

        $elapsed = now()->timestamp - $sentAt;
        $remaining = self::COOLDOWN_SECONDS - $elapsed;

        return max(0, $remaining);
    }

    public function isLockedOut(string $type, string $email): bool
    {
        $attempts = $this->getAttempts($type, $email);

        return $attempts >= self::MAX_ATTEMPTS;
    }

    public function getRemainingAttempts(string $type, string $email): int
    {
        $attempts = $this->getAttempts($type, $email);

        return max(0, self::MAX_ATTEMPTS - $attempts);
    }

    public function clearCode(string $type, string $email): void
    {
        foreach ($this->getCodeKeys($type, $email) as $key) {
            $this->redisHelper->forget($key);
        }
        foreach ($this->getAttemptsKeys($type, $email) as $key) {
            $this->redisHelper->forget($key);
        }
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<int, string>
     */
    private function getCodeKeys(string $type, string $email): array
    {
        return $this->buildKeys($type, $email, 'Code', 'code');
    }

    /**
     * @return array<int, string>
     */
    private function getSentAtKeys(string $type, string $email): array
    {
        return $this->buildKeys($type, $email, 'SentAt', 'sent_at');
    }

    /**
     * @return array<int, string>
     */
    private function getAttemptsKeys(string $type, string $email): array
    {
        return $this->buildKeys($type, $email, 'Attempts', 'attempts');
    }

    /**
     * @return array<int, string>
     */
    private function buildKeys(string $type, string $email, string $suffix, string $legacySuffix): array
    {
        $normalizedEmail = strtolower(trim($email));
        $emailHash = sha1($normalizedEmail);

        return [
            $this->redisHelper->buildVerificationKey($type, $emailHash, $suffix),
            $this->redisHelper->buildLegacyVerificationKey($type, $emailHash, $legacySuffix),
        ];
    }

    private function getAttempts(string $type, string $email): int
    {
        return (int) $this->getFromKeys($this->getAttemptsKeys($type, $email), 0);
    }

    private function incrementAttempts(string $type, string $email): void
    {
        $attempts = $this->getAttempts($type, $email) + 1;

        foreach ($this->getAttemptsKeys($type, $email) as $key) {
            $this->redisHelper->put($key, $attempts, now()->addSeconds(self::LOCKOUT_SECONDS));
        }
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function getFromKeys(array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            $value = $this->redisHelper->get($key);

            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }
}
