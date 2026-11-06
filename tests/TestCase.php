<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails\Tests;

use AlexXVL\ProblemDetails\ProblemDetailsServiceProvider;
use Illuminate\Foundation\Application;
use LogicException;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ProblemDetailsServiceProvider::class];
    }

    protected function app(): Application
    {
        return $this->app ?? throw new LogicException('The application has not been initialized.');
    }
}
