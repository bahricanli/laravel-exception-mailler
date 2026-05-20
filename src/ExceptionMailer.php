<?php

namespace BahriCanli\ExceptionMailer;

use BahriCanli\ExceptionMailer\Mail\ExceptionOccurredMail;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionMailer
{
    private bool $sending = false;

    public function __construct(
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function report(Throwable $exception, bool $force = false): bool
    {
        if ($this->sending) {
            return false;
        }

        if (! $force && ! $this->isEnabled()) {
            return false;
        }

        if (! $force && ! $this->shouldSend($exception)) {
            return false;
        }

        $to = $this->recipients('to');

        if ($to === []) {
            $this->logMissingRecipients();

            return false;
        }

        $this->sending = true;

        try {
            $mailable = new ExceptionOccurredMail(
                subjectLine: $this->subjectLine($exception),
                exceptionData: $this->exceptionData($exception),
                requestData: $this->requestData(),
                fromConfig: $this->from()
            );

            $pendingMail = $this->pendingMail($to);

            if ($this->shouldQueue()) {
                $this->prepareQueue($mailable);
                $pendingMail->queue($mailable);
            } else {
                $pendingMail->send($mailable);
            }

            if ($this->config->get('exception-mailer.log_success', false)) {
                $this->logger->info('Exception mail sent.', [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }

            return true;
        } catch (Throwable $mailException) {
            if ($this->config->get('exception-mailer.log_failures', true)) {
                $this->logger->error('Exception mail could not be sent.', [
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                    'mail_exception' => $mailException,
                ]);
            }

            return false;
        } finally {
            $this->sending = false;
        }
    }

    public function shouldSend(Throwable $exception): bool
    {
        if ($exception instanceof ShouldntReport) {
            return false;
        }

        if (! $this->environmentAllowed()) {
            return false;
        }

        if ($this->matchesException($exception, $this->arrayConfig('ignored_exceptions'))) {
            return false;
        }

        if ($this->hasIgnoredHttpStatus($exception)) {
            return false;
        }

        $reportOnly = $this->arrayConfig('report_only');

        return $reportOnly === []
            || in_array('*', $reportOnly, true)
            || $this->matchesException($exception, $reportOnly);
    }

    private function isEnabled(): bool
    {
        return (bool) $this->config->get('exception-mailer.enabled', true);
    }

    private function environmentAllowed(): bool
    {
        $environments = $this->arrayConfig('environments');

        if ($environments === [] || in_array('*', $environments, true)) {
            return true;
        }

        $currentEnvironment = (string) app()->environment();

        foreach ($environments as $environment) {
            if (Str::is($environment, $currentEnvironment)) {
                return true;
            }
        }

        return false;
    }

    private function hasIgnoredHttpStatus(Throwable $exception): bool
    {
        if (! $exception instanceof HttpExceptionInterface) {
            return false;
        }

        return in_array((string) $exception->getStatusCode(), $this->arrayConfig('ignored_http_statuses'), true);
    }

    private function matchesException(Throwable $exception, array $classes): bool
    {
        foreach ($classes as $class) {
            if ($class === '*' || ($class !== '' && is_a($exception, $class))) {
                return true;
            }
        }

        return false;
    }

    private function exceptionData(Throwable $exception): array
    {
        $previous = $exception->getPrevious();

        return [
            'class' => $exception::class,
            'short_class' => class_basename($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->traceData($exception),
            'previous' => $previous ? [
                'class' => $previous::class,
                'message' => $previous->getMessage(),
                'file' => $previous->getFile(),
                'line' => $previous->getLine(),
            ] : null,
        ];
    }

    private function traceData(Throwable $exception): array
    {
        $frames = array_slice(
            $exception->getTrace(),
            0,
            (int) $this->config->get('exception-mailer.max_trace_frames', 30)
        );

        return array_map(function (array $frame): array {
            return [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'call' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
            ];
        }, $frames);
    }

    private function requestData(): array
    {
        if (! $this->config->get('exception-mailer.include_request', true)) {
            return [];
        }

        if (! app()->bound('request') || ! app('request') instanceof Request) {
            return $this->consoleData();
        }

        /** @var Request $request */
        $request = app('request');

        $data = [
            'type' => app()->runningInConsole() ? 'console' : 'http',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $this->routeName($request),
        ];

        if ($this->config->get('exception-mailer.include_inputs', true)) {
            $data['inputs'] = $this->maskData($this->normalizeData($request->all()));
        }

        if ($this->config->get('exception-mailer.include_headers', false)) {
            $data['headers'] = $this->maskData($this->normalizeData($request->headers->all()));
        }

        return $data;
    }

    private function consoleData(): array
    {
        return [
            'type' => 'console',
            'command' => implode(' ', $_SERVER['argv'] ?? []),
        ];
    }

    private function routeName(Request $request): ?string
    {
        $route = $request->route();

        if (! is_object($route) || ! method_exists($route, 'getName')) {
            return null;
        }

        return $route->getName();
    }

    private function normalizeData(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return [
                'name' => $value->getClientOriginalName(),
                'mime_type' => $value->getClientMimeType(),
                'size' => $value->getSize(),
            ];
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeData($item), $value);
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? (string) $value
                : get_debug_type($value);
        }

        if (is_resource($value)) {
            return get_resource_type($value);
        }

        return $value;
    }

    private function maskData(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $data[$key] = '********';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskData($value);
            }
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = Str::lower($key);

        foreach ($this->arrayConfig('mask_inputs') as $pattern) {
            $pattern = Str::lower($pattern);

            if ($key === $pattern || Str::is($pattern, $key)) {
                return true;
            }
        }

        return false;
    }

    private function subjectLine(Throwable $exception): string
    {
        return strtr((string) $this->config->get('exception-mailer.subject', '[{{ app }}] {{ exception }}'), [
            '{{ app }}' => (string) config('app.name', 'Laravel'),
            '{{ environment }}' => (string) app()->environment(),
            '{{ exception }}' => class_basename($exception),
            '{{ class }}' => $exception::class,
            '{{ message }}' => $exception->getMessage(),
        ]);
    }

    private function pendingMail(array $to): mixed
    {
        $mailer = $this->config->get('exception-mailer.mailer')
            ? Mail::mailer($this->config->get('exception-mailer.mailer'))
            : Mail::mailer();

        $pendingMail = $mailer->to($to);

        if ($cc = $this->recipients('cc')) {
            $pendingMail->cc($cc);
        }

        if ($bcc = $this->recipients('bcc')) {
            $pendingMail->bcc($bcc);
        }

        return $pendingMail;
    }

    private function prepareQueue(ExceptionOccurredMail $mailable): void
    {
        if ($connection = $this->config->get('exception-mailer.queue.connection')) {
            $mailable->onConnection($connection);
        }

        if ($queue = $this->config->get('exception-mailer.queue.queue')) {
            $mailable->onQueue($queue);
        }
    }

    private function shouldQueue(): bool
    {
        return (bool) $this->config->get('exception-mailer.queue.enabled', false);
    }

    private function recipients(string $key): array
    {
        return $this->arrayConfig($key);
    }

    private function from(): array
    {
        return [
            'address' => $this->config->get('exception-mailer.from.address'),
            'name' => $this->config->get('exception-mailer.from.name'),
        ];
    }

    private function arrayConfig(string $key): array
    {
        $value = $this->config->get("exception-mailer.{$key}", []);

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            (array) $value
        ), static fn (string $item): bool => $item !== ''));
    }

    private function logMissingRecipients(): void
    {
        if (! $this->config->get('exception-mailer.log_failures', true)) {
            return;
        }

        $this->logger->warning('Exception mail was not sent because no recipients are configured.');
    }
}
