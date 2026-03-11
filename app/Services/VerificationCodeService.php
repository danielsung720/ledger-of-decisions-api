<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VerificationCodeService
{
    private const CODE_LENGTH = 6;
    private const CODE_TTL_SECONDS = 600;
    private const COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900;

    public function generate(string $type, string $email): string
    {
        $code = $this->generateCode();

        Cache::put(
            $this->getCodeKey($type, $email),
            $code,
            now()->addSeconds(self::CODE_TTL_SECONDS)
        );

        Cache::put(
            $this->getSentAtKey($type, $email),
            now()->timestamp,
            now()->addSeconds(self::COOLDOWN_SECONDS)
        );

        return $code;
    }

    public function verify(string $type, string $email, string $code): bool
    {
        if ($this->isLockedOut($type, $email)) {
            return false;
        }

        $storedCode = Cache::get($this->getCodeKey($type, $email));

        if ($storedCode === null || $storedCode !== $code) {
            $this->incrementAttempts($type, $email);
            return false;
        }

        $this->clearCode($type, $email);
        return true;
    }

    public function canResend(string $type, string $email): bool
    {
        return !Cache::has($this->getSentAtKey($type, $email));
    }

    public function getCooldownSeconds(string $type, string $email): int
    {
        $sentAt = Cache::get($this->getSentAtKey($type, $email));

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
        Cache::forget($this->getCodeKey($type, $email));
        Cache::forget($this->getAttemptsKey($type, $email));
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function getCodeKey(string $type, string $email): string
    {
        return $this->buildKey($type, $email, 'code');
    }

    private function getSentAtKey(string $type, string $email): string
    {
        return $this->buildKey($type, $email, 'sent_at');
    }

    private function getAttemptsKey(string $type, string $email): string
    {
        return $this->buildKey($type, $email, 'attempts');
    }

    private function buildKey(string $type, string $email, string $suffix): string
    {
        $app = Str::slug((string) config('app.name', 'ledger'));
        $env = strtolower((string) config('app.env', 'local'));
        $normalizedEmail = strtolower(trim($email));
        $emailHash = sha1($normalizedEmail);

        return "{$app}:{$env}:verification:{$type}:{$emailHash}:{$suffix}";
    }

    private function getAttempts(string $type, string $email): int
    {
        return (int) Cache::get($this->getAttemptsKey($type, $email), 0);
    }

    private function incrementAttempts(string $type, string $email): void
    {
        $attempts = $this->getAttempts($type, $email) + 1;

        Cache::put(
            $this->getAttemptsKey($type, $email),
            $attempts,
            now()->addSeconds(self::LOCKOUT_SECONDS)
        );
    }
}
