<?php

namespace BahriCanli\ExceptionMailer\Commands;

use BahriCanli\ExceptionMailer\ExceptionMailer;
use Illuminate\Console\Command;
use RuntimeException;

class TestExceptionMailerCommand extends Command
{
    protected $signature = 'exception-mailer:test {--force : Send even if disabled or environment filters would skip the exception}';

    protected $description = 'Send a test exception email.';

    public function handle(ExceptionMailer $mailer): int
    {
        $sent = $mailer->report(
            new RuntimeException('This is a test exception from BahriCanli ExceptionMailer.'),
            (bool) $this->option('force')
        );

        if (! $sent) {
            $this->warn('No email was sent. Check EXCEPTION_MAILER_TO, environment filters, and mail configuration.');

            return self::FAILURE;
        }

        $this->info('Test exception email sent.');

        return self::SUCCESS;
    }
}
