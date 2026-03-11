<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\VerificationCodeMail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerificationCodeMailTest extends TestCase
{
    #[Test]
    public function EnvelopeShouldUseDefaultSubjectForUnknownType(): void
    {
        $mail = new VerificationCodeMail('123456', 'custom_type');
        $envelope = $mail->envelope();

        $this->assertSame('驗證碼 - Ledger of Decisions', $envelope->subject);
    }
}
