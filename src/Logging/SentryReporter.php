<?php

namespace LaravelApiErrors\Logging;

use LaravelApiErrors\Contracts\ApiErrorCode;

class SentryReporter
{
    public static function capture(ApiErrorCode $errorCode, \Throwable $exception, array $context = []): void
    {
        if (! function_exists('\Sentry\captureException')) {
            return;
        }

        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($errorCode, $context) {
            if (config('api-errors.sentry.set_tags', true)) {
                $scope->setTag('error_code', $errorCode->code());
                $scope->setTag('error_domain', $errorCode->domain());
                $scope->setTag('http_status', (string) $errorCode->httpStatus());

                $requestId = request()?->header(config('api-errors.request_id_header', 'X-Request-Id'));
                if ($requestId) {
                    $scope->setTag('request_id', $requestId);
                }
            }

            $scope->setContext('api_error', array_merge($context, [
                'code'    => $errorCode->code(),
                'domain'  => $errorCode->domain(),
                'message' => $errorCode->message(),
            ]));

            $scope->setLevel(self::mapSeverity($errorCode->severity()));
        });

        \Sentry\captureException($exception);
    }

    protected static function mapSeverity(string $severity): \Sentry\Severity
    {
        return match ($severity) {
            'debug'   => \Sentry\Severity::debug(),
            'info'    => \Sentry\Severity::info(),
            'warning' => \Sentry\Severity::warning(),
            'fatal'   => \Sentry\Severity::fatal(),
            default   => \Sentry\Severity::error(),
        };
    }
}
