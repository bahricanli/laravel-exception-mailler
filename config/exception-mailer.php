<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;

$csv = static function (?string $value): array {
    return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
};

return [
    'enabled' => env('EXCEPTION_MAILER_ENABLED', true),

    'mailer' => env('EXCEPTION_MAILER_MAILER'),

    'to' => $csv(env('EXCEPTION_MAILER_TO')),
    'cc' => $csv(env('EXCEPTION_MAILER_CC')),
    'bcc' => $csv(env('EXCEPTION_MAILER_BCC')),

    'from' => [
        'address' => env('EXCEPTION_MAILER_FROM_ADDRESS'),
        'name' => env('EXCEPTION_MAILER_FROM_NAME'),
    ],

    'subject' => env('EXCEPTION_MAILER_SUBJECT', '[{{ app }}] {{ exception }}'),

    'queue' => [
        'enabled' => env('EXCEPTION_MAILER_QUEUE', false),
        'connection' => env('EXCEPTION_MAILER_QUEUE_CONNECTION'),
        'queue' => env('EXCEPTION_MAILER_QUEUE_NAME'),
    ],

    'environments' => $csv(env('EXCEPTION_MAILER_ENVIRONMENTS', 'production')),

    'report_only' => ['*'],

    'ignored_exceptions' => [
        AuthenticationException::class,
        AuthorizationException::class,
        ValidationException::class,
        ModelNotFoundException::class,
        TokenMismatchException::class,
    ],

    'ignored_http_statuses' => [
        404,
    ],

    'include_request' => true,
    'include_inputs' => true,
    'include_headers' => false,

    'mask_inputs' => [
        '*password*',
        '*token*',
        '*secret*',
        '*authorization*',
        '*cookie*',
        '*credit_card*',
        '*card_number*',
        'cvv',
        'ccv',
    ],

    'max_trace_frames' => 30,

    'log_success' => false,
    'log_failures' => true,
];
