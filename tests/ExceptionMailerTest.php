<?php

namespace BahriCanli\ExceptionMailer\Tests;

use BahriCanli\ExceptionMailer\ExceptionMailer;
use BahriCanli\ExceptionMailer\Mail\ExceptionOccurredMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ExceptionMailerTest extends TestCase
{
    public function test_it_sends_exception_report_mail(): void
    {
        config(['exception-mailer.to' => ['dev@example.com']]);

        Mail::fake();

        $sent = app(ExceptionMailer::class)->report(new RuntimeException('Boom'), true);

        $this->assertTrue($sent);

        Mail::assertSent(ExceptionOccurredMail::class, function (ExceptionOccurredMail $mail): bool {
            return $mail->hasTo('dev@example.com');
        });
    }

    public function test_it_masks_sensitive_request_inputs(): void
    {
        config(['exception-mailer.to' => ['dev@example.com']]);

        $this->app->instance('request', Request::create('/login', 'POST', [
            'email' => 'hello@example.com',
            'password' => 'secret',
            'nested' => [
                'api_token' => 'token-value',
            ],
        ]));

        Mail::fake();

        app(ExceptionMailer::class)->report(new RuntimeException('Boom'), true);

        Mail::assertSent(ExceptionOccurredMail::class, function (ExceptionOccurredMail $mail): bool {
            return data_get($mail->requestData, 'inputs.email') === 'hello@example.com'
                && data_get($mail->requestData, 'inputs.password') === '********'
                && data_get($mail->requestData, 'inputs.nested.api_token') === '********';
        });
    }
}
