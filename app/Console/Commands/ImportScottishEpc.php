<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportScottishEpc extends Command
{
    protected $signature = 'epc:import-scotland {path : Path to folder containing CSV files} {--scan-only : Scan headers only and do not import} {--write-migration= : Write a generated migration stub to this file path}';
    protected $description = 'Bulk import Scottish EPC quarterly CSV files into epc_certificates_scotland';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return self::FAILURE;
        }

        // Ensure LOCAL INFILE is enabled
        DB::statement("SET GLOBAL local_infile = 1");

        $files = glob(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.csv');
        if (empty($files)) {
            $this->warn("No CSV files found in {$path}");
            return self::SUCCESS;
        }

        if ($this->option('scan-only')) {
            $union = [];
            $seen = [];
            $perFile = [];

            foreach ($files as $file) {
                $fh = fopen($file, 'r');
                if ($fh === false) { $this->warn("Cannot open: {$file}"); continue; }
                $h1 = fgets($fh);
                $h2 = fgets($fh);
                fclose($fh);
                if ($h1 === false) { $this->warn("No header in: {$file}"); continue; }
                $cols = str_getcsv(trim($h1));
                $perFile[basename($file)] = $cols;
                foreach ($cols as $c) {
                    if (!isset($seen[$c])) {
                        $seen[$c] = true;
                        $union[] = $c;
                    }
                }
            }

            $this->info("Scanned " . count($perFile) . " file(s).");
            $this->info("Union column count: " . count($union));

            // Report columns that vary
            $varying = [];
            foreach ($union as $col) {
                $presentIn = 0;
                foreach ($perFile as $cols) { if (in_array($col, $cols, true)) $presentIn++; }
                if ($presentIn !== count($perFile)) {
                    $varying[$col] = $presentIn;
                }
            }
            if (!empty($varying)) {
                $this->line("Columns that vary across quarters:");
                foreach ($varying as $col => $cnt) {
                    $this->line(" - {$col}: {$cnt}/" . count($perFile));
                }
            }

            if ($path = $this->option('write-migration')) {
                $stub = $this->buildMigrationStub($union);
                if (@file_put_contents($path, $stub) === false) {
                    $this->error("Failed to write migration stub to: {$path}");
                    return self::FAILURE;
                }
                $this->info("Migration stub written to: {$path}");
            }

            return self::SUCCESS;
        }

        // Get existing target table columns once
        $tableCols = DB::select(<<<SQL
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'epc_certificates_scotland'
        SQL);
        $tableColNames = array_map(fn($r) => $r->COLUMN_NAME, $tableCols);

        foreach ($files as $file) {
            // Parse this file's header (Row 1 is machine names; Row 2 human-readable)
            $fh = fopen($file, 'r');
            if ($fh === false) {
                $this->error("Unable to open CSV: {$file}");
                return self::FAILURE;
            }
            $header1 = fgets($fh); // machine header
            $header2 = fgets($fh); // human header (ignored)
            fclose($fh);
            if ($header1 === false) {
                $this->error("No header row in: {$file}");
                return self::FAILURE;
            }
            $columns = str_getcsv(trim($header1));

            // Build token list preserving this file's column order. Unknown columns map to user vars to keep alignment.
            $missing = [];
            $tokens = [];
            foreach ($columns as $c) {
                if (in_array($c, $tableColNames, true)) {
                    $tokens[] = "`{$c}`";
                } else {
                    $missing[] = $c;
                    $tokens[] = '@skip_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $c);
                }
            }
            if (!empty($missing)) {
                $this->warn('Skipping unrecognised columns in ' . basename($file) . ':');
                foreach ($missing as $m) { $this->line(' - ' . $m); }
            }

            $columnsSql = implode(', ', $tokens);

            $base = basename($file);
            // Detect line endings (some quarters are CRLF)
            $sample = @file_get_contents($file, false, null, 0, 200000) ?: '';
            $lineTerm = (strpos($sample, "\r\n") !== false) ? "\\r\\n" : "\\n";

            $this->info("Importing {$base}...");

            $sql = "
                LOAD DATA LOCAL INFILE '" . addslashes($file) . "'
                INTO TABLE epc_certificates_scotland
                CHARACTER SET utf8mb4
                FIELDS TERMINATED BY ','
                ENCLOSED BY '\"'
                ESCAPED BY '\\\\'
                LINES TERMINATED BY '{$lineTerm}'
                IGNORE 2 LINES
                ({$columnsSql})
                SET source_file = '{$base}'
            ";

            $this->line(" - line endings: " . ($lineTerm === "\\r\\n" ? "CRLF" : "LF"));

            try {
                DB::connection()->getPdo()->exec($sql);
            } catch (\Exception $e) {
                $this->error("Failed on {$base}: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->info('All files imported.');
        return self::SUCCESS;
    }

    private function buildMigrationStub(array $columns): string
    {
        $stringCols = ['POSTCODE', 'REPORT_REFERENCE_NUMBER', 'LODGEMENT_DATE'];
        $lines = [];
        foreach ($columns as $c) {
            if (in_array($c, $stringCols, true)) {
                $lines[] = "            $" . "table->string('{$c}')->nullable();";
            } else {
                $lines[] = "            $" . "table->text('{$c}')->nullable();";
            }
        }

        $colsText = implode("\n", $lines);
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epc_certificates_scotland', function (Blueprint \$table) {
            \$table->id();
{$colsText}
            \$table->string('source_file')->nullable();
            \$table->index('POSTCODE');
            \$table->index('REPORT_REFERENCE_NUMBER');
            \$table->index('LODGEMENT_DATE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epc_certificates_scotland');
    }
};
PHP;
    }
}
