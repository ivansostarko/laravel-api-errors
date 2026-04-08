<?php

namespace LaravelApiErrors\Contracts;

interface ApiErrorCode
{
    /**
     * The unique, stable string code sent to frontends (e.g. "AUTH_INVALID_TOKEN").
     */
    public function code(): string;

    /**
     * Default human-readable message (before translation).
     */
    public function message(): string;

    /**
     * HTTP status code.
     */
    public function httpStatus(): int;

    /**
     * Logical domain grouping (e.g. "AUTH", "BILLING").
     */
    public function domain(): string;

    /**
     * Sentry severity / log level override (debug, info, warning, error, fatal).
     */
    public function severity(): string;
}
