<?php

namespace BahriCanli\ExceptionMailer;

use BahriCanli\ExceptionMailer\Commands\TestExceptionMailerCommand;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Throwable;

class ExceptionMailerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/exception-mailer.php', 'exception-mailer');

        $this->app->singleton(ExceptionMailer::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'exception-mailer');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/exception-mailer.php' => config_path('exception-mailer.php'),
            ], 'exception-mailer-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/exception-mailer'),
            ], 'exception-mailer-views');

            $this->commands([
                TestExceptionMailerCommand::class,
            ]);
        }

        $this->app->booted(function (): void {
            $this->registerReportableCallback();
        });
    }

    private function registerReportableCallback(): void
    {
        if (! $this->app->bound(ExceptionHandler::class)) {
            return;
        }

        $handler = $this->app->make(ExceptionHandler::class);

        if (! method_exists($handler, 'reportable')) {
            return;
        }

        $handler->reportable(function (Throwable $exception): void {
            $this->app->make(ExceptionMailer::class)->report($exception);
        });
    }
}
