---
name: laravel-api-errors-export
description: Export API error codes from the ivansostarko/laravel-api-errors package to TypeScript, Swagger/OpenAPI, or translation files. Use this skill whenever the user wants to generate a TypeScript file of error codes, export error codes for the frontend, create an OpenAPI schema for errors, sync translation files for API errors, generate i18n files from error codes, or asks about sharing error contracts with a frontend team. Also trigger for "api-errors:ts", "api-errors:swagger", "api-errors:sync-translations", or any mention of TypeScript error types, Swagger error schemas, or translating API error messages.
---

# Laravel API Errors — Export & Code Generation

This skill covers the three export commands and how to integrate their outputs.

## TypeScript Export

### Generate

```bash
php artisan api-errors:ts
# or with custom path:
php artisan api-errors:ts --path=frontend/src/types/api-errors.ts
```

Default output: `resources/js/api-errors.ts` (configurable via `typescript_path` in config).

### What it generates

```typescript
// Const object with every registered code
export const API_ERROR_CODES = {
  AUTH_UNAUTHENTICATED: { code: 'AUTH_UNAUTHENTICATED', status: 401, domain: 'AUTH', message: '...' },
  BILLING_CARD_DECLINED: { code: 'BILLING_CARD_DECLINED', status: 402, domain: 'BILLING', message: '...' },
  // ...
} as const;

// Union type of all code strings
export type ApiErrorCode = keyof typeof API_ERROR_CODES;

// Response shape types (default + RFC 7807)
export type ApiErrorResponse = { ... };
export type ApiProblemDetail = { ... };

// Type guard
export function isApiError(data: unknown): data is ApiErrorResponse;
```

### Frontend usage pattern

```typescript
import { isApiError } from '@/api-errors';

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

### CI integration

Add to your CI pipeline to keep TypeScript in sync:

```yaml
# .github/workflows/ci.yml
- name: Check TypeScript error codes are up to date
  run: |
    php artisan api-errors:ts
    git diff --exit-code resources/js/api-errors.ts
```

---

## Swagger / OpenAPI Export

### Generate

```bash
php artisan api-errors:swagger
# or with custom path:
php artisan api-errors:swagger --path=docs/openapi/errors.json
```

Default output: `storage/api-docs/error-codes.json` (configurable via `swagger_path` in config).

### What it generates

An OpenAPI 3.1 JSON file containing:

- `components.schemas.ApiErrorCode` — string enum of all code values
- `components.schemas.ApiErrorResponse` — the default response object schema
- `components.schemas.RFC7807ProblemDetail` — the RFC 7807 response schema
- `x-error-code-details` — per-code metadata (http_status, domain, message, severity)

### Reference from your main OpenAPI spec

```yaml
# openapi.yaml
responses:
  '401':
    description: Unauthenticated
    content:
      application/json:
        schema:
          $ref: './error-codes.json#/components/schemas/ApiErrorResponse'
  '422':
    description: Validation Error
    content:
      application/json:
        schema:
          $ref: './error-codes.json#/components/schemas/ApiErrorResponse'
```

---

## Translation Sync

### Generate

```bash
php artisan api-errors:sync-translations --locale=en
php artisan api-errors:sync-translations --locale=de
php artisan api-errors:sync-translations --locale=es
```

Output: `lang/{locale}/api-errors.php`

### What it generates

A standard Laravel translation file:

```php
<?php
return [
    // — AUTH —
    'AUTH_UNAUTHENTICATED' => 'Authentication is required.',
    'AUTH_TOKEN_EXPIRED' => 'Your authentication token has expired.',

    // — BILLING —
    'BILLING_CARD_DECLINED' => 'The credit card was declined.',
];
```

### Workflow

1. Run `api-errors:sync-translations --locale=en` to generate the base file.
2. Copy and translate for other locales.
3. When you add new error codes, re-run the command — it regenerates the full file. **Back up your translations first**, or manage manually after initial generation.

### How translations are resolved

When an error response is built, the package checks `api-errors::messages.{CODE}` in the current locale. If the key exists, that translation is used. If not, the enum's `message()` method provides the fallback. This happens automatically — no code changes needed.

### Message interpolation

Translations support `:placeholder` syntax:

```php
// lang/en/api-errors.php
'BILLING_INSUFFICIENT_FUNDS' => 'Insufficient funds. Required: :amount.',
```

```php
BillingErrorCode::INSUFFICIENT_FUNDS->translatedMessage(['amount' => '$50.00']);
// → "Insufficient funds. Required: $50.00."
```

---

## Listing All Codes (Debugging)

```bash
# All codes
php artisan api-errors:list

# Filter by domain
php artisan api-errors:list --domain=BILLING
```

Outputs a table:

```
+---------------------------+------+---------+----------+-----------------------------------+
| Code                      | HTTP | Domain  | Severity | Message                           |
+---------------------------+------+---------+----------+-----------------------------------+
| BILLING_CARD_DECLINED     | 402  | BILLING | warning  | The credit card was declined.     |
| BILLING_INSUFFICIENT_FUNDS| 402  | BILLING | warning  | Insufficient funds.               |
+---------------------------+------+---------+----------+-----------------------------------+
```
