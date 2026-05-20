<?php

namespace BahriCanli\ExceptionMailer\Tests;

use BahriCanli\ExceptionMailer\ExceptionMailerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ExceptionMailerServiceProvider::class,
        ];
    }
}
