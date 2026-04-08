<?php

namespace LaravelApiErrors\Enums;

use LaravelApiErrors\Contracts\ApiErrorCode;

/**
 * Default error codes that ship with the package.
 * Applications should create their own enums implementing ApiErrorCode
 * and register them via config('api-errors.extra_enums').
 */
enum DefaultErrorCode: string implements ApiErrorCode
{
    use InteractsWithApiError;

    /*
    |--------------------------------------------------------------------------
    | Generic
    |--------------------------------------------------------------------------
    */
    case UNKNOWN_ERROR          = 'UNKNOWN_ERROR';
    case VALIDATION_ERROR       = 'VALIDATION_ERROR';
    case RESOURCE_NOT_FOUND     = 'RESOURCE_NOT_FOUND';
    case METHOD_NOT_ALLOWED     = 'METHOD_NOT_ALLOWED';
    case TOO_MANY_REQUESTS      = 'TOO_MANY_REQUESTS';
    case SERVICE_UNAVAILABLE    = 'SERVICE_UNAVAILABLE';
    case INTERNAL_SERVER_ERROR  = 'INTERNAL_SERVER_ERROR';
    case FORBIDDEN              = 'FORBIDDEN';
    case CONFLICT               = 'CONFLICT';

    /*
    |--------------------------------------------------------------------------
    | Authentication / Authorization
    |--------------------------------------------------------------------------
    */
    case AUTH_UNAUTHENTICATED   = 'AUTH_UNAUTHENTICATED';
    case AUTH_TOKEN_EXPIRED     = 'AUTH_TOKEN_EXPIRED';
    case AUTH_TOKEN_INVALID     = 'AUTH_TOKEN_INVALID';
    case AUTH_INSUFFICIENT_ROLE = 'AUTH_INSUFFICIENT_ROLE';

    /*
    |--------------------------------------------------------------------------
    | Contract implementations
    |--------------------------------------------------------------------------
    */

    public function code(): string
    {
        return $this->value;
    }

    public function message(): string
    {
        return match ($this) {
            self::UNKNOWN_ERROR          => 'An unexpected error occurred.',
            self::VALIDATION_ERROR       => 'The given data was invalid.',
            self::RESOURCE_NOT_FOUND     => 'The requested resource was not found.',
            self::METHOD_NOT_ALLOWED     => 'The HTTP method is not allowed for this endpoint.',
            self::TOO_MANY_REQUESTS      => 'Too many requests. Please try again later.',
            self::SERVICE_UNAVAILABLE    => 'The service is temporarily unavailable.',
            self::INTERNAL_SERVER_ERROR  => 'Internal server error.',
            self::FORBIDDEN              => 'You do not have permission to perform this action.',
            self::CONFLICT               => 'The request conflicts with the current state.',
            self::AUTH_UNAUTHENTICATED   => 'Authentication is required.',
            self::AUTH_TOKEN_EXPIRED     => 'Your authentication token has expired.',
            self::AUTH_TOKEN_INVALID     => 'The authentication token is invalid.',
            self::AUTH_INSUFFICIENT_ROLE => 'You do not have the required role.',
        };
    }

    public function httpStatus(): int
    {
        return match ($this) {
            self::UNKNOWN_ERROR          => 500,
            self::VALIDATION_ERROR       => 422,
            self::RESOURCE_NOT_FOUND     => 404,
            self::METHOD_NOT_ALLOWED     => 405,
            self::TOO_MANY_REQUESTS      => 429,
            self::SERVICE_UNAVAILABLE    => 503,
            self::INTERNAL_SERVER_ERROR  => 500,
            self::FORBIDDEN              => 403,
            self::CONFLICT               => 409,
            self::AUTH_UNAUTHENTICATED   => 401,
            self::AUTH_TOKEN_EXPIRED     => 401,
            self::AUTH_TOKEN_INVALID     => 401,
            self::AUTH_INSUFFICIENT_ROLE => 403,
        };
    }

    public function domain(): string
    {
        return match ($this) {
            self::AUTH_UNAUTHENTICATED,
            self::AUTH_TOKEN_EXPIRED,
            self::AUTH_TOKEN_INVALID,
            self::AUTH_INSUFFICIENT_ROLE => 'AUTH',
            default                      => 'GENERAL',
        };
    }

    public function severity(): string
    {
        return match ($this) {
            self::INTERNAL_SERVER_ERROR,
            self::UNKNOWN_ERROR,
            self::SERVICE_UNAVAILABLE => 'error',
            self::VALIDATION_ERROR    => 'info',
            default                   => 'warning',
        };
    }
}
