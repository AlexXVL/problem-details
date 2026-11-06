<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails\Tests;

use AlexXVL\ProblemDetails\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function test_uses_relative_type_by_default(): void
    {
        $exception = new ValidationException([
            'email' => ['The email field is required.'],
        ]);

        self::assertSame('/problems/validation-error', $exception->getType());
    }

    public function test_accepts_custom_type(): void
    {
        $exception = new ValidationException(
            errors: [],
            type: 'https://example.com/problems/validation-error',
        );

        self::assertSame('https://example.com/problems/validation-error', $exception->getType());
    }
}
