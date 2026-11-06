<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails\Exceptions;

use AlexXVL\ProblemDetails\Contracts\ProblemDetailsExceptionInterface;
use AlexXVL\ProblemDetails\ProblemDetails;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

/**
 * Base exception that renders as an RFC 9457 Problem Details document.
 */
abstract class BaseException extends Exception implements ProblemDetailsExceptionInterface
{
    protected int $statusCode = ResponseAlias::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * Stable machine-readable error code exposed as the Problem Details `code` member.
     */
    protected ?string $errorCode = null;

    /**
     * @var array<string, array<int, string>>|null
     */
    protected ?array $errors = null;

    /**
     * @param  string|null  $message  RFC 9457 detail and standard exception message.
     * @param  int  $code  Standard exception code; not exposed as the Problem Details `code` member.
     * @param  Throwable|null  $previous
     */
    public function __construct(
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message ?? '', $code, $previous);
    }

    public function render(Request $request): JsonResponse
    {
        return $this->toProblemDetails($request)->toResponse();
    }

    public function toProblemDetails(Request $request): ProblemDetails
    {
        $detail = $this->getDetail();

        return new ProblemDetails(
            type: $this->getType(),
            title: $this->getTitle(),
            status: $this->statusCode,
            detail: $detail !== '' ? $detail : null,
            instance: $request->getRequestUri(),
            code: $this->errorCode,
            errors: $this->errors,
        );
    }

    public function getType(): string
    {
        return 'about:blank';
    }

    public function getTitle(): string
    {
        return ResponseAlias::$statusTexts[$this->statusCode] ?? 'Error';
    }

    /**
     * RFC 9457 `detail` member.
     *
     * The value comes from the standard Exception message so PHP tooling,
     * logging and the HTTP representation stay consistent.
     */
    public function getDetail(): string
    {
        return $this->getMessage();
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, array<int, string>>|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * Do not report expected business errors (4xx) as application errors.
     */
    public function report(): bool
    {
        return $this->statusCode < ResponseAlias::HTTP_INTERNAL_SERVER_ERROR;
    }
}
