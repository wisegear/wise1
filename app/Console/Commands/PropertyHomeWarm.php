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

        // Orchestrator mode: define the seven independent tasks
        $tasks = ['sales','avgPrice','p90','top5','topSale','top3','monthly24','typeSplit','newBuildSplit','durationSplit','avgPriceByType'];

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

        $maxWorkers = (int) min($parallel, 11); // safety cap (we have 11 tasks)
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

            case 'monthly24':
                // Monthly sales — last 24 months (England & Wales, Cat A)
                // Build a slightly wider seed window, then trim to last available month and take 24 months
                $seedMonths = 36;
                $seedStart  = now()->startOfMonth()->subMonths($seedMonths - 1);
                $seedEnd    = now()->startOfMonth();

                $raw = DB::table('land_registry')
                    ->selectRaw("DATE_FORMAT(`Date`, '%Y-%m-01') as month_start, COUNT(*) as sales")
                    ->where('PPDCategoryType', 'A')
                    ->whereDate('Date', '>=', $seedStart)
                    ->groupBy('month_start')
                    ->orderBy('month_start')
                    ->pluck('sales', 'month_start')
                    ->toArray();

                // Determine last month with data
                $keys = array_keys($raw);
                if (!empty($keys)) {
                    sort($keys); // ascending
                    $lastDataKey = end($keys); // e.g., '2025-08-01'
                    $seriesEnd = \Carbon\Carbon::createFromFormat('Y-m-d', $lastDataKey)->startOfMonth();
                } else {
                    // If nothing in window, use end of previous month
                    $seriesEnd = $seedEnd->copy()->subMonth();
                }

                // Build exactly 24 months ending at last available month
                $start = $seriesEnd->copy()->subMonths(23)->startOfMonth();

                $labels = [];
                $data   = [];
                $cursor = $start->copy();
                while ($cursor->lte($seriesEnd)) {
                    $key = $cursor->format('Y-m-01');
                    $labels[] = $cursor->format('M Y');  // matches controller (formatted to MM/YY in ticks)
                    $data[]   = (int)($raw[$key] ?? 0);
                    $cursor->addMonth();
                }

                // Store combined payload to match controller Cache::remember() contract
                Cache::put('dashboard:sales_last_24m:EW:catA:v2', [$labels, $data], $ttl);
                break;

            case 'typeSplit':
                // Property type split by year (England & Wales, Cat A)
                // D = Detached, S = Semi-detached, T = Terraced, F = Flat
                $data = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as total')
                    ->where('PPDCategoryType', 'A')
                    ->whereIn('PropertyType', ['D','S','T','F'])
                    ->groupBy('year', 'type')
                    ->orderBy('year')
                    ->get();

                // Cache key used by the homepage Blade (or future controller) for the stacked type chart
                Cache::put('ew:propertyTypeSplitByYear:catA:v1', $data, $ttl);
                break;

            case 'newBuildSplit':
                // New build vs existing split by year (England & Wales, Cat A)
                // Y = New build, N = Existing
                $data = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `NewBuild` as nb, COUNT(*) as total')
                    ->where('PPDCategoryType', 'A')
                    ->whereIn('NewBuild', ['Y','N'])
                    ->groupBy('year', 'nb')
                    ->orderBy('year')
                    ->get();

                Cache::put('ew:newBuildSplitByYear:catA:v1', $data, $ttl);
                break;

            case 'durationSplit':
                // Leasehold vs Freehold split by year (England & Wales, Cat A)
                // F = Freehold, L = Leasehold
                $data = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `Duration` as dur, COUNT(*) as total')
                    ->where('PPDCategoryType', 'A')
                    ->whereIn('Duration', ['F','L'])
                    ->groupBy('year', 'dur')
                    ->orderBy('year')
                    ->get();

                Cache::put('ew:durationSplitByYear:catA:v1', $data, $ttl);
                break;

            case 'avgPriceByType':
                // Average price by property type by year (England & Wales, Cat A)
                // D = Detached, S = Semi-detached, T = Terraced, F = Flat
                $data = DB::table('land_registry')
                    ->selectRaw('`YearDate` as year, `PropertyType` as type, ROUND(AVG(`Price`)) as avg_price')
                    ->where('PPDCategoryType', 'A')
                    ->whereIn('PropertyType', ['D','S','T','F'])
                    ->whereNotNull('Price')
                    ->where('Price', '>', 0)
                    ->groupBy('year', 'type')
                    ->orderBy('year')
                    ->get();

                Cache::put('ew:avgPriceByTypeByYear:catA:v1', $data, $ttl);
                break;

            default:
                $this->warn("Unknown task '{$task}', skipping.");
        }
    }
}