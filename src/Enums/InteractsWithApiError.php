<?php

namespace LaravelApiErrors\Enums;

trait InteractsWithApiError
{
    /*
    |--------------------------------------------------------------------------
    | Convenience helpers that any enum using this trait inherits.
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve a translated message, falling back to the default.
     */
    public function translatedMessage(array $replace = [], ?string $locale = null): string
    {
        if (! config('api-errors.use_translations', true)) {
            return $this->interpolate($this->message(), $replace);
        }

        $key = config('api-errors.translation_namespace', 'api-errors') . '::messages.' . $this->code();

        $translated = __($key, $replace, $locale);

        // If the translation system returns the key itself, fall back.
        if ($translated === $key) {
            return $this->interpolate($this->message(), $replace);
        }

        return $translated;
    }

    /**
     * Build an ApiException from this error code.
     */
    public function exception(array $context = [], ?\Throwable $previous = null): \LaravelApiErrors\Exceptions\ApiException
    {
        return new \LaravelApiErrors\Exceptions\ApiException($this, $context, $previous);
    }

    /**
     * Throw immediately.
     */
    public function throw(array $context = [], ?\Throwable $previous = null): never
    {
        throw $this->exception($context, $previous);
    }

    /**
     * Build a JSON response without throwing.
     */
    public function respond(array $context = [], array $headers = []): \Illuminate\Http\JsonResponse
    {
        return \LaravelApiErrors\Http\Responses\ApiErrorResponse::make($this, $context, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    private function interpolate(string $message, array $replace): string
    {
        foreach ($replace as $key => $value) {
            $message = str_replace([':' . $key, '{' . $key . '}'], (string) $value, $message);
        }

        return $message;
    }
}
