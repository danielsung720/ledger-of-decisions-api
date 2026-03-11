<?php

namespace App\Console\Commands;

use App\Services\MailService;
use Illuminate\Console\Command;

class TestSendMail extends Command
{
    protected $signature = 'mail:test {email} {--type=register : Email type (register or reset_password)}';

    protected $description = 'Send a test verification code email';

    public function handle(MailService $mailService): int
    {
        $email = $this->argument('email');
        $type = $this->option('type');
        $code = $mailService->generateCode();

        $this->info('Sending test email...');
        $this->newLine();

        $this->table(['Field', 'Value'], [
            ['Email', $email],
            ['Type', $type],
            ['Code', $code],
        ]);

        $this->newLine();

        $result = $mailService->sendVerificationCode($email, $code, $type);

        if ($result) {
            $this->info('Email sent successfully! Check your inbox.');

            return Command::SUCCESS;
        }

        $this->error('Failed to send email. Check storage/logs/laravel.log for details.');

        return Command::FAILURE;
    }
}
