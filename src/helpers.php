<?php

use LaravelApiErrors\Contracts\ApiErrorCode;
use LaravelApiErrors\Exceptions\ApiException;
use LaravelApiErrors\Http\Responses\ApiErrorResponse;
use LaravelApiErrors\Support\ErrorCodeRegistry;

if (! function_exists('api_error')) {
    /**
     * Return a JSON error response for the given error code.
     *
     * @param  ApiErrorCode|string  $code  Enum case or code string
     */
    function api_error(ApiErrorCode|string $code, array $context = [], array $headers = []): \Illuminate\Http\JsonResponse
    {
        $resolved = $code instanceof ApiErrorCode ? $code : app(ErrorCodeRegistry::class)->resolve($code);

        if (! $resolved) {
            throw new \InvalidArgumentException("Unknown API error code [{$code}].");
        }

        return ApiErrorResponse::make($resolved, $context, $headers);
    }
}

if (! function_exists('api_abort')) {
    /**
     * Throw an ApiException for the given error code.
     *
     * @param  ApiErrorCode|string  $code
     */
    function api_abort(ApiErrorCode|string $code, array $context = [], ?\Throwable $previous = null): never
    {
        $resolved = $code instanceof ApiErrorCode ? $code : app(ErrorCodeRegistry::class)->resolve($code);

        if (! $resolved) {
            throw new \InvalidArgumentException("Unknown API error code [{$code}].");
        }

        throw new ApiException($resolved, $context, $previous);
    }
}

if (! function_exists('api_error_code')) {
    /**
     * Resolve a code string to its ApiErrorCode enum case.
     */
    function api_error_code(string $code): ?ApiErrorCode
    {
        return app(ErrorCodeRegistry::class)->resolve($code);
    }
}
