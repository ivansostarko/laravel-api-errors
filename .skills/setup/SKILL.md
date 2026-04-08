---
name: laravel-api-errors-setup
description: Install, configure, and integrate the ivansostarko/laravel-api-errors package into a Laravel application. Use this skill whenever the user mentions "laravel-api-errors", "API error codes setup", "centralized error handling Laravel", installing or configuring API error responses, RFC 7807 setup, request ID tracing setup, Sentry error code integration, or wants to wire up consistent JSON error responses in a Laravel project. Also trigger when the user asks how to register the exception renderer, add the request ID middleware, or publish the api-errors config.
---

# Laravel API Errors — Setup & Integration

This skill walks through the full installation and integration of `ivansostarko/laravel-api-errors` into a Laravel 11/12/13 application.

## Step 1: Install the package

```bash
composer require ivansostarko/laravel-api-errors
```

The service provider auto-discovers via `extra.laravel.providers` in composer.json.

## Step 2: Publish the config

```bash
php artisan vendor:publish --tag=api-errors-config
```

This creates `config/api-errors.php`. Key settings to review:

| Setting | Default | Purpose |
|---|---|---|
| `format` | `default` | `default` (flat JSON) or `rfc7807` (Problem Details) |
| `request_id_header` | `X-Request-Id` | Header name for distributed tracing |
| `auto_request_id` | `true` | Generate UUID if header missing |
| `use_translations` | `true` | Resolve messages via Laravel translator |
| `logging.enabled` | `true` | Auto-log errors |
| `logging.exclude_status` | `[404, 422]` | Skip logging for these HTTP statuses |
| `sentry.enabled` | `false` | Enable Sentry tag integration |
| `extra_enums` | `[]` | Register app-specific error code enums |

## Step 3: Register the exception renderer

In `bootstrap/app.php`, inside `withExceptions`:

```php
use LaravelApiErrors\Support\ExceptionRenderer;

return Application::configure(basePath: dirname(__DIR__))
    ->withExceptions(function (Exceptions $exceptions) {
        ExceptionRenderer::register($exceptions);
    })
    ->create();
```

This automatically converts these Laravel exceptions into consistent JSON on API routes:

- `ApiException` → the error code you set
- `ValidationException` → `VALIDATION_ERROR` (422)
- `AuthenticationException` → `AUTH_UNAUTHENTICATED` (401)
- `ModelNotFoundException` → `RESOURCE_NOT_FOUND` (404)
- `NotFoundHttpException` → `RESOURCE_NOT_FOUND` (404)
- `MethodNotAllowedHttpException` → `METHOD_NOT_ALLOWED` (405)
- `TooManyRequestsHttpException` → `TOO_MANY_REQUESTS` (429)

It only renders JSON when the request expects JSON or hits `api/*` routes — web routes are unaffected.

## Step 4: Add the request ID middleware

In `bootstrap/app.php`, inside `withMiddleware`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \LaravelApiErrors\Http\Middleware\AttachRequestId::class,
    ]);
})
```

This generates a UUID v4 `X-Request-Id` if the incoming request doesn't have one, and attaches it to the response. The same ID appears in error JSON bodies, logs, and Sentry tags.

## Step 5: Publish the starter enum (optional)

```bash
php artisan vendor:publish --tag=api-errors-stubs
```

This copies `AppErrorCode.php` into `app/Enums/` as a starting point. Register it:

```php
// config/api-errors.php
'extra_enums' => [
    \App\Enums\AppErrorCode::class,
],
```

## Step 6: Enable Sentry (optional)

Set in `.env`:

```
API_ERRORS_SENTRY=true
```

Requires `sentry/sentry-laravel` to be installed. The package automatically sets `error_code`, `error_domain`, `http_status`, and `request_id` as Sentry tags.

## Step 7: Generate translations (optional)

```bash
php artisan api-errors:sync-translations --locale=en
```

Creates `lang/en/api-errors.php` with all registered codes. Edit to customize messages.

## Verification

Run `php artisan api-errors:list` to confirm all codes are registered. You should see a table with Code, HTTP, Domain, Severity, and Message columns.

## Common mistakes

1. **Forgetting `ExceptionRenderer::register()`** — errors won't auto-render as JSON.
2. **Registering the same code string in two enums** — throws `LogicException` at boot. Each code must be globally unique.
3. **Not adding middleware** — request IDs won't appear in responses or logs.
4. **Setting `API_ERRORS_DEBUG=true` in production** — exposes stack traces.
