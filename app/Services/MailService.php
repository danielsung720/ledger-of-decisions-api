<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    /**
     * 發送驗證碼郵件
     */
    public function sendVerificationCode(string $email, string $code, string $type = 'register'): bool
    {
        try {
            Mail::to($email)->send(new VerificationCodeMail($code, $type));

            Log::info('Verification code email sent', [
                'email' => $this->maskEmail($email),
                'type' => $type,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send verification code email', [
                'email' => $this->maskEmail($email),
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 生成 6 位數驗證碼
     */
    public function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 遮罩 Email（用於日誌，保護隱私）
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));

        return $maskedName . '@' . $domain;
    }
}
