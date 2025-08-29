<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class OuterPrimeWarm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:outer-prime-warm {--district=} {--parallel=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm caches for Outer Prime London charts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Warming Outer Prime London caches...');

        // TTL = 45 days
        $ttl = 60 * 60 * 24 * 45;

        // Reduce memory usage in long runs
        DB::connection()->disableQueryLog();

        $districtOption = (string) ($this->option('district') ?? '');
        $parallel = max(1, (int) ($this->option('parallel') ?? 1));

        if ($districtOption !== '') {
            // Child mode: warm a single district, no spawning
            $this->warmDistrict($districtOption, $ttl);
        } else {
            // Orchestrator mode: fetch all Outer Prime districts
            $districts = DB::table('prime_postcodes')
                ->where('category', 'Outer Prime London')
                ->orderBy('postcode')
                ->pluck('postcode')
                ->unique()
                ->values()
                ->all();

            if (empty($districts)) {
                $this->warn('No Outer Prime districts found.');
                return;
            }

            if ($parallel <= 1) {
                // Sequential (original behaviour)
                $this->withProgressBar($districts, function (string $district) use ($ttl) {
                    $this->warmDistrict($district, $ttl);
                });
            } else {
                // Parallel: spawn child workers of this same command
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
                    $proc = new Process([$php, $artisan, 'app:outer-prime-warm', '--district='.$district]);
                    $proc->setTimeout(null);
                    $proc->disableOutput();
                    $proc->start();
                    $running[$district] = $proc;
                };

                // Prime the pool
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

            // ===== Warm ALL Outer Prime London (aggregate across all OPL outward codes) =====
            $this->newLine();
            $this->info('Warming ALL Outer Prime London (aggregate)');

            $keyBaseAll = 'outerprime:v2:catA:ALL:'; // separate namespace for aggregate

            // Base query: join to DISTINCT OPL outward codes and match by prefix
            $baseAll = DB::table('land_registry as lr')
                ->join(DB::raw("(SELECT DISTINCT postcode FROM prime_postcodes WHERE category = 'Outer Prime London') as pp"),
                    DB::raw("SUBSTRING_INDEX(lr.Postcode, ' ', 1)"), 'LIKE', DB::raw("CONCAT(pp.postcode, '%')"))
                ->where('lr.PPDCategoryType', 'A');

            // Avg price by year
            $avgPriceAll = (clone $baseAll)
                ->selectRaw('lr.YearDate as year, ROUND(AVG(lr.Price)) as avg_price')
                ->groupBy('lr.YearDate')
                ->orderBy('lr.YearDate')
                ->get();
            Cache::put($keyBaseAll . 'avgPrice', $avgPriceAll, $ttl);

            // Sales count by year
            $salesAll = (clone $baseAll)
                ->selectRaw('lr.YearDate as year, COUNT(*) as sales')
                ->groupBy('lr.YearDate')
                ->orderBy('lr.YearDate')
                ->get();
            Cache::put($keyBaseAll . 'sales', $salesAll, $ttl);

            // Property types by year
            $propertyTypesAll = (clone $baseAll)
                ->selectRaw('lr.YearDate as year, lr.PropertyType as type, COUNT(*) as count')
                ->groupBy('lr.YearDate', 'type')
                ->orderBy('lr.YearDate')
                ->get();
            Cache::put($keyBaseAll . 'propertyTypes', $propertyTypesAll, $ttl);

            // 90th percentile per year via NTILE window
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

            // Top 5% average per year via window ranking
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

            // Top sale per year
            $topSalePerYearAll = (clone $baseAll)
                ->selectRaw('lr.YearDate as year, MAX(lr.Price) as top_sale')
                ->groupBy('lr.YearDate')
                ->orderBy('lr.YearDate')
                ->get();
            Cache::put($keyBaseAll . 'topSalePerYear', $topSalePerYearAll, $ttl);

            // Top 3 sales per year (for tooltip)
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

            // Last warm timestamp for the ALL aggregate namespace
            Cache::put('outerprime:v2:catA:last_warm', now()->toIso8601String(), $ttl);

            Cache::put('outerprime:v1:catA:last_warm', now()->toDateTimeString(), $ttl);
            $this->info('Outer Prime London warming complete.');
        }
    }

    private function warmDistrict(string $district, int $ttl): void
    {
        $this->info("Warming district: {$district}");

        $keyBase = 'outerprime:v1:catA:' . $district . ':';

        // Average price
        $avgPrice = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'avgPrice', $avgPrice, $ttl);

        // Sales
        $sales = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, COUNT(*) as sales')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'sales', $sales, $ttl);

        // Property types
        $propertyTypes = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate', 'type')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'propertyTypes', $propertyTypes, $ttl);

        // 90th percentile
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

        // Top 5% avg
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

        // Top sale
        $topSale = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, MAX(`Price`) as top_sale')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('Price')
            ->where('Price', '>', 0)
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'topSalePerYear', $topSale, $ttl);

        // Top 3 per year
        $rankedTop3 = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, `Date`, `Postcode`, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->whereNotNull('Price')
            ->where('Price', '>', 0);
        $top3 = DB::query()
            ->fromSub($rankedTop3, 'r')
            ->select('year', 'Date', 'Postcode', 'Price', 'rn')
            ->where('rn', '<=', 3)
            ->orderBy('year')
            ->orderBy('rn')
            ->get();
        Cache::put($keyBase . 'top3PerYear', $top3, $ttl);
    }
}
