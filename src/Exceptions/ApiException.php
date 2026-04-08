<?php

namespace LaravelApiErrors\Exceptions;

use Illuminate\Http\JsonResponse;
use LaravelApiErrors\Contracts\ApiErrorCode;
use LaravelApiErrors\Http\Responses\ApiErrorResponse;

class ApiException extends \RuntimeException
{
    public function __construct(
        protected ApiErrorCode $errorCode,
        protected array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $this->errorCode->message(),
            $this->errorCode->httpStatus(),
            $previous,
        );
    }

    public function getErrorCode(): ApiErrorCode
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Laravel's exception handler calls this automatically.
     */
    public function render($request): JsonResponse
    {
        return ApiErrorResponse::make($this->errorCode, $this->context);
    }

    /**
     * Report context for Laravel's logger.
     */
    public function context(): array
    {
        return array_merge($this->context, [
            'error_code' => $this->errorCode->code(),
            'domain'     => $this->errorCode->domain(),
        ]);
    }
}
