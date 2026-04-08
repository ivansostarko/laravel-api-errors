# Laravel API Errors

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ivansostarko/laravel-api-errors.svg?style=flat-square)](https://packagist.org/packages/ivansostarko/laravel-api-errors)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](https://github.com/ivansostarko/laravel-api-errors/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/ivansostarko/laravel-api-errors.svg?style=flat-square)](https://packagist.org/packages/ivansostarko/laravel-api-errors)

**Centralized, enum-based API error codes for Laravel** — frontend-safe, RFC 7807 compatible, Swagger-ready, TypeScript-exportable, with Sentry integration, request tracing, and translation support.

Stop scattering magic strings and ad-hoc error responses across your codebase. Define every error code once as a PHP enum, and let the package handle JSON responses, logging, tracing, and cross-team contracts automatically.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Error Code Enums](#error-code-enums)
  - [The Contract](#the-contract)
  - [Creating Your Own Enum](#creating-your-own-enum)
  - [Registering Enums](#registering-enums)
  - [Domain Grouping](#domain-grouping)
- [Throwing & Responding](#throwing--responding)
  - [Using the Enum Directly](#using-the-enum-directly)
  - [Using Helper Functions](#using-helper-functions)
  - [Validation Error Mapping](#validation-error-mapping)
- [Response Formats](#response-formats)
  - [Default JSON](#default-json)
  - [RFC 7807 Problem Details](#rfc-7807-problem-details)
- [Laravel Exception Handler Integration](#laravel-exception-handler-integration)
- [Request ID Tracing](#request-id-tracing)
- [Translations](#translations)
- [Logging Integration](#logging-integration)
- [Sentry Integration](#sentry-integration)
- [TypeScript Export](#typescript-export)
- [Swagger / OpenAPI Export](#swagger--openapi-export)
- [Artisan Commands](#artisan-commands)
- [Microservice Architecture](#microservice-architecture)
- [Monolith Architecture](#monolith-architecture)
- [Configuration Reference](#configuration-reference)
- [Testing](#testing)
- [License](#license)

---

## Features

| Feature | Description |
|---|---|
| **Centralized error codes** | Every code lives in a single enum — no duplicates, no magic strings. |
| **Enum-based registry** | PHP 8.1+ backed enums implement a shared contract. |
| **ApiException** | Throw a rich exception that auto-renders to JSON. |
| **Helper functions** | `api_error()`, `api_abort()`, `api_error_code()` for expressive code. |
| **Auto JSON responses** | Laravel's exception handler renders consistent JSON automatically. |
| **Frontend-safe** | Stable string codes that frontend teams can rely on as a contract. |
| **RFC 7807 support** | Toggle between flat JSON and `application/problem+json`. |
| **TypeScript export** | Generate a `.ts` file with all codes, types, and a type guard. |
| **Swagger / OpenAPI** | Export an OpenAPI 3.1 JSON schema of all error codes. |
| **Translation ready** | Every code resolves through Laravel's translator. |
| **Validation mapping** | Flattens Laravel validation errors into `[{field, message}]`. |
| **Domain grouping** | Group codes by logical domain (AUTH, BILLING, etc.). |
| **Logging integration** | Auto-logs errors with code, domain, request ID, and severity. |
| **Sentry integration** | Sets tags (`error_code`, `domain`, `request_id`) on Sentry events. |
| **Request ID tracing** | Middleware generates/propagates `X-Request-Id` across services. |
| **Publishable config** | Full control via `config/api-errors.php`. |
| **Monolith friendly** | Register multiple domain enums in one app. |
| **Microservice friendly** | Share the contract package; each service registers its own codes. |
| **Backward compatible** | Default format is a simple, flat JSON object. |

---

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

---

## Installation

```bash
composer require ivansostarko/laravel-api-errors
```

Publish the config file:

```bash
php artisan vendor:publish --tag=api-errors-config
```

Optionally publish a starter enum stub into your app:

```bash
php artisan vendor:publish --tag=api-errors-stubs
```

---

## Quick Start

### 1. Define an error code enum

```php
// app/Enums/AppErrorCode.php

namespace App\Enums;

use LaravelApiErrors\Contracts\ApiErrorCode;
use LaravelApiErrors\Enums\InteractsWithApiError;

enum AppErrorCode: string implements ApiErrorCode
{
    use InteractsWithApiError;

    case ORDER_ALREADY_SHIPPED = 'ORDER_ALREADY_SHIPPED';

    public function code(): string       { return $this->value; }
    public function httpStatus(): int    { return 409; }
    public function domain(): string     { return 'ORDER'; }
    public function severity(): string   { return 'warning'; }

    public function message(): string
    {
        return match ($this) {
            self::ORDER_ALREADY_SHIPPED => 'This order has already been shipped.',
        };
    }
}
```

### 2. Register it

```php
// config/api-errors.php
'extra_enums' => [
    \App\Enums\AppErrorCode::class,
],
```

### 3. Use it

```php
// In a controller
use App\Enums\AppErrorCode;

public function cancel(Order $order)
{
    if ($order->shipped) {
        AppErrorCode::ORDER_ALREADY_SHIPPED->throw(['order_id' => $order->id]);
    }

    // ...
}
```

The response is automatically rendered as:

```json
{
    "success": false,
    "error_code": "ORDER_ALREADY_SHIPPED",
    "message": "This order has already been shipped.",
    "domain": "ORDER",
    "status": 409,
    "request_id": "a1b2c3d4-...",
    "context": {
        "order_id": 42
    }
}
```

---

## Error Code Enums

### The Contract

Every error code enum must implement `LaravelApiErrors\Contracts\ApiErrorCode`:

```php
interface ApiErrorCode
{
    public function code(): string;      // Unique stable string, e.g. "AUTH_TOKEN_EXPIRED"
    public function message(): string;   // Default human-readable message
    public function httpStatus(): int;   // HTTP status code
    public function domain(): string;    // Logical group, e.g. "AUTH"
    public function severity(): string;  // Log level: debug, info, warning, error, fatal
}
```

### Creating Your Own Enum

Use the `InteractsWithApiError` trait to get helper methods (`throw()`, `respond()`, `exception()`, `translatedMessage()`):

```php
enum BillingErrorCode: string implements ApiErrorCode
{
    use InteractsWithApiError;

    case CARD_DECLINED        = 'BILLING_CARD_DECLINED';
    case INSUFFICIENT_FUNDS   = 'BILLING_INSUFFICIENT_FUNDS';
    case SUBSCRIPTION_EXPIRED = 'BILLING_SUBSCRIPTION_EXPIRED';

    public function code(): string    { return $this->value; }
    public function domain(): string  { return 'BILLING'; }
    public function severity(): string { return 'warning'; }

    public function message(): string
    {
        return match ($this) {
            self::CARD_DECLINED        => 'The credit card was declined.',
            self::INSUFFICIENT_FUNDS   => 'Insufficient funds.',
            self::SUBSCRIPTION_EXPIRED => 'Your subscription has expired.',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::CARD_DECLINED        => 402,
            self::INSUFFICIENT_FUNDS   => 402,
            self::SUBSCRIPTION_EXPIRED => 403,
        };
    }
}
```

### Registering Enums

Add enum classes to the `extra_enums` array in `config/api-errors.php`. The registry validates that no two enums register the same code string — if they do, it throws a `LogicException` at boot time.

```php
'extra_enums' => [
    \App\Enums\BillingErrorCode::class,
    \App\Enums\InventoryErrorCode::class,
    \Modules\Shipping\Enums\ShippingErrorCode::class,
],
```

### Domain Grouping

Every code declares a `domain()`. You can list codes by domain:

```bash
php artisan api-errors:list --domain=BILLING
```

Or programmatically:

```php
$registry = app(\LaravelApiErrors\Support\ErrorCodeRegistry::class);
$billingCodes = $registry->domain('BILLING');
$grouped      = $registry->groupedByDomain();
```

---

## Throwing & Responding

### Using the Enum Directly

```php
use App\Enums\AppErrorCode;

// Throw — renders automatically via Laravel's exception handler
AppErrorCode::ORDER_ALREADY_SHIPPED->throw(['order_id' => $id]);

// Return a response without throwing
return AppErrorCode::ORDER_ALREADY_SHIPPED->respond(['order_id' => $id]);

// Create the exception object for deferred throwing
$e = AppErrorCode::ORDER_ALREADY_SHIPPED->exception(['order_id' => $id]);
```

### Using Helper Functions

```php
// Return a JSON response
return api_error('ORDER_ALREADY_SHIPPED', ['order_id' => $id]);

// Throw immediately
api_abort('ORDER_ALREADY_SHIPPED', ['order_id' => $id]);

// Resolve code string → enum case
$code = api_error_code('ORDER_ALREADY_SHIPPED');
```

Helpers accept either an `ApiErrorCode` enum instance or a code string.

### Validation Error Mapping

When Laravel throws a `ValidationException` on an API route, the package automatically catches it and returns:

```json
{
    "success": false,
    "error_code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "domain": "GENERAL",
    "status": 422,
    "context": {
        "errors": [
            { "field": "email", "message": "The email field is required." },
            { "field": "email", "message": "The email must be a valid email address." },
            { "field": "name",  "message": "The name field is required." }
        ]
    }
}
```

The nested validation errors are flattened into a consistent `[{field, message}]` array that frontends can iterate over directly.

---

## Response Formats

### Default JSON

Set `API_ERRORS_FORMAT=default` (this is the default):

```json
{
    "success": false,
    "error_code": "AUTH_TOKEN_EXPIRED",
    "message": "Your authentication token has expired.",
    "domain": "AUTH",
    "status": 401,
    "request_id": "550e8400-e29b-41d4-a716-446655440000",
    "context": {}
}
```

### RFC 7807 Problem Details

Set `API_ERRORS_FORMAT=rfc7807`:

```json
{
    "type": "https://api-errors.dev/codes/auth_token_expired",
    "title": "AUTH_TOKEN_EXPIRED",
    "status": 401,
    "detail": "Your authentication token has expired.",
    "instance": "/api/v1/profile",
    "request_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

The `Content-Type` header is set to `application/problem+json` automatically.

---

## Laravel Exception Handler Integration

Register the package's renderable callbacks in your `bootstrap/app.php`:

```php
use LaravelApiErrors\Support\ExceptionRenderer;

return Application::configure(basePath: dirname(__DIR__))
    ->withExceptions(function (Exceptions $exceptions) {
        ExceptionRenderer::register($exceptions);
    })
    ->create();
```

This automatically converts the following exceptions into consistent API error responses (only for requests that expect JSON or hit `api/*` routes):

| Laravel Exception | Error Code |
|---|---|
| `ApiException` | Whatever code you set |
| `ValidationException` | `VALIDATION_ERROR` |
| `AuthenticationException` | `AUTH_UNAUTHENTICATED` |
| `ModelNotFoundException` | `RESOURCE_NOT_FOUND` |
| `NotFoundHttpException` | `RESOURCE_NOT_FOUND` |
| `MethodNotAllowedHttpException` | `METHOD_NOT_ALLOWED` |
| `TooManyRequestsHttpException` | `TOO_MANY_REQUESTS` |
| Any `HttpExceptionInterface` | `INTERNAL_SERVER_ERROR` |

---

## Request ID Tracing

Add the middleware to your API stack:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \LaravelApiErrors\Http\Middleware\AttachRequestId::class,
    ]);
})
```

Behavior:
- If the incoming request has an `X-Request-Id` header (e.g. from a gateway or upstream service), it is preserved.
- If not, a UUID v4 is generated automatically.
- The same ID is attached to the response header and included in every error JSON body.
- The ID is also sent to Sentry and the logger for end-to-end tracing.

Configure the header name in `config/api-errors.php`:

```php
'request_id_header' => 'X-Request-Id',
```

---

## Translations

Every error code's message is resolved through Laravel's translator. Generate a translation file:

```bash
php artisan api-errors:sync-translations --locale=en
php artisan api-errors:sync-translations --locale=es
```

This creates `lang/en/api-errors.php` with all registered codes. Edit the file to customize messages per locale:

```php
// lang/es/api-errors.php
return [
    'AUTH_UNAUTHENTICATED' => 'Se requiere autenticación.',
    'VALIDATION_ERROR'     => 'Los datos proporcionados no son válidos.',
];
```

Translations are resolved automatically — no extra code needed.

---

## Logging Integration

Every `ApiException` is logged automatically with structured context:

```
[2026-04-08 12:00:00] local.ERROR: [API Error] AUTH_TOKEN_EXPIRED: Your authentication token has expired. {
    "error_code": "AUTH_TOKEN_EXPIRED",
    "domain": "AUTH",
    "http_status": 401,
    "request_id": "550e8400-...",
    "url": "https://example.com/api/v1/profile",
    "method": "GET"
}
```

Configure in `config/api-errors.php`:

```php
'logging' => [
    'enabled' => true,
    'channel' => 'stack',        // null = default channel
    'exclude_status' => [404, 422], // Don't log these
],
```

---

## Sentry Integration

Enable in `.env`:

```
API_ERRORS_SENTRY=true
```

When an `ApiException` is captured, the package:
- Sets Sentry tags: `error_code`, `error_domain`, `http_status`, `request_id`
- Sets Sentry context with the full error payload
- Maps `severity()` to Sentry severity levels

This means you can filter Sentry issues by error code or domain directly in the Sentry dashboard.

---

## TypeScript Export

Generate a TypeScript file that your frontend can import:

```bash
php artisan api-errors:ts
```

Output (`resources/js/api-errors.ts`):

```typescript
export const API_ERROR_CODES = {
  UNKNOWN_ERROR: { code: 'UNKNOWN_ERROR', status: 500, domain: 'GENERAL', message: 'An unexpected error occurred.' },
  VALIDATION_ERROR: { code: 'VALIDATION_ERROR', status: 422, domain: 'GENERAL', message: 'The given data was invalid.' },
  AUTH_UNAUTHENTICATED: { code: 'AUTH_UNAUTHENTICATED', status: 401, domain: 'AUTH', message: 'Authentication is required.' },
  // ... all registered codes
} as const;

export type ApiErrorCode = keyof typeof API_ERROR_CODES;

export type ApiErrorResponse = {
  success: false;
  error_code: ApiErrorCode;
  message: string;
  domain: string;
  status: number;
  request_id?: string;
  context?: Record<string, unknown>;
};

export function isApiError(data: unknown): data is ApiErrorResponse {
  return typeof data === 'object' && data !== null && 'error_code' in data && 'success' in data;
}
```

Usage in frontend:

```typescript
import { isApiError, API_ERROR_CODES } from './api-errors';

const res = await fetch('/api/orders/42', { method: 'DELETE' });
const data = await res.json();

if (isApiError(data)) {
  switch (data.error_code) {
    case 'ORDER_ALREADY_SHIPPED':
      toast.warn('Cannot cancel a shipped order.');
      break;
    case 'AUTH_TOKEN_EXPIRED':
      router.push('/login');
      break;
    default:
      toast.error(data.message);
  }
}
```

---

## Swagger / OpenAPI Export

```bash
php artisan api-errors:swagger
```

Generates an OpenAPI 3.1 JSON file at `storage/api-docs/error-codes.json` containing:
- An `ApiErrorCode` string enum schema with all registered codes
- `ApiErrorResponse` and `RFC7807ProblemDetail` object schemas
- `x-error-code-details` with HTTP status, domain, message, and severity for each code

Reference these schemas in your main OpenAPI spec:

```yaml
responses:
  '409':
    description: Conflict
    content:
      application/json:
        schema:
          $ref: './error-codes.json#/components/schemas/ApiErrorResponse'
```

---

## Artisan Commands

| Command | Description |
|---|---|
| `php artisan api-errors:list` | List all registered error codes in a table. |
| `php artisan api-errors:list --domain=AUTH` | Filter by domain. |
| `php artisan api-errors:ts` | Export TypeScript file. |
| `php artisan api-errors:swagger` | Export OpenAPI schema. |
| `php artisan api-errors:sync-translations` | Generate translation file for a locale. |

---

## Microservice Architecture

In a microservice setup, extract the contract into a shared Composer package:

```
shared-api-contracts/
├── src/
│   └── Contracts/
│       └── ApiErrorCode.php
│   └── Enums/
│       └── InteractsWithApiError.php
└── composer.json
```

Each microservice then:
1. Requires the shared contracts package.
2. Requires `laravel-api-errors/laravel-api-errors`.
3. Defines its own domain-specific enums implementing `ApiErrorCode`.
4. Registers them in `config/api-errors.php`.

The `X-Request-Id` header flows through service-to-service calls automatically.

---

## Monolith Architecture

In a monolith with multiple modules/domains, create one enum per domain and register them all:

```
app/
├── Enums/
│   ├── AuthErrorCode.php
│   ├── BillingErrorCode.php
│   ├── InventoryErrorCode.php
│   └── ShippingErrorCode.php
```

```php
// config/api-errors.php
'extra_enums' => [
    \App\Enums\AuthErrorCode::class,
    \App\Enums\BillingErrorCode::class,
    \App\Enums\InventoryErrorCode::class,
    \App\Enums\ShippingErrorCode::class,
],
```

The registry ensures no two domains accidentally use the same code string.

---

## Configuration Reference

Publish with `php artisan vendor:publish --tag=api-errors-config`.

| Key | Default | Description |
|---|---|---|
| `format` | `default` | `default` or `rfc7807` |
| `debug` | `false` | Include stack traces (only when `APP_DEBUG=true`) |
| `request_id_header` | `X-Request-Id` | Header name for request tracing |
| `auto_request_id` | `true` | Generate UUID if header is missing |
| `request_id_in_response` | `true` | Include request ID in JSON body |
| `use_translations` | `true` | Resolve messages through Laravel translator |
| `translation_namespace` | `api-errors` | Translation namespace |
| `logging.enabled` | `true` | Enable automatic logging |
| `logging.channel` | `null` | Log channel (null = default) |
| `logging.exclude_status` | `[404, 422]` | HTTP statuses to skip logging |
| `sentry.enabled` | `false` | Enable Sentry integration |
| `sentry.set_tags` | `true` | Set error_code/domain as Sentry tags |
| `validation_error_code` | `VALIDATION_ERROR` | Code used for validation exceptions |
| `extra_enums` | `[]` | Additional enum classes to register |
| `typescript_path` | `resources/js/api-errors.ts` | TypeScript export output path |
| `swagger_path` | `storage/api-docs/error-codes.json` | Swagger export output path |

---

## Testing

```bash
composer test
```

Or with PHPUnit directly:

```bash
./vendor/bin/phpunit
```

---

## License

The MIT License (MIT). Please see [LICENSE](https://github.com/ivansostarko/laravel-api-errors/blob/main/LICENSE) for more information.
