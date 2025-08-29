<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class PropertyHomeWarm extends Command
{
    /**
     * Keep the same signature so your existing schedule still works.
     */
    protected $signature = 'property:home-warm {--parallel=1} {--task=}';

    protected $description = 'Warm the PropertyController homepage cache (England & Wales, Category A aggregates only)';

    public function handle(): int
    {
        $this->info('Starting PropertyController home cache warm (EW Cat A only)...');

        // Reduce memory usage during large aggregations
        DB::connection()->disableQueryLog();

        // TTL in seconds (45 days)
        $ttl = 60 * 60 * 24 * 45;

        // If a specific task is provided, run only that (child mode)
        $task = (string) ($this->option('task') ?? '');
        if ($task !== '') {
            $this->runTask($task, $ttl);
            Cache::put('property:home:catA:last_warm', now()->toIso8601String(), $ttl);
            $this->info("Task '{$task}' complete.");
            return self::SUCCESS;
        }

        // Orchestrator mode: define the six independent tasks
        $tasks = ['sales','avgPrice','p90','top5','topSale','top3'];

        $parallel = max(1, (int) ($this->option('parallel') ?? 1));
        if ($parallel <= 1) {
            // Sequential behaviour (original)
            $this->withProgressBar($tasks, function (string $t) use ($ttl) {
                $this->runTask($t, $ttl);
            });
            $this->newLine(2);
            Cache::put('property:home:catA:last_warm', now()->toIso8601String(), $ttl);
            $this->info('PropertyController home cache warm complete (EW Cat A only).');
            return self::SUCCESS;
        }

        // Parallel: spawn up to N child processes, each running a single task
        $this->info("Running in parallel with up to {$parallel} workers...");

        $total = count($tasks);
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $maxWorkers = (int) min($parallel, 6); // safety cap (we only have 6 tasks)
        $queue = $tasks; // array of strings
        $running = [];

        $startWorker = function (string $t) use (&$running) {
            $php = PHP_BINARY;
            $artisan = base_path('artisan');
            $proc = new Process([$php, $artisan, 'property:home-warm', '--task='.$t]);
            $proc->setTimeout(null);
            $proc->disableOutput();
            $proc->start();
            $running[$t] = $proc;
        };

        // Prime the pool
        while (!empty($queue) && count($running) < $maxWorkers) {
            $startWorker(array_shift($queue));
        }

        // Event loop
        while (!empty($running)) {
            foreach ($running as $t => $proc) {
                if (!$proc->isRunning()) {
                    if ($proc->getExitCode() !== 0) {
                        $this->warn("Worker failed for task {$t} (exit code: {$proc->getExitCode()})");
                    }
                    unset($running[$t]);
                    $bar->advance();
                    if (!empty($queue)) {
                        $startWorker(array_shift($queue));
                    }
                }
            }
            usleep(100000); // 100ms
        }

        $bar->finish();
        $this->newLine(2);

        Cache::put('property:home:catA:last_warm', now()->toIso8601String(), $ttl);
        $this->info('PropertyController home cache warm complete (EW Cat A only).');

        return self::SUCCESS;
    }

    private function runTask(string $task, int $ttl): void
    {
        switch ($task) {
            case 'sales':
                $data = DB::table('land_registry')
                    ->selectRaw('YEAR(`Date`) as year, COUNT(*) as total')
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('year')->orderBy('year')->get();
                Cache::put('land_registry_sales_by_year:catA:v2', $data, $ttl);
                break;

            case 'avgPrice':
                $data = DB::table('land_registry')
                    ->selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                    ->where('PPDCategoryType', 'A')
                    ->groupBy('year')->orderBy('year')->get();
                Cache::put('land_registry_avg_price_by_year:catA:v2', $data, $ttl);
                break;

            case 'p90':
                $sub = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Price`, CUME_DIST() OVER (PARTITION BY `YearDate` ORDER BY `Price`) as cd')
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')->where('Price', '>', 0);
                $data = DB::query()->fromSub($sub, 't')
                    ->selectRaw('year, MIN(Price) as p90_price')
                    ->where('cd', '>=', 0.9)
                    ->groupBy('year')->orderBy('year')->get();
                Cache::put('ew:p90:catA:v1', $data, $ttl);
                break;

            case 'top5':
                $sub = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')->where('Price', '>', 0);
                $data = DB::query()->fromSub($sub, 'r')
                    ->selectRaw('year, ROUND(AVG(`Price`)) as top5_avg')
                    ->whereColumn('rn', '<=', DB::raw('CEIL(0.05*cnt)'))
                    ->groupBy('year')->orderBy('year')->get();
                Cache::put('ew:top5avg:catA:v1', $data, $ttl);
                break;

            case 'topSale':
                $data = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')->where('Price', '>', 0)
                    ->groupBy('YearDate')
                    ->orderBy('year')
                    ->get();
                Cache::put('ew:topSalePerYear:catA:v1', $data, $ttl);
                break;

            case 'top3':
                $rankedTop3 = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
                    ->where('PPDCategoryType', 'A')
                    ->whereNotNull('Price')->where('Price', '>', 0);
                $data = DB::query()
                    ->fromSub($rankedTop3, 'r')
                    ->select('year', 'Date', 'Postcode', 'Price', 'rn')
                    ->where('rn', '<=', 3)
                    ->orderBy('year')
                    ->orderBy('rn')
                    ->get();
                Cache::put('ew:top3PerYear:catA:v1', $data, $ttl);
                break;

            default:
                $this->warn("Unknown task '{$task}', skipping.");
        }
    }
}
