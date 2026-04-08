<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Response Format
    |--------------------------------------------------------------------------
    |
    | Choose between 'default' (flat JSON) or 'rfc7807' (Problem Details).
    | You may also set the env variable API_ERRORS_FORMAT.
    |
    */
    'format' => env('API_ERRORS_FORMAT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Include Debug Info
    |--------------------------------------------------------------------------
    |
    | When true (and APP_DEBUG is true), responses include exception class,
    | file, line, and trace. NEVER enable in production.
    |
    */
    'debug' => env('API_ERRORS_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Request ID Header
    |--------------------------------------------------------------------------
    |
    | The header name used for distributed request tracing.
    |
    */
    'request_id_header' => env('API_ERRORS_REQUEST_ID_HEADER', 'X-Request-Id'),

    /*
    |--------------------------------------------------------------------------
    | Auto-generate Request ID
    |--------------------------------------------------------------------------
    |
    | If no request ID is present in the incoming request, generate one
    | automatically using UUID v4.
    |
    */
    'auto_request_id' => true,

    /*
    |--------------------------------------------------------------------------
    | Attach Request ID to Response
    |--------------------------------------------------------------------------
    */
    'request_id_in_response' => true,

    /*
    |--------------------------------------------------------------------------
    | Translation Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace used for looking up error code translations.
    | Translations are resolved as: {namespace}::{error_code}
    |
    */
    'translation_namespace' => 'api-errors',

    /*
    |--------------------------------------------------------------------------
    | Use Translations
    |--------------------------------------------------------------------------
    |
    | When true the package will attempt to resolve a translated message
    | for every error code before falling back to the enum default.
    |
    */
    'use_translations' => true,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => env('API_ERRORS_LOG_CHANNEL', null), // null = default channel
        'level'   => 'error',

        // HTTP status codes that should NOT be logged (e.g. 404, 422).
        'exclude_status' => [404, 422],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sentry Integration
    |--------------------------------------------------------------------------
    */
    'sentry' => [
        'enabled'           => env('API_ERRORS_SENTRY', false),
        'capture_exceptions' => true,
        'set_tags'          => true, // Adds error_code + domain as Sentry tags.
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Error Code
    |--------------------------------------------------------------------------
    |
    | The error code string used when a ValidationException is caught.
    |
    */
    'validation_error_code' => 'VALIDATION_ERROR',

    /*
    |--------------------------------------------------------------------------
    | Domain Prefix Separator
    |--------------------------------------------------------------------------
    |
    | Separator between domain prefix and error slug.
    | e.g. AUTH_INVALID_TOKEN  →  domain=AUTH, slug=INVALID_TOKEN
    |
    */
    'domain_separator' => '_',

    /*
    |--------------------------------------------------------------------------
    | Extra Error Code Enums (Microservice / Multi-Module)
    |--------------------------------------------------------------------------
    |
    | Register additional enums here. Each must implement ApiErrorCode.
    | The package merges them into the central registry at boot.
    |
    */
    'extra_enums' => [
        // \App\Enums\BillingErrorCode::class,
        // \App\Enums\InventoryErrorCode::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | TypeScript Export Path
    |--------------------------------------------------------------------------
    |
    | Where the `api-errors:ts` command writes the generated .ts file.
    |
    */
    'typescript_path' => resource_path('js/api-errors.ts'),

    /*
    |--------------------------------------------------------------------------
    | Swagger / OpenAPI Export Path
    |--------------------------------------------------------------------------
    */
    'swagger_path' => storage_path('api-docs/error-codes.json'),

];
