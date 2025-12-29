<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class UpclWarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Keep the same signature so your existing schedule line still works.
     */
    protected $signature = 'upcl:warm {--district=} {--parallel=1}';

    /**
     * The console command description.
     */
    protected $description = 'Warm the Ultra Prime Central London cache';

    public function handle(): int
    {
        $this->info('Starting Ultra Prime cache warm...');

        $districtOption = (string) ($this->option('district') ?? '');
        $parallel = max(1, (int) ($this->option('parallel') ?? 1));

        // Reduce memory usage during large aggregations
        DB::connection()->disableQueryLog();

        // TTL in seconds (45 days)
        $ttl = 60 * 60 * 24 * 45;

        if ($districtOption !== '') {
            // Child mode: warm a single district only
            $this->warmDistrict($districtOption, $ttl);
            return self::SUCCESS;
        }

        // Orchestrator: fetch all Ultra Prime districts
        $districts = DB::table('prime_postcodes')
            ->where('category', 'Ultra Prime')
            ->pluck('postcode')
            ->unique()
            ->values()
            ->all();

        if (empty($districts)) {
            $this->warn('No Ultra Prime districts found.');
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

            $startWorker = function (string $district) use (&$running) {
                $php = PHP_BINARY;
                $artisan = base_path('artisan');
                $proc = new Process([$php, $artisan, 'upcl:warm', '--district='.$district]);
                $proc->setTimeout(null);
                $proc->disableOutput();
                $proc->start();
                $running[$district] = $proc;
            };

            // Prime pool
            while (!empty($queue) && count($running) < $maxWorkers) {
                $startWorker(array_shift($queue));
            }

            // Event loop
            while (!empty($running)) {
                foreach ($running as $district => $proc) {
                    if (!$proc->isRunning()) {
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
                usleep(100000); // 100ms
            }

            $bar->finish();
            $this->newLine();
        }

        // ===== Warm ALL Ultra Prime (aggregate across all UPCL outward codes) =====
        $this->newLine();
        $this->info('Warming ALL Ultra Prime (aggregate)');
        $keyBaseAll = 'upcl:v5:catA:ALL:';

        $baseAll = DB::table('land_registry as lr')
            ->join(DB::raw("(SELECT DISTINCT postcode FROM prime_postcodes WHERE category = 'Ultra Prime') as pp"),
                DB::raw("SUBSTRING_INDEX(lr.Postcode, ' ', 1)"), 'LIKE', DB::raw("CONCAT(pp.postcode, '%')"))
            ->where('lr.PPDCategoryType', 'A');

        // Avg price
        $avgPriceAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, ROUND(AVG(lr.Price)) as avg_price')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'avgPrice', $avgPriceAll, $ttl);

        // Sales
        $salesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, COUNT(*) as sales')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'sales', $salesAll, $ttl);

        // Property types
        $propertyTypesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, lr.PropertyType as type, COUNT(*) as count')
            ->groupBy('lr.YearDate','type')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'propertyTypes', $propertyTypesAll, $ttl);

        // Average price by property type (D/S/T/F) per year
        $avgPriceByTypeAll = (clone $baseAll)
            ->selectRaw("lr.YearDate as year, LEFT(lr.PropertyType, 1) as type, ROUND(AVG(lr.Price)) as avg_price")
            ->whereNotNull('lr.PropertyType')
            ->whereRaw("LEFT(lr.PropertyType, 1) IN ('D','S','T','F')")
            ->groupBy('lr.YearDate', 'type')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'avgPriceByType', $avgPriceByTypeAll, $ttl);

        // New build vs existing (% of sales) per year
        $newBuildPctAll = (clone $baseAll)
            ->selectRaw(
                "lr.YearDate as year, " .
                "ROUND(100 * SUM(CASE WHEN lr.NewBuild = 'Y' THEN 1 ELSE 0 END) / COUNT(*), 1) as new_pct, " .
                "ROUND(100 * SUM(CASE WHEN lr.NewBuild = 'N' THEN 1 ELSE 0 END) / COUNT(*), 1) as existing_pct"
            )
            ->whereNotNull('lr.NewBuild')
            ->whereIn('lr.NewBuild', ['Y','N'])
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'newBuildPct', $newBuildPctAll, $ttl);

        // Freehold vs Leasehold (% of sales) per year (Duration: F/L)
        $tenurePctAll = (clone $baseAll)
            ->selectRaw(
                "lr.YearDate as year, " .
                "ROUND(100 * SUM(CASE WHEN lr.Duration = 'F' THEN 1 ELSE 0 END) / COUNT(*), 1) as free_pct, " .
                "ROUND(100 * SUM(CASE WHEN lr.Duration = 'L' THEN 1 ELSE 0 END) / COUNT(*), 1) as lease_pct"
            )
            ->whereNotNull('lr.Duration')
            ->whereIn('lr.Duration', ['F','L'])
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'tenurePct', $tenurePctAll, $ttl);

        // P90
        $decilesAll = (clone $baseAll)
            ->selectRaw('lr.YearDate, lr.Price, NTILE(10) OVER (PARTITION BY lr.YearDate ORDER BY lr.Price) as decile');
        $p90All = DB::query()->fromSub($decilesAll,'t')
            ->selectRaw('YearDate as year, MIN(Price) as p90')
            ->where('decile',10)
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBaseAll.'p90',$p90All,$ttl);

        // Top 5% avg
        $rankedTop5All = (clone $baseAll)
            ->selectRaw('lr.YearDate, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn, COUNT(*) OVER (PARTITION BY lr.YearDate) as cnt');
        $top5All = DB::query()->fromSub($rankedTop5All,'ranked')
            ->selectRaw('YearDate as year, ROUND(AVG(Price)) as top5_avg')
            ->whereRaw('rn <= CEIL(cnt * 0.05)')
            ->groupBy('year')
            ->orderBy('year')
            ->get();
        Cache::put($keyBaseAll.'top5',$top5All,$ttl);

        // Top sale per year
        $topSalePerYearAll = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, MAX(lr.Price) as top_sale')
            ->groupBy('lr.YearDate')
            ->orderBy('lr.YearDate')
            ->get();
        Cache::put($keyBaseAll.'topSalePerYear',$topSalePerYearAll,$ttl);

        // Top 3 per year
        $rankedTop3All = (clone $baseAll)
            ->selectRaw('lr.YearDate as year, lr.Date, lr.Postcode, lr.Price, ROW_NUMBER() OVER (PARTITION BY lr.YearDate ORDER BY lr.Price DESC) as rn');
        $top3PerYearAll = DB::query()->fromSub($rankedTop3All,'r')
            ->select('year','Date','Postcode','Price','rn')
            ->where('rn','<=',3)
            ->orderBy('year')
            ->orderBy('rn')
            ->get();
        Cache::put($keyBaseAll.'top3PerYear',$top3PerYearAll,$ttl);

        Cache::put('upcl:v5:catA:last_warm', now()->toIso8601String(), $ttl);

        $this->info('Ultra Prime cache warm complete.');

        return self::SUCCESS;
    }

    private function warmDistrict(string $district, int $ttl): void
    {
        $keyBase = 'upcl:v5:catA:' . $district . ':';

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

        // Average price by property type (D/S/T/F) per year
        $avgPriceByType = DB::table('land_registry')
            ->selectRaw("`YearDate` as year, LEFT(`PropertyType`, 1) as type, ROUND(AVG(`Price`)) as avg_price")
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('PropertyType')
            ->whereRaw("LEFT(`PropertyType`, 1) IN ('D','S','T','F')")
            ->groupBy('YearDate', 'type')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'avgPriceByType', $avgPriceByType, $ttl);

        // New build vs existing (% of sales) per year
        $newBuildPct = DB::table('land_registry')
            ->selectRaw(
                "`YearDate` as year, " .
                "ROUND(100 * SUM(CASE WHEN `NewBuild` = 'Y' THEN 1 ELSE 0 END) / COUNT(*), 1) as new_pct, " .
                "ROUND(100 * SUM(CASE WHEN `NewBuild` = 'N' THEN 1 ELSE 0 END) / COUNT(*), 1) as existing_pct"
            )
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('NewBuild')
            ->whereIn('NewBuild', ['Y','N'])
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'newBuildPct', $newBuildPct, $ttl);

        // Freehold vs Leasehold (% of sales) per year (Duration: F/L)
        $tenurePct = DB::table('land_registry')
            ->selectRaw(
                "`YearDate` as year, " .
                "ROUND(100 * SUM(CASE WHEN `Duration` = 'F' THEN 1 ELSE 0 END) / COUNT(*), 1) as free_pct, " .
                "ROUND(100 * SUM(CASE WHEN `Duration` = 'L' THEN 1 ELSE 0 END) / COUNT(*), 1) as lease_pct"
            )
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('Duration')
            ->whereIn('Duration', ['F','L'])
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'tenurePct', $tenurePct, $ttl);

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