# AlexXVL Problem Details

RFC 9457 Problem Details responses for Laravel APIs.

The package is hosted at
[github.com/AlexXVL/problem-details](https://github.com/AlexXVL/problem-details).

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13

## Installation

```bash
composer require alexxvl/problem-details
```

The service provider is registered automatically through Laravel package discovery.
If package discovery is disabled, register it manually:

```php
AlexXVL\ProblemDetails\ProblemDetailsServiceProvider::class
```

## Development from source

To install from the repository instead of Packagist, add a VCS repository to
your application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/AlexXVL/problem-details"
        }
    ]
}
```

Then require a tagged release:

```bash
composer require alexxvl/problem-details:^0.1
```

The constraint resolves against a tag (for example, `v0.1.0`).

For local development while the package lives next to the application, use a
path repository instead:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../packages/problem-details",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

Then require the development branch:

```bash
composer require alexxvl/problem-details:@dev
```

## Configuration

Publish the package configuration:

```bash
php artisan vendor:publish --tag=problem-details-config
```

The `api_prefix` option in `config/problem-details.php` controls which requests
are converted by the framework exception renderer. The default is `api`, which
matches both `/api` and `/api/v1/...`. Set it to an empty string to convert
framework exceptions for every request. Exceptions extending `BaseException`
are rendered on every request regardless of this setting.

## Usage

Extend the base exception in your application module:

```php
namespace App\Modules\User\Exceptions;

use AlexXVL\ProblemDetails\Exceptions\BaseException;

abstract class UserException extends BaseException
{
}
```

Define a concrete error:

```php
namespace App\Modules\User\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class UserNotFoundException extends UserException
{
    protected int $statusCode = Response::HTTP_NOT_FOUND;

    protected ?string $errorCode = 'USER_NOT_FOUND';

    public function getType(): string
    {
        return url('/problems/user-not-found');
    }

    public function getTitle(): string
    {
        return 'User Not Found';
    }
}
```

Throw it from a service or repository:

```php
throw new UserNotFoundException("User {$uuid} not found");
```

`title` describes the problem type and should stay stable. `Exception::$message`
is exposed as the RFC `detail` member and should describe the concrete
occurrence. If no message is provided, `detail` is omitted.

## Validation errors

`AlexXVL\ProblemDetails\Exceptions\ValidationException` is available for
application-level validation errors:

```php
throw new ValidationException($validator->errors()->toArray());
```

It renders a `422` response with the `errors` extension member:

```json
{
    "type": "/problems/validation-error",
    "title": "Validation Error",
    "status": 422,
    "detail": "Validation failed",
    "instance": "/api/v1/users",
    "code": "VALIDATION_ERROR",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

The example above shows the default relative type used by an
application-thrown exception. When Laravel's own `ValidationException` is
converted by the renderer, the type is resolved through the application URL
generator. You can also pass a custom `type` explicitly:

```php
throw new ValidationException(
    $validator->errors()->toArray(),
    type: 'https://example.com/problems/validation-error',
);
```

Laravel's own `ValidationException`, `AuthenticationException` and
`HttpExceptionInterface` exceptions are converted automatically.

## Response format

```json
{
    "type": "http://localhost/problems/user-not-found",
    "title": "User Not Found",
    "status": 404,
    "detail": "User 123 not found",
    "instance": "/api/v1/users/123",
    "code": "USER_NOT_FOUND"
}
```

The response uses `Content-Type: application/problem+json`.

Extension members:

- `code` — stable machine-readable error code;
- `errors` — field validation errors.

## Notes

- Framework exceptions (validation, authentication and HTTP errors) are
  converted only for requests matching the configured `api_prefix` (default
  `api`), either exactly or with subpaths. Other requests are handled by Laravel
  as usual.
- Exceptions extending `BaseException` render as Problem Details on every
  request because they implement their own `render()` method.
- 4xx business exceptions are not reported to the logger.
- 404 responses keep the original `detail` when `app.debug` is enabled. In
  production the detail is replaced with `Not Found` so framework and model
  internals are not exposed. Exceptions extending `BaseException` keep their
  own `detail` on every request.
- 5xx responses hide exception details when `app.debug` is disabled.

## Development

```bash
composer install
composer test
```
