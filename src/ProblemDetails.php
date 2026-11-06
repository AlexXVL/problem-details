<?php

declare(strict_types=1);

namespace AlexXVL\ProblemDetails;

use Illuminate\Http\JsonResponse;

/**
 * RFC 9457 Problem Details document.
 *
 * Extension members used by the application:
 * - `code` — stable machine-readable error code;
 * - `errors` — field validation errors.
 */
final readonly class ProblemDetails
{
    /**
     * @param  array<string, array<int, string>>|null  $errors
     * @param  array<string, string|array<int, string>>  $headers
     */
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public ?string $detail,
        public string $instance,
        public ?string $code = null,
        public ?array $errors = null,
        public array $headers = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $problem = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
        ];

        if ($this->detail !== null) {
            $problem['detail'] = $this->detail;
        }

        $problem['instance'] = $this->instance;

        if ($this->code !== null) {
            $problem['code'] = $this->code;
        }

        if ($this->errors !== null) {
            $problem['errors'] = $this->errors;
        }

        return $problem;
    }

    public function toResponse(): JsonResponse
    {
        return new JsonResponse(
            $this->toArray(),
            $this->status,
            ['Content-Type' => 'application/problem+json'] + $this->headers,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
