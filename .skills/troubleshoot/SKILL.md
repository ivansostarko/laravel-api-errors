---
name: laravel-api-errors-troubleshoot
description: Diagnose and fix issues with the ivansostarko/laravel-api-errors package. Use this skill whenever the user reports that API errors are not rendering as JSON, error codes are missing, duplicate code exceptions are thrown at boot, request IDs are not appearing, Sentry tags are not set, translations are not resolving, validation errors have the wrong shape, or any other problem with laravel-api-errors. Also trigger when the user says "my API errors aren't working", "I'm getting a LogicException about duplicate codes", "error responses are HTML instead of JSON", or "request_id is null".
---

# Laravel API Errors — Troubleshooting

Diagnose and fix the most common issues.

---

## Problem: Errors render as HTML, not JSON

**Symptoms**: API endpoints return Laravel's default HTML error page or Whoops page instead of the structured JSON response.

**Causes & Fixes**:

1. **ExceptionRenderer not registered.** Verify `bootstrap/app.php`:
   ```php
   ->withExceptions(function (Exceptions $exceptions) {
       \LaravelApiErrors\Support\ExceptionRenderer::register($exceptions);
   })
   ```

2. **Request doesn't signal JSON.** The renderer only activates when `$request->expectsJson()` is true OR the URL matches `api/*`. Fix by:
   - Sending `Accept: application/json` header from your client
   - Prefixing routes with `api/`
   - Or using `X-Requested-With: XMLHttpRequest`

3. **Another exception handler is catching first.** If you have other `renderable()` calls for the same exception types, order matters — the first match wins. Put `ExceptionRenderer::register()` before other renderables.

---

## Problem: LogicException — "Duplicate API error code"

**Symptoms**: App crashes at boot with `Duplicate API error code [SOME_CODE] registered by [EnumA] and [EnumB]`.

**Cause**: Two enum classes define a case with the same code string.

**Fix**: Rename one of the cases. Convention: prefix with the domain name — `AUTH_TOKEN_EXPIRED`, `BILLING_CARD_DECLINED`, etc. Run `php artisan api-errors:list` to see all registered codes.

---

## Problem: InvalidArgumentException — enum must implement ApiErrorCode

**Symptoms**: `[SomeClass] must be a backed enum implementing LaravelApiErrors\Contracts\ApiErrorCode`.

**Causes**:
1. The class listed in `extra_enums` is not a backed string enum.
2. The enum doesn't `implements ApiErrorCode`.
3. Typo in the class name in config.

**Fix**: Verify the enum declaration:
```php
enum MyCode: string implements \LaravelApiErrors\Contracts\ApiErrorCode
{
    use \LaravelApiErrors\Enums\InteractsWithApiError;
    // ...
}
```

---

## Problem: request_id is null in responses

**Symptoms**: The `request_id` field is missing or null in error JSON.

**Causes & Fixes**:

1. **Middleware not registered.** Add to `bootstrap/app.php`:
   ```php
   $middleware->api(prepend: [
       \LaravelApiErrors\Http\Middleware\AttachRequestId::class,
   ]);
   ```

2. **`auto_request_id` is false** and no upstream service sends the header. Set `auto_request_id` to `true` in config.

3. **`request_id_in_response` is false.** Set to `true` in config.

---

## Problem: Translations not resolving (always shows English default)

**Symptoms**: Error messages always show the enum's `message()` value regardless of locale.

**Causes & Fixes**:

1. **Translation file doesn't exist.** Run:
   ```bash
   php artisan api-errors:sync-translations --locale=de
   ```

2. **`use_translations` is false.** Set to `true` in `config/api-errors.php`.

3. **Wrong namespace.** The package looks for `api-errors::messages.{CODE}`. If you published translations with `--tag=api-errors-translations`, they go to `lang/vendor/api-errors/{locale}/messages.php`. If you used `sync-translations`, they go to `lang/{locale}/api-errors.php`. Both work but the keys must match.

4. **App locale not set.** Check `App::getLocale()` returns the expected locale.

---

## Problem: Sentry tags not appearing

**Symptoms**: Sentry events don't have `error_code`, `error_domain`, or `request_id` tags.

**Causes & Fixes**:

1. **Sentry not enabled.** Set `API_ERRORS_SENTRY=true` in `.env`.
2. **`sentry/sentry-laravel` not installed.** The package checks `function_exists('\Sentry\captureException')` — if Sentry SDK isn't installed, it silently skips.
3. **`sentry.set_tags` is false.** Set to `true` in config.
4. **Exception is not an `ApiException`.** Only `ApiException` instances trigger Sentry reporting. Standard Laravel exceptions caught by ExceptionRenderer are logged but not sent to Sentry by default.

---

## Problem: Validation errors have wrong format

**Symptoms**: Validation errors don't include the `context.errors` array with `[{field, message}]` shape.

**Cause**: The `ExceptionRenderer` isn't catching `ValidationException`, or another handler intercepts it first.

**Fix**: Ensure `ExceptionRenderer::register()` is called and comes before other validation exception handlers. The package flattens validation errors into:
```json
{
  "context": {
    "errors": [
      { "field": "email", "message": "The email field is required." }
    ]
  }
}
```

---

## Problem: api_error() or api_abort() throws "Unknown API error code"

**Symptoms**: `InvalidArgumentException: Unknown API error code [SOME_CODE]`.

**Cause**: The code string passed doesn't match any registered enum case.

**Fix**:
1. Check spelling — code strings are case-sensitive.
2. Verify the enum is registered in `extra_enums`.
3. Run `php artisan api-errors:list` to see all available codes.
4. Prefer passing the enum case directly: `api_error(BillingErrorCode::CARD_DECLINED)` instead of `api_error('BILLING_CARD_DECLINED')`.

---

## Problem: RFC 7807 format not working

**Symptoms**: Response is flat JSON even though RFC 7807 is configured.

**Fix**: Check config and env:
```php
// config/api-errors.php
'format' => env('API_ERRORS_FORMAT', 'default'),
```
```env
API_ERRORS_FORMAT=rfc7807
```

The RFC 7807 response uses `Content-Type: application/problem+json` and has fields `type`, `title`, `status`, `detail`, `instance`.

---

## Problem: Debug traces showing in production

**Symptoms**: Error responses include stack traces, file paths, or exception class names.

**Cause**: `API_ERRORS_DEBUG=true` is set AND `APP_DEBUG=true`.

**Fix**: In production, ensure both are false:
```env
APP_DEBUG=false
API_ERRORS_DEBUG=false
```

---

## Diagnostic Checklist

Run through this when something isn't working:

```bash
# 1. Is the package installed?
composer show ivansostarko/laravel-api-errors

# 2. Is the config published?
cat config/api-errors.php

# 3. Are codes registered?
php artisan api-errors:list

# 4. Is the service provider loaded?
php artisan about | grep ApiErrors

# 5. Check .env values
grep API_ERRORS .env
```
