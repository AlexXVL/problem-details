<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails\Tests;

use AlexXVL\ProblemDetails\ProblemDetails;
use PHPUnit\Framework\TestCase;

final class ProblemDetailsTest extends TestCase
{
    public function test_builds_standard_document_with_extensions(): void
    {
        $problem = new ProblemDetails(
            type: 'https://example.com/problems/test',
            title: 'Test Error',
            status: 404,
            detail: 'The requested resource was not found.',
            instance: '/api/v1/test',
            code: 'TEST_NOT_FOUND',
            errors: ['field' => ['Invalid value.']],
        );

        self::assertSame([
            'type' => 'https://example.com/problems/test',
            'title' => 'Test Error',
            'status' => 404,
            'detail' => 'The requested resource was not found.',
            'instance' => '/api/v1/test',
            'code' => 'TEST_NOT_FOUND',
            'errors' => ['field' => ['Invalid value.']],
        ], $problem->toArray());
    }

    public function test_omits_optional_members(): void
    {
        $problem = new ProblemDetails(
            type: 'about:blank',
            title: 'Not Found',
            status: 404,
            detail: null,
            instance: '/api/v1/test',
        );

        self::assertSame([
            'type' => 'about:blank',
            'title' => 'Not Found',
            'status' => 404,
            'instance' => '/api/v1/test',
        ], $problem->toArray());
    }

    public function test_returns_problem_json_response(): void
    {
        $problem = new ProblemDetails(
            type: 'about:blank',
            title: 'Not Found',
            status: 404,
            detail: null,
            instance: '/api/v1/test',
        );

        $response = $problem->toResponse();

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
    }
}
