<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $code,
        public string $type = 'register' // register | reset_password
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'register' => '您的驗證碼 - Ledger of Decisions',
            'reset_password' => '密碼重設驗證碼 - Ledger of Decisions',
            default => '驗證碼 - Ledger of Decisions',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
