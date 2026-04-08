<?php

namespace LaravelApiErrors\Console;

use Illuminate\Console\Command;
use LaravelApiErrors\Support\ErrorCodeRegistry;

class ExportSwaggerCommand extends Command
{
    protected $signature = 'api-errors:swagger {--path= : Output file path}';
    protected $description = 'Export all registered API error codes as an OpenAPI-compatible JSON schema.';

    public function handle(ErrorCodeRegistry $registry): int
    {
        $path = $this->option('path') ?? config('api-errors.swagger_path', storage_path('api-docs/error-codes.json'));

        $codes = $registry->all();

        $enumValues = array_keys($codes);

        $schema = [
            'openapi' => '3.1.0',
            'info'    => [
                'title'       => 'API Error Codes',
                'description' => 'Auto-generated error code reference.',
                'version'     => '1.0.0',
            ],
            'components' => [
                'schemas' => [
                    'ApiErrorCode' => [
                        'type' => 'string',
                        'enum' => $enumValues,
                        'description' => 'All registered API error codes.',
                    ],
                    'ApiErrorResponse' => [
                        'type'       => 'object',
                        'required'   => ['success', 'error_code', 'message', 'status'],
                        'properties' => [
                            'success'    => ['type' => 'boolean', 'example' => false],
                            'error_code' => ['$ref' => '#/components/schemas/ApiErrorCode'],
                            'message'    => ['type' => 'string'],
                            'domain'     => ['type' => 'string'],
                            'status'     => ['type' => 'integer'],
                            'request_id' => ['type' => 'string', 'format' => 'uuid'],
                            'context'    => ['type' => 'object', 'additionalProperties' => true],
                        ],
                    ],
                    'RFC7807ProblemDetail' => [
                        'type'       => 'object',
                        'required'   => ['type', 'title', 'status', 'detail'],
                        'properties' => [
                            'type'       => ['type' => 'string', 'format' => 'uri'],
                            'title'      => ['$ref' => '#/components/schemas/ApiErrorCode'],
                            'status'     => ['type' => 'integer'],
                            'detail'     => ['type' => 'string'],
                            'instance'   => ['type' => 'string'],
                            'request_id' => ['type' => 'string', 'format' => 'uuid'],
                            'extensions' => ['type' => 'object', 'additionalProperties' => true],
                        ],
                    ],
                ],
            ],
            'x-error-code-details' => [],
        ];

        foreach ($codes as $code => $case) {
            $schema['x-error-code-details'][$code] = [
                'http_status' => $case->httpStatus(),
                'domain'      => $case->domain(),
                'message'     => $case->message(),
                'severity'    => $case->severity(),
            ];
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("Swagger schema exported to: {$path}");

        return self::SUCCESS;
    }
}
