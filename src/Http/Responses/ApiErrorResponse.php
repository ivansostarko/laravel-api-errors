<?php

namespace LaravelApiErrors\Http\Responses;

use Illuminate\Http\JsonResponse;
use LaravelApiErrors\Contracts\ApiErrorCode;

class ApiErrorResponse
{
    /**
     * Build a JSON error response from an ApiErrorCode.
     */
    public static function make(
        ApiErrorCode $errorCode,
        array $context = [],
        array $headers = [],
    ): JsonResponse {
        $format = config('api-errors.format', 'default');

        $body = match ($format) {
            'rfc7807' => self::rfc7807($errorCode, $context),
            default   => self::default($errorCode, $context),
        };

        // Attach request ID
        $requestId = self::requestId();
        if ($requestId && config('api-errors.request_id_in_response', true)) {
            $body['request_id'] = $requestId;
            $headers[config('api-errors.request_id_header', 'X-Request-Id')] = $requestId;
        }

        // Debug info
        if (config('api-errors.debug') && config('app.debug')) {
            $body['debug'] = self::debug();
        }

        $contentType = $format === 'rfc7807'
            ? 'application/problem+json'
            : 'application/json';

        $headers['Content-Type'] = $contentType;

        return new JsonResponse($body, $errorCode->httpStatus(), $headers);
    }

    /**
     * Build response for validation errors.
     */
    public static function validation(
        ApiErrorCode $errorCode,
        array $errors,
        array $headers = [],
    ): JsonResponse {
        $context = ['errors' => self::flattenValidation($errors)];

        return self::make($errorCode, $context, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | Formats
    |--------------------------------------------------------------------------
    */

    protected static function default(ApiErrorCode $errorCode, array $context): array
    {
        $payload = [
            'success'    => false,
            'error_code' => $errorCode->code(),
            'message'    => self::resolveMessage($errorCode),
            'domain'     => $errorCode->domain(),
            'status'     => $errorCode->httpStatus(),
        ];

        if (! empty($context)) {
            $payload['context'] = $context;
        }

        return $payload;
    }

    protected static function rfc7807(ApiErrorCode $errorCode, array $context): array
    {
        $payload = [
            'type'     => 'https://api-errors.dev/codes/' . strtolower($errorCode->code()),
            'title'    => $errorCode->code(),
            'status'   => $errorCode->httpStatus(),
            'detail'   => self::resolveMessage($errorCode),
            'instance' => request()?->getRequestUri() ?? '/',
        ];

        if (! empty($context)) {
            $payload['extensions'] = $context;
        }

        return $payload;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected static function resolveMessage(ApiErrorCode $errorCode): string
    {
        if (method_exists($errorCode, 'translatedMessage')) {
            return $errorCode->translatedMessage();
        }

        return $errorCode->message();
    }

    protected static function requestId(): ?string
    {
        return request()?->header(config('api-errors.request_id_header', 'X-Request-Id'));
    }

    protected static function debug(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        return [
            'trace' => collect($trace)->map(fn ($f) => ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '') . ' (' . ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?') . ')')->toArray(),
        ];
    }

    /**
     * Flatten Laravel validation errors into [{field, message, rule?}] format.
     */
    protected static function flattenValidation(array $errors): array
    {
        $flat = [];
        foreach ($errors as $field => $messages) {
            foreach ((array) $messages as $msg) {
                $flat[] = [
                    'field'   => $field,
                    'message' => $msg,
                ];
            }
        }

        return $flat;
    }
}
