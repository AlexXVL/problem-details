<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Throwable;

final class ProblemDetailsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/problem-details.php', 'problem-details');

        $this->app->singleton(ProblemDetailsRenderer::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/problem-details.php' => $this->app->configPath('problem-details.php'),
        ], 'problem-details-config');

        $this->registerRenderer($this->app->make(ExceptionHandler::class));
    }

    private function registerRenderer(object $handler): void
    {
        if (! method_exists($handler, 'renderable')) {
            return;
        }

        $handler->renderable(function (Throwable $exception, Request $request): ?JsonResponse {
            return $this->app->make(ProblemDetailsRenderer::class)->render($exception, $request);
        });
    }
}
