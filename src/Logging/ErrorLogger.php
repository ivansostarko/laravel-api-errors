<?php

namespace LaravelApiErrors\Logging;

use Illuminate\Support\Facades\Log;
use LaravelApiErrors\Contracts\ApiErrorCode;

class ErrorLogger
{
    public static function log(ApiErrorCode $errorCode, array $context = [], ?\Throwable $exception = null): void
    {
        if (! config('api-errors.logging.enabled', true)) {
            return;
        }

        $excluded = config('api-errors.logging.exclude_status', []);
        if (in_array($errorCode->httpStatus(), $excluded, true)) {
            return;
        }

        $channel = config('api-errors.logging.channel');
        $level   = $errorCode->severity();

        $data = [
            'error_code'  => $errorCode->code(),
            'domain'      => $errorCode->domain(),
            'http_status' => $errorCode->httpStatus(),
            'request_id'  => request()?->header(config('api-errors.request_id_header', 'X-Request-Id')),
            'url'         => request()?->fullUrl(),
            'method'      => request()?->method(),
            'context'     => $context,
        ];

        if ($exception) {
            $data['exception_class'] = get_class($exception);
            $data['exception_message'] = $exception->getMessage();
        }

        $logger = $channel ? Log::channel($channel) : Log::getFacadeRoot();
        $logger->{$level}("[API Error] {$errorCode->code()}: {$errorCode->message()}", $data);

        // Sentry
        if (config('api-errors.sentry.enabled') && $exception) {
            SentryReporter::capture($errorCode, $exception, $context);
        }
    }
}
