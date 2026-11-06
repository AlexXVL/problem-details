<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails\Exceptions;

use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

final class ValidationException extends BaseException
{
    protected int $statusCode = ResponseAlias::HTTP_UNPROCESSABLE_ENTITY;

    protected ?string $errorCode = 'VALIDATION_ERROR';

    /**
     * @param  array<string, array<int, string>>  $errors
     * @param  string|null  $type  Problem type URI reference.
     */
    public function __construct(
        array $errors,
        ?string $message = null,
        ?Throwable $previous = null,
        private readonly ?string $type = null
    ) {
        $this->errors = $errors;

        parent::__construct($message ?? 'Validation failed', 0, $previous);
    }

    public function getType(): string
    {
        return $this->type ?? '/problems/validation-error';
    }

    public function getTitle(): string
    {
        return 'Validation Error';
    }
}
