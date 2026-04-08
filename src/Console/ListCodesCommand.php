<?php

namespace LaravelApiErrors\Console;

use Illuminate\Console\Command;
use LaravelApiErrors\Support\ErrorCodeRegistry;

class ListCodesCommand extends Command
{
    protected $signature = 'api-errors:list {--domain= : Filter by domain}';
    protected $description = 'List all registered API error codes.';

    public function handle(ErrorCodeRegistry $registry): int
    {
        $domain = $this->option('domain');
        $codes  = $domain ? $registry->domain(strtoupper($domain)) : $registry->all();

        if (empty($codes)) {
            $this->warn('No error codes found.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($codes as $code => $case) {
            $rows[] = [
                $code,
                $case->httpStatus(),
                $case->domain(),
                $case->severity(),
                $case->message(),
            ];
        }

        $this->table(['Code', 'HTTP', 'Domain', 'Severity', 'Message'], $rows);

        return self::SUCCESS;
    }
}
