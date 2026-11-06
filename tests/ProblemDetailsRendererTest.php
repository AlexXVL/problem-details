<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails\Tests;

use AlexXVL\ProblemDetails\ProblemDetailsRenderer;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProblemDetailsRendererTest extends TestCase
{
    private ProblemDetailsRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app()['config']->set('app.debug', true);
        $this->app()['config']->set('problem-details.api_prefix', 'api');

        $this->renderer = $this->app()->make(ProblemDetailsRenderer::class);
    }

    public function test_renders_framework_http_exception(): void
    {
        $response = $this->renderer->render(
            new NotFoundHttpException,
            Request::create('/api/v1/test'),
        );

        self::assertNotNull($response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
        self::assertSame([
            'type' => 'about:blank',
            'title' => 'Not Found',
            'status' => 404,
            'detail' => 'Not Found',
            'instance' => '/api/v1/test',
        ], $response->getData(true));
    }

    public function test_preserves_404_detail_in_debug_mode(): void
    {
        $this->app()['config']->set('app.debug', true);

        $response = $this->renderer->render(
            new HttpException(404, 'User 123 was not found.'),
            Request::create('/api/v1/test'),
        );

        self::assertNotNull($response);
        self::assertSame('User 123 was not found.', $response->getData(true)['detail']);
    }

    public function test_hides_404_detail_in_production(): void
    {
        $this->app()['config']->set('app.debug', false);

        $response = $this->renderer->render(
            new HttpException(404, 'User 123 was not found.'),
            Request::create('/api/v1/test'),
        );

        self::assertNotNull($response);
        self::assertSame('Not Found', $response->getData(true)['detail']);
    }

    public function test_hides_5xx_detail_in_production(): void
    {
        $this->app()['config']->set('app.debug', false);

        $response = $this->renderer->render(
            new RuntimeException('Internal database details.'),
            Request::create('/api/v1/test'),
        );

        self::assertNotNull($response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('An unexpected error occurred.', $response->getData(true)['detail']);
    }

    public function test_exposes_5xx_detail_in_debug_mode(): void
    {
        $this->app()['config']->set('app.debug', true);

        $response = $this->renderer->render(
            new RuntimeException('Internal database details.'),
            Request::create('/api/v1/test'),
        );

        self::assertNotNull($response);
        self::assertSame(500, $response->getStatusCode());
        self::assertSame('Internal database details.', $response->getData(true)['detail']);
    }

    public function test_converts_laravel_validation_exception(): void
    {
        $response = $this->renderer->render(
            LaravelValidationException::withMessages([
                'email' => ['The email field is required.'],
            ]),
            Request::create('/api/v1/users'),
        );

        self::assertNotNull($response);
        self::assertSame(422, $response->getStatusCode());
        self::assertSame([
            'type' => $this->app()['url']->to('/problems/validation-error'),
            'title' => 'Validation Error',
            'status' => 422,
            'detail' => 'Validation failed',
            'instance' => '/api/v1/users',
            'code' => 'VALIDATION_ERROR',
            'errors' => [
                'email' => ['The email field is required.'],
            ],
        ], $response->getData(true));
    }

    public function test_renders_authentication_exception(): void
    {
        $response = $this->renderer->render(
            new AuthenticationException,
            Request::create('/api/v1/test'),
        );

        self::assertNotNull($response);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame('UNAUTHENTICATED', $response->getData(true)['code']);
    }

    public function test_preserves_http_exception_headers(): void
    {
        $response = $this->renderer->render(
            new HttpException(429, 'Too many attempts.', null, ['Retry-After' => '60']),
            Request::create('/api/v1/test'),
        );

        self::assertNotNull($response);
        self::assertSame(429, $response->getStatusCode());
        self::assertSame('60', $response->headers->get('Retry-After'));
    }

    public function test_ignores_non_api_requests(): void
    {
        self::assertNull(
            $this->renderer->render(new NotFoundHttpException, Request::create('/web/test')),
        );
    }

    public function test_renders_exact_api_prefix(): void
    {
        $response = $this->renderer->render(
            new NotFoundHttpException,
            Request::create('/api'),
        );

        self::assertNotNull($response);
        self::assertSame(404, $response->getStatusCode());
    }

    public function test_uses_configured_api_prefix(): void
    {
        $this->app()['config']->set('problem-details.api_prefix', 'v1');

        self::assertNotNull(
            $this->renderer->render(new NotFoundHttpException, Request::create('/v1/users')),
        );
        self::assertNull(
            $this->renderer->render(new NotFoundHttpException, Request::create('/api/users')),
        );
    }

    public function test_empty_api_prefix_matches_all_requests(): void
    {
        $this->app()['config']->set('problem-details.api_prefix', '');

        self::assertNotNull(
            $this->renderer->render(new NotFoundHttpException, Request::create('/web/test')),
        );
    }

    public function test_ignores_http_response_exceptions(): void
    {
        self::assertNull(
            $this->renderer->render(
                new HttpResponseException(new Response('', 204)),
                Request::create('/api/v1/test'),
            ),
        );
    }
}
