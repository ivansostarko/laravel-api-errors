---
name: laravel-api-errors-create-codes
description: Create new API error code enums for the ivansostarko/laravel-api-errors package. Use this skill whenever the user wants to add new error codes, create a domain-specific error enum, define custom API errors, group errors by domain, add error codes for a new module or feature, or asks how to structure error codes in a monolith or microservice Laravel app. Also trigger when the user says "add an error code for X", "create billing/auth/order errors", "I need a new error enum", or references the ApiErrorCode contract or InteractsWithApiError trait.
---

# Laravel API Errors — Creating Error Code Enums

This skill covers creating, structuring, and registering custom error code enums.

## The Contract

Every error code enum must:
1. Be a **backed string enum** (`enum Foo: string`)
2. Implement `LaravelApiErrors\Contracts\ApiErrorCode`
3. Use the `LaravelApiErrors\Enums\InteractsWithApiError` trait

The contract requires five methods:

```php
public function code(): string;      // Unique stable string, e.g. "BILLING_CARD_DECLINED"
public function message(): string;   // Default human-readable message
public function httpStatus(): int;   // HTTP status code (401, 404, 422, etc.)
public function domain(): string;    // Logical group: "AUTH", "BILLING", "ORDER"
public function severity(): string;  // Log level: "debug", "info", "warning", "error", "fatal"
```

## Template

```php
<?php

namespace App\Enums;

use LaravelApiErrors\Contracts\ApiErrorCode;
use LaravelApiErrors\Enums\InteractsWithApiError;

enum {EnumName}: string implements ApiErrorCode
{
    use InteractsWithApiError;

    case {DOMAIN}_{ERROR_SLUG} = '{DOMAIN}_{ERROR_SLUG}';

    public function code(): string    { return $this->value; }
    public function domain(): string  { return '{DOMAIN}'; }

    public function message(): string
    {
        return match ($this) {
            self::{DOMAIN}_{ERROR_SLUG} => '{Human readable message.}',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::{DOMAIN}_{ERROR_SLUG} => {HTTP_STATUS},
        };
    }

    public function severity(): string
    {
        return match ($this) {
            default => 'warning',
        };
    }
}
```

## Naming Conventions

- **Code strings**: `DOMAIN_ERROR_SLUG` — uppercase, underscore-separated. Examples: `AUTH_TOKEN_EXPIRED`, `BILLING_CARD_DECLINED`, `ORDER_ALREADY_SHIPPED`.
- **Enum class name**: `{Domain}ErrorCode` — e.g. `BillingErrorCode`, `OrderErrorCode`, `AuthErrorCode`.
- **The case name and the string value should be identical**: `case BILLING_CARD_DECLINED = 'BILLING_CARD_DECLINED'`.
- **Domain prefix in the code string must match `domain()` return value**.

## Registering the Enum

Add to `config/api-errors.php`:

```php
'extra_enums' => [
    \App\Enums\BillingErrorCode::class,
],
```

The registry checks for duplicate code strings across ALL registered enums at boot time. If two enums define the same code string, a `LogicException` is thrown immediately — this prevents silent conflicts.

## HTTP Status Code Guide

| Status | When to use |
|---|---|
| 400 | Malformed request syntax |
| 401 | Authentication required or token invalid/expired |
| 402 | Payment required (card declined, insufficient funds) |
| 403 | Authenticated but not authorized |
| 404 | Resource not found |
| 405 | HTTP method not allowed |
| 409 | State conflict (duplicate, already exists, already processed) |
| 422 | Validation failed (semantic error in valid request) |
| 429 | Rate limited |
| 500 | Internal/unexpected server error |
| 503 | Service unavailable / maintenance |

## Severity Guide

| Severity | When to use |
|---|---|
| `debug` | Development-only errors |
| `info` | Expected/benign errors (validation, not found) |
| `warning` | Actionable but non-critical (auth failures, rate limits) |
| `error` | Unexpected failures, server errors, integration failures |
| `fatal` | System-critical, requires immediate attention |

## Domain Grouping Patterns

### Single-domain enum (simple)
One enum per domain, one file per enum:
```
app/Enums/
├── AuthErrorCode.php       // domain: AUTH
├── BillingErrorCode.php    // domain: BILLING
└── OrderErrorCode.php      // domain: ORDER
```

### Multi-domain enum (small apps)
One enum with all codes, domain derived from prefix:
```php
enum AppErrorCode: string implements ApiErrorCode
{
    case AUTH_TOKEN_EXPIRED       = 'AUTH_TOKEN_EXPIRED';
    case BILLING_CARD_DECLINED   = 'BILLING_CARD_DECLINED';
    case ORDER_ALREADY_SHIPPED   = 'ORDER_ALREADY_SHIPPED';

    public function domain(): string
    {
        return str($this->value)->before('_')->toString();
        // Or use explicit match() for more control
    }
}
```

### Microservice pattern
Each service defines its own enum. To avoid collisions, prefix with service name:
- `BILLING_CARD_DECLINED` (billing service)
- `INVENTORY_OUT_OF_STOCK` (inventory service)
- `SHIPPING_ADDRESS_INVALID` (shipping service)

Share the `ApiErrorCode` interface via a shared Composer package. Each service installs `ivansostarko/laravel-api-errors` independently.

## Using Error Codes After Creation

The `InteractsWithApiError` trait provides these methods on every case:

```php
// Throw an ApiException (auto-renders to JSON)
BillingErrorCode::CARD_DECLINED->throw(['card_last4' => '4242']);

// Return a JsonResponse without throwing
return BillingErrorCode::CARD_DECLINED->respond(['card_last4' => '4242']);

// Create exception object for deferred use
$e = BillingErrorCode::CARD_DECLINED->exception(['card_last4' => '4242']);

// Get translated message
$msg = BillingErrorCode::CARD_DECLINED->translatedMessage(['amount' => '$50']);
```

Global helpers also work with code strings:

```php
return api_error('BILLING_CARD_DECLINED', ['card_last4' => '4242']);
api_abort('BILLING_CARD_DECLINED');
$code = api_error_code('BILLING_CARD_DECLINED');
```

## Checklist Before Committing

1. ✅ Code string is globally unique (no other enum uses it)
2. ✅ Code string matches the case name
3. ✅ `domain()` returns the correct group
4. ✅ `httpStatus()` uses the right HTTP status
5. ✅ `severity()` is set appropriately for logging/Sentry
6. ✅ Enum is registered in `config/api-errors.php` → `extra_enums`
7. ✅ Run `php artisan api-errors:list` to verify
8. ✅ Run `php artisan api-errors:ts` to regenerate TypeScript (if frontend consumes it)
