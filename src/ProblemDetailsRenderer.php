<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails;

use AlexXVL\ProblemDetails\Contracts\ProblemDetailsExceptionInterface;
use AlexXVL\ProblemDetails\Exceptions\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Converts framework exceptions into RFC 9457 Problem Details responses.
 */
final class ProblemDetailsRenderer
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly UrlGenerator $url,
    ) {}

    public function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if ($exception instanceof HttpResponseException || ! $this->isApiRequest($request)) {
            return null;
        }

        return $this->toProblemDetails($exception, $request)->toResponse();
    }

    private function isApiRequest(Request $request): bool
    {
        $prefix = trim((string) ($this->config->get('problem-details.api_prefix') ?? 'api'), '/');

        return $prefix === '' || $request->is($prefix, $prefix.'/*');
    }

    private function toProblemDetails(Throwable $exception, Request $request): ProblemDetails
    {
        return match (true) {
            $exception instanceof ProblemDetailsExceptionInterface => $exception->toProblemDetails($request),
            $exception instanceof LaravelValidationException => $this->validationProblem($exception, $request),
            $exception instanceof AuthenticationException => $this->authenticationProblem($request),
            $exception instanceof HttpExceptionInterface => $this->httpProblem($exception, $request),
            default => $this->serverProblem($exception, $request),
        };
    }

    private function validationProblem(
        LaravelValidationException $exception,
        Request $request
    ): ProblemDetails {
        return (new ValidationException(
            errors: $exception->errors(),
            type: $this->url->to('/problems/validation-error'),
        ))->toProblemDetails($request);
    }

    private function authenticationProblem(Request $request): ProblemDetails
    {
        return new ProblemDetails(
            type: 'about:blank',
            title: ResponseAlias::$statusTexts[ResponseAlias::HTTP_UNAUTHORIZED] ?? 'Error',
            status: ResponseAlias::HTTP_UNAUTHORIZED,
            detail: 'Authentication required.',
            instance: $request->getRequestUri(),
            code: 'UNAUTHENTICATED',
        );
    }

    private function httpProblem(HttpExceptionInterface $exception, Request $request): ProblemDetails
    {
        $status = $exception->getStatusCode();
        $detail = $exception->getMessage();
        $isDebug = (bool) $this->config->get('app.debug');

        if ($status >= ResponseAlias::HTTP_INTERNAL_SERVER_ERROR && ! $isDebug) {
            $detail = ResponseAlias::$statusTexts[$status] ?? 'Error';
        }

        // In production, hide model and route internals for framework 404 responses.
        // In debug mode, keep the original detail for easier local diagnostics.
        if ($detail === '' || ($status === ResponseAlias::HTTP_NOT_FOUND && ! $isDebug)) {
            $detail = ResponseAlias::$statusTexts[$status] ?? 'Error';
        }

        return new ProblemDetails(
            type: 'about:blank',
            title: ResponseAlias::$statusTexts[$status] ?? 'Error',
            status: $status,
            detail: $detail,
            instance: $request->getRequestUri(),
            headers: $exception->getHeaders(),
        );
    }

    private function serverProblem(Throwable $exception, Request $request): ProblemDetails
    {
        $detail = $this->config->get('app.debug')
            ? $exception->getMessage()
            : 'An unexpected error occurred.';

        return new ProblemDetails(
            type: 'about:blank',
            title: ResponseAlias::$statusTexts[ResponseAlias::HTTP_INTERNAL_SERVER_ERROR],
            status: ResponseAlias::HTTP_INTERNAL_SERVER_ERROR,
            detail: $detail !== '' ? $detail : 'An unexpected error occurred.',
            instance: $request->getRequestUri(),
        );
    }
}
