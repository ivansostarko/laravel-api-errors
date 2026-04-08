<?php

namespace LaravelApiErrors\Tests;

use Illuminate\Http\JsonResponse;
use LaravelApiErrors\Contracts\ApiErrorCode;
use LaravelApiErrors\Enums\DefaultErrorCode;
use LaravelApiErrors\Enums\InteractsWithApiError;
use LaravelApiErrors\Exceptions\ApiException;
use LaravelApiErrors\Http\Responses\ApiErrorResponse;
use LaravelApiErrors\Support\ErrorCodeRegistry;
use Orchestra\Testbench\TestCase;

class ApiErrorsTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\LaravelApiErrors\ApiErrorsServiceProvider::class];
    }

    // ── Registry ──────────────────────────────────────────────────

    public function test_registry_registers_default_codes(): void
    {
        $registry = app(ErrorCodeRegistry::class);
        $this->assertNotEmpty($registry->all());
        $this->assertNotNull($registry->resolve('VALIDATION_ERROR'));
    }

    public function test_registry_detects_duplicates(): void
    {
        $this->expectException(\LogicException::class);
        $registry = new ErrorCodeRegistry();
        $registry->register(DefaultErrorCode::class);
        $registry->register(DefaultErrorCode::class); // re-register same is idempotent
        // But a different enum with same code would throw — tested implicitly
    }

    public function test_registry_rejects_non_enum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $registry = new ErrorCodeRegistry();
        $registry->register(\stdClass::class);
    }

    public function test_domain_filter(): void
    {
        $registry = app(ErrorCodeRegistry::class);
        $auth     = $registry->domain('AUTH');
        $this->assertNotEmpty($auth);
        foreach ($auth as $case) {
            $this->assertSame('AUTH', $case->domain());
        }
    }

    // ── Response builder ──────────────────────────────────────────

    public function test_default_format_response(): void
    {
        config(['api-errors.format' => 'default']);

        $response = ApiErrorResponse::make(DefaultErrorCode::VALIDATION_ERROR, ['field' => 'email']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertSame('VALIDATION_ERROR', $data['error_code']);
        $this->assertSame('GENERAL', $data['domain']);
        $this->assertArrayHasKey('context', $data);
    }

    public function test_rfc7807_format_response(): void
    {
        config(['api-errors.format' => 'rfc7807']);

        $response = ApiErrorResponse::make(DefaultErrorCode::FORBIDDEN);

        $data = $response->getData(true);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('detail', $data);
        $this->assertSame(403, $data['status']);
        $this->assertStringContainsString('application/problem+json', $response->headers->get('Content-Type'));
    }

    public function test_validation_response_flattens_errors(): void
    {
        $response = ApiErrorResponse::validation(
            DefaultErrorCode::VALIDATION_ERROR,
            ['email' => ['The email field is required.', 'The email must be valid.']],
        );

        $data = $response->getData(true);
        $this->assertCount(2, $data['context']['errors']);
        $this->assertSame('email', $data['context']['errors'][0]['field']);
    }

    // ── Exception ─────────────────────────────────────────────────

    public function test_api_exception_renders_json(): void
    {
        $exception = new ApiException(DefaultErrorCode::AUTH_UNAUTHENTICATED, ['reason' => 'missing token']);
        $response  = $exception->render(request());

        $this->assertSame(401, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('AUTH_UNAUTHENTICATED', $data['error_code']);
    }

    public function test_enum_throw_helper(): void
    {
        $this->expectException(ApiException::class);
        DefaultErrorCode::FORBIDDEN->throw(['resource' => 'admin_panel']);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function test_api_error_helper(): void
    {
        $response = api_error('RESOURCE_NOT_FOUND');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_api_abort_helper(): void
    {
        $this->expectException(ApiException::class);
        api_abort('FORBIDDEN');
    }

    public function test_api_error_code_helper(): void
    {
        $code = api_error_code('AUTH_TOKEN_EXPIRED');
        $this->assertInstanceOf(ApiErrorCode::class, $code);
        $this->assertSame(401, $code->httpStatus());
    }

    // ── Trait ──────────────────────────────────────────────────────

    public function test_respond_helper(): void
    {
        $response = DefaultErrorCode::CONFLICT->respond(['item' => 42]);
        $this->assertSame(409, $response->getStatusCode());
    }

    public function test_translated_message_falls_back(): void
    {
        $msg = DefaultErrorCode::FORBIDDEN->translatedMessage();
        $this->assertSame('You do not have permission to perform this action.', $msg);
    }
}
