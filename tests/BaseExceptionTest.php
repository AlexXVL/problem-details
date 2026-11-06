<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails\Tests;

use AlexXVL\ProblemDetails\Exceptions\BaseException;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class BaseExceptionTest extends TestCase
{
    public function test_renders_problem_details_document(): void
    {
        $exception = $this->makeException();
        $request = Request::create('/api/v1/test?foo=bar');

        $problem = $exception->toProblemDetails($request);

        self::assertSame('https://example.com/problems/test', $problem->type);
        self::assertSame('Test Error', $problem->title);
        self::assertSame(404, $problem->status);
        self::assertNull($problem->detail);
        self::assertSame('/api/v1/test?foo=bar', $problem->instance);
        self::assertSame('TEST_NOT_FOUND', $problem->code);
    }

    public function test_exception_message_is_rendered_as_detail(): void
    {
        $exception = $this->makeException('Resource 123 was not found.');
        $request = Request::create('/api/v1/test');

        self::assertSame('Resource 123 was not found.', $exception->getDetail());
        self::assertSame('Resource 123 was not found.', $exception->toProblemDetails($request)->detail);
        self::assertSame(
            'Resource 123 was not found.',
            $exception->render($request)->getData(true)['detail'],
        );
    }

    public function test_business_errors_are_not_reported(): void
    {
        self::assertTrue($this->makeException()->report());
    }

    public function test_server_errors_are_reported(): void
    {
        $exception = new class extends BaseException
        {
            protected int $statusCode = 500;
        };

        self::assertFalse($exception->report());
    }

    private function makeException(string $message = ''): BaseException
    {
        return new class($message) extends BaseException
        {
            protected int $statusCode = 404;

            protected ?string $errorCode = 'TEST_NOT_FOUND';

            public function getType(): string
            {
                return 'https://example.com/problems/test';
            }

            public function getTitle(): string
            {
                return 'Test Error';
            }
        };
    }
}
