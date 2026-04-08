<?php

namespace LaravelApiErrors\Console;

use Illuminate\Console\Command;
use LaravelApiErrors\Support\ErrorCodeRegistry;

class SyncTranslationsCommand extends Command
{
    protected $signature = 'api-errors:sync-translations {--locale=en : The locale to generate}';
    protected $description = 'Generate a translation file with all registered error codes.';

    public function handle(ErrorCodeRegistry $registry): int
    {
        $locale = $this->option('locale');
        $codes  = $registry->all();

        $lines = ["<?php\n", "return ["];

        foreach ($registry->groupedByDomain() as $domain => $domainCodes) {
            $lines[] = "\n    // — {$domain} —";
            foreach ($domainCodes as $code => $case) {
                $escaped = addslashes($case->message());
                $lines[] = "    '{$code}' => '{$escaped}',";
            }
        }

        $lines[] = "\n];\n";

        $path = lang_path("{$locale}/api-errors.php");
        $dir  = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, implode("\n", $lines));

        $this->info("Translation file written to: {$path}");
        $this->info(count($codes) . ' entries.');

        return self::SUCCESS;
    }
}
