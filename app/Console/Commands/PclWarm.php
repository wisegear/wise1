<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class PclWarm extends Command
{
    /**
     * Keep the same signature so existing schedules/crons still work.
     */
    protected $signature = 'pcl:warm {--district=} {--parallel=1}';

    protected $description = 'Warm the Prime Central London cache (Category A only)';

    public function handle(): int
    {
        $districtOption = (string) ($this->option('district') ?? '');
        $parallel = max(1, (int) ($this->option('parallel') ?? 1));

        // Reduce memory usage for long-running commands
        DB::connection()->disableQueryLog();

        // TTL = 45 days
        $ttl = 60 * 60 * 24 * 45;

        if ($districtOption !== '') {
            // Child mode: warm a single district, no spawning.
            $this->warmDistrict($districtOption, $ttl);
            return self::SUCCESS;
        }

        // Orchestrator mode: fetch all PCL districts
        $this->info('Starting Prime Central cache warm...');

        $districts = DB::table('prime_postcodes')
            ->where('category', 'Prime Central')
            ->pluck('postcode')
            ->unique()
            ->values()
            ->all();

        if (empty($districts)) {
            $this->warn('No Prime Central districts found.');
            return self::SUCCESS;
        }

        if ($parallel <= 1) {
            // Sequential (original behaviour)
            $this->withProgressBar($districts, function (string $district) use ($ttl) {
                $this->warmDistrict($district, $ttl);
            });
        } else {
            // Parallel: spawn up to N child processes running this same command with --district
            $this->info("Running in parallel with up to {$parallel} workers...");

            $total = count($districts);
            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $maxWorkers = (int) min($parallel, 8); // safety cap
            $queue = $districts; // array of strings
            $running = [];

            // Helper to start a new worker for a district
            $startWorker = function (string $district) use (&$running) {
                $php = PHP_BINARY;
                $artisan = base_path('artisan');
                $proc = new Process([$php, $artisan, 'pcl:warm', '--district='.$district]);
                $proc->setTimeout(null);
                $proc->disableOutput();
                $proc->start();
                $running[$district] = $proc;
            };

            // Prime the pool
            while (!empty($queue) && count($running) < $maxWorkers) {
                $startWorker(array_shift($queue));
            }

            // Event loop: keep pool full until all are done
            while (!empty($running)) {
                foreach ($running as $district => $proc) {
                    if (!$proc->isRunning()) {
                        // Optional: check for errors
                        if ($proc->getExitCode() !== 0) {
                            $this->warn("Worker failed for {$district} (exit code: {$proc->getExitCode()})");
                        }
                        unset($running[$district]);
                        $bar->advance();

                        if (!empty($queue)) {
                            $startWorker(array_shift($queue));
                        }
                    }
                }
                // Small sleep to avoid tight loop
                usleep(100000); // 100ms
            }

            $bar->finish();
            $this->newLine();
        }

        // ===== Warm ALL Prime Central (aggregate across all PCL outward codes) =====
        $this->newLine();
        $this->info('Warming ALL Prime Central (aggregate)');

        $keyBaseAll = 'pcl:v3:catA:ALL:'; // v3 to avoid stale cache from prior logic

        $baseAll = DB::table('land_registry as lr')
            ->join(DB::raw("(SELECT DISTINCT postcode FROM prime_postcodes WHERE category = 'Prime Central') as pp"),
                DB::raw("SUBSTRING_INDEX(lr.Postcode, ' ', 1)"), 'LIKE', DB::raw("CONCAT(pp.postcode, '%')"))
            ->where('lr.PPDCategoryType', 'A');

        $avgPriceAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, ROUND(AVG(lr.Price)) as avg_price')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll . 'avgPrice', $avgPriceAll, $ttl);

        $salesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, COUNT(*) as sales')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll . 'sales', $salesAll, $ttl);

        $propertyTypesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, lr.PropertyType as type, COUNT(*) as count')
            ->groupBy('lr.YearDate', 'type')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll . 'propertyTypes', $propertyTypesAll, $ttl);

        $decilesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate, lr.Price, NTILE(10) OVER (PARTITION BY lr.YearDate ORDER BY lr.Price) as decile');
        $p90All = DB::query()
            ->fromSub($decilesAll, 't')
            ->selectRaw('YearDate as year, MIN(Price) as p90')
            ->where('decile', 10)
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBaseAll . 'p90', $p90All, $ttl);

        $rankedTop5All = (clone $baseAll)
            ->selectRaw('lr.YearDate, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn, COUNT(*) OVER (PARTITION BY lr.YearDate) as cnt');
        $top5All = DB::query()
            ->fromSub($rankedTop5All, 'ranked')
            ->selectRaw('YearDate as year, ROUND(AVG(Price)) as top5_avg')
            ->whereRaw('rn <= CEIL(cnt * 0.05)')
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBaseAll . 'top5', $top5All, $ttl);

        $topSalePerYearAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, MAX(lr.Price) as top_sale')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll . 'topSalePerYear', $topSalePerYearAll, $ttl);

        $rankedTop3All = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, lr.Date, lr.Postcode, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn');
        $top3PerYearAll = DB::query()
            ->fromSub($rankedTop3All, 'r')
            ->select('year', 'Date', 'Postcode', 'Price', 'rn')
            ->where('rn', '<=', 3)
            ->orderBy('year')
            ->orderBy('rn')
            ->get();
        Cache::put($keyBaseAll . 'top3PerYear', $top3PerYearAll, $ttl);

        Cache::put('pcl:v3:catA:last_warm', now()->toIso8601String(), $ttl);
        Cache::put('pcl:v2:catA:last_warm', now()->toIso8601String(), $ttl);

        $this->newLine(2);
        $this->info('Prime Central cache warm complete.');

        return self::SUCCESS;
    }

    private function warmDistrict(string $district, int $ttl): void
    {
        $keyBase = 'pcl:v2:catA:' . $district . ':';

        // Average price by year
        $avgPrice = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'avgPrice', $avgPrice, $ttl);

        // Sales count by year
        $sales = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, COUNT(*) as sales')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'sales', $sales, $ttl);

        // Property types by year
        $propertyTypes = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate', 'type')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'propertyTypes', $propertyTypes, $ttl);

        // 90th percentile threshold per year via NTILE window
        $deciles = DB::table('land_registry')
            ->selectRaw('`YearDate`, `Price`, NTILE(10) OVER (PARTITION BY `YearDate` ORDER BY `Price`) as decile')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('Price')
            ->where('Price', '>', 0);

        $p90 = DB::query()
            ->fromSub($deciles, 't')
            ->selectRaw('`YearDate` as year, MIN(`Price`) as p90')
            ->where('decile', 10)
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBase . 'p90', $p90, $ttl);

        // Top 5% average per year via row_number/count windows
        $rankedTop5 = DB::table('land_registry')
            ->selectRaw('`YearDate`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('Price')
            ->where('Price', '>', 0);

        $top5 = DB::query()
            ->fromSub($rankedTop5, 'ranked')
            ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as top5_avg')
            ->whereRaw('rn <= CEIL(cnt * 0.05)')
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBase . 'top5', $top5, $ttl);

        // Top sale per year (for spike marker)
        $topSalePerYear = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('Price')
            ->where('Price', '>', 0)
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'topSalePerYear', $topSalePerYear, $ttl);

        // Top 3 sales per year (for context/tooltips)
        $rankedTop3 = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('Price')
            ->where('Price', '>', 0);

        $top3PerYear = DB::query()
            ->fromSub($rankedTop3, 'r')
            ->select('year', 'Date', 'Postcode', 'Price', 'rn')
            ->where('rn', '<=', 3)
            ->orderBy('year')
            ->orderBy('rn')
            ->get();
        Cache::put($keyBase . 'top3PerYear', $top3PerYear, $ttl);
    }
}
