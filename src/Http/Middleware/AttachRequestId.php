<?php

namespace LaravelApiErrors\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttachRequestId
{
    public function handle(Request $request, Closure $next)
    {
        $header = config('api-errors.request_id_header', 'X-Request-Id');

        if (! $request->hasHeader($header) && config('api-errors.auto_request_id', true)) {
            $request->headers->set($header, (string) Str::uuid());
        }

        $response = $next($request);

        if (config('api-errors.request_id_in_response', true)) {
            $response->headers->set($header, $request->header($header));
        }

        return $response;
    }
}
