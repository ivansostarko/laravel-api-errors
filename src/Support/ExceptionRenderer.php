<?php

namespace LaravelApiErrors\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelApiErrors\Enums\DefaultErrorCode;
use LaravelApiErrors\Exceptions\ApiException;
use LaravelApiErrors\Http\Responses\ApiErrorResponse;
use LaravelApiErrors\Logging\ErrorLogger;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ExceptionRenderer
{
    /**
     * Register renderable callbacks on the Laravel exception handler.
     *
     * Call this in bootstrap/app.php:
     *   ->withExceptions(function (Exceptions $exceptions) {
     *       \LaravelApiErrors\Support\ExceptionRenderer::register($exceptions);
     *   })
     */
    public static function register(object $exceptions): void
    {
        // ApiException — our own
        $exceptions->renderable(function (ApiException $e, Request $request) {
            if (! $request->expectsJson() && ! self::isApiRequest($request)) {
                return null;
            }

            ErrorLogger::log($e->getErrorCode(), $e->getContext(), $e);

            return $e->render($request);
        });

        // Validation
        $exceptions->renderable(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson() && ! self::isApiRequest($request)) {
                return null;
            }

            $code = self::resolveOrDefault(
                config('api-errors.validation_error_code', 'VALIDATION_ERROR'),
                DefaultErrorCode::VALIDATION_ERROR,
            );

            return ApiErrorResponse::validation($code, $e->errors());
        });

        // Auth
        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson() && ! self::isApiRequest($request)) {
                return null;
            }

            return DefaultErrorCode::AUTH_UNAUTHENTICATED->respond();
        });

        // Model not found
        $exceptions->renderable(function (ModelNotFoundException $e, Request $request) {
            if (! $request->expectsJson() && ! self::isApiRequest($request)) {
                return null;
            }

            return DefaultErrorCode::RESOURCE_NOT_FOUND->respond([
                'model' => class_basename($e->getModel()),
            ]);
        });

        // 404
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson() && ! self::isApiRequest($request)) {
                return null;
            }

            return DefaultErrorCode::RESOURCE_NOT_FOUND->respond();
        });

        // 405
        $exceptions->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            if (! $request->expectsJson() && ! self::isApiRequest($request)) {
                return null;
            }

            return DefaultErrorCode::METHOD_NOT_ALLOWED->respond();
        });

        // 429
        $exceptions->renderable(function (TooManyRequestsHttpException $e, Request $request) {
            if (! $request->expectsJson() && ! self::isApiRequest($request)) {
                return null;
            }

            return DefaultErrorCode::TOO_MANY_REQUESTS->respond();
        });

        // Generic HTTP exceptions
        $exceptions->renderable(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->expectsJson() && ! self::isApiRequest($request)) {
                return null;
            }

            $code = DefaultErrorCode::tryFrom('INTERNAL_SERVER_ERROR') ?? DefaultErrorCode::INTERNAL_SERVER_ERROR;

            return $code->respond(['http_status' => $e->getStatusCode()]);
        });
    }

    protected static function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->is('*/api/*');
    }

    protected static function resolveOrDefault(string $codeString, DefaultErrorCode $fallback): \LaravelApiErrors\Contracts\ApiErrorCode
    {
        $registry = app(ErrorCodeRegistry::class);
        return $registry->resolve($codeString) ?? $fallback;
    }
}
