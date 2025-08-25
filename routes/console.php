<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Warm the cache for Ultra Prime Central London charts without hitting HTTP otherwise Bob will say it's slow.
Artisan::command('upcl:warm', function () {
    $this->info('Starting Ultra Prime cache warm...');

    // Ultra Prime postcode districts
    $districts = DB::table('prime_postcodes')
        ->where('category', 'Ultra Prime')
        ->pluck('postcode')
        ->unique()
        ->values();

    if ($districts->isEmpty()) {
        $this->warn('No Ultra Prime districts found.');
        return;
    }

    // Reduce memory usage during large aggregations
    DB::connection()->disableQueryLog();

    // TTL in seconds (45 days)
    $ttl = 60 * 60 * 24 * 45;

    $this->withProgressBar($districts, function ($district) use ($ttl) {
        $keyBase = 'upcl:v4:catA:' . $district . ':';

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

        // 90th percentile (threshold) per year via window function
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

        // Top 5% average per year via window ranking
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
    });

    $this->newLine(2);

    // Record global last warm timestamp
    Cache::put('upcl:v4:catA:last_warm', now()->toIso8601String(), $ttl);

    $this->info('Ultra Prime cache warm complete.');
})->purpose('Warm the Ultra Prime Central London cache');

// Schedule: run monthly (2nd of the month at 02:15)
Schedule::command('upcl:warm')->monthlyOn(2, '02:15');

// Show Ultra Prime cache status (store + last_warm key visibility from both Cache and DB)
Artisan::command('upcl:status', function () {
    $this->info('Ultra Prime cache status');

    $store = config('cache.default');
    $prefix = config('cache.prefix');
    $this->line("Cache store: {$store}");
    $this->line("Cache prefix: {$prefix}");

    $key = 'upcl:v4:catA:last_warm';
    $v = Cache::get($key);
    $this->line('Cache::get(last_warm): ' . ($v ? $v : '[null]'));

    // Also check DB cache table directly (useful if CLI and Web use different stores)
    try {
        $dbKey = ($prefix ? $prefix : '') . $key;
        $raw = DB::table('cache')->where('key', $dbKey)->value('value');
        $un = $raw ? @unserialize($raw) : null;
        $this->line('DB cache value: ' . ($un ? $un : '[null]'));
    } catch (\Throwable $e) {
        $this->error('DB cache check failed: ' . $e->getMessage());
    }
})->purpose('Show UPCL cache status');

// List Ultra Prime districts (diagnostic)
Artisan::command('upcl:list', function () {
    $this->info('Ultra Prime postcode districts (from prime_postcodes):');

    // Raw distinct (no TRIM) to see what the warmer uses
    $raw = DB::table('prime_postcodes')
        ->where('category', 'Ultra Prime')
        ->pluck('postcode')
        ->unique()
        ->sort()
        ->values();

    $this->line('Unique districts used by warm(): ' . $raw->count());
    foreach ($raw as $pc) {
        $this->line('  - ' . $pc);
    }

    // Compare with TRIMmed to detect trailing spaces
    $trimmed = DB::table('prime_postcodes')
        ->where('category', 'Ultra Prime')
        ->selectRaw('TRIM(postcode) AS pc')
        ->pluck('pc')
        ->unique()
        ->sort()
        ->values();

    $this->newLine();
    $this->line('Unique districts after TRIM(): ' . $trimmed->count());
    if ($trimmed->count() !== $raw->count()) {
        $this->warn('Mismatch detected. Some rows likely have trailing/leading spaces or casing differences.');
        // Show suspicious rows where TRIM changes the value
        $suspicious = DB::table('prime_postcodes')
            ->where('category', 'Ultra Prime')
            ->select('postcode')
            ->get()
            ->filter(function ($r) { return $r->postcode !== trim($r->postcode); })
            ->pluck('postcode')
            ->unique();
        if ($suspicious->isNotEmpty()) {
            $this->line('Examples with whitespace issues:');
            foreach ($suspicious as $pc) { $this->line('  * "' . $pc . '"'); }
        }
    }

    $this->newLine();
    $this->info('Tip: To normalise, you can run a one-off TRIM in a migration or seeder:');
    $this->line("DB::table('prime_postcodes')->update(['postcode' => DB::raw('TRIM(postcode)')]);");
})->purpose('List Ultra Prime postcode districts and detect formatting issues');

// Clear Ultra Prime v3 cache keys (use with caution)
Artisan::command('upcl:clear', function () {
    $this->warn('Clearing Ultra Prime v4 catA cache keys...');
    $prefix = config('cache.prefix', '');
    $keys = DB::table('cache')->where('key', 'like', $prefix . 'upcl:v4:catA:%')->pluck('key');
    foreach ($keys as $key) {
        DB::table('cache')->where('key', $key)->delete();
    }
    $this->info('Cleared ' . count($keys) . ' keys.');
})->purpose('Clear UPCL v3 cache keys');

// Warm the cache for Prime Central London charts without hitting HTTP otherwise Bob will say it's slow.

// routes/console.php
Artisan::command('pcl:warm', function () {
    $this->info('Starting Prime Central cache warm...');

    $districts = DB::table('prime_postcodes')
        ->where('category', 'Prime Central')
        ->pluck('postcode')->unique()->values();

    if ($districts->isEmpty()) {
        $this->warn('No Prime Central districts found.');
        return;
    }

    // Reduce memory usage during large aggregations
    DB::connection()->disableQueryLog();

    $ttl = 60 * 60 * 24 * 45; // 45 days in seconds

    $this->withProgressBar($districts, function ($district) use ($ttl) {
        $keyBase = 'pcl:v2:catA:' . $district . ':';

        // Average price by year
        $avgPrice = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate')->orderBy('YearDate')->get();
        Cache::put($keyBase . 'avgPrice', $avgPrice, $ttl);

        // Sales count by year
        $sales = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, COUNT(*) as sales')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate')->orderBy('YearDate')->get();
        Cache::put($keyBase . 'sales', $sales, $ttl);

        // Property types by year
        $propertyTypes = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->where('PPDCategoryType', 'A')
            ->groupBy('YearDate','type')->orderBy('YearDate')->get();
        Cache::put($keyBase . 'propertyTypes', $propertyTypes, $ttl);

        // 90th percentile (threshold) per year via window function
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

        // Top 5% average per year via window ranking
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
    });

    $this->newLine(2);

    // Record global last warm timestamp
    Cache::put('pcl:v2:catA:last_warm', now()->toIso8601String(), $ttl);

    $this->info('Prime Central cache warm complete.');
})->purpose('Warm the Prime Central London cache');

// Schedule: run monthly (2nd of the month at 02:15)
Schedule::command('pcl:warm')->monthlyOn(2, '02:15');

// Show Prime Central cache status
Artisan::command('pcl:status', function () {
    $this->info('Prime Central cache status');

    $store = config('cache.default');
    $prefix = config('cache.prefix');
    $this->line("Cache store: {$store}");
    $this->line("Cache prefix: {$prefix}");

    $key = 'pcl:v2:catA:last_warm';
    $v = Cache::get($key);
    $this->line('Cache::get(last_warm): ' . ($v ? $v : '[null]'));

    // Also check DB cache table directly
    try {
        $dbKey = ($prefix ? $prefix : '') . $key;
        $raw = DB::table('cache')->where('key', $dbKey)->value('value');
        $un = $raw ? @unserialize($raw) : null;
        $this->line('DB cache value: ' . ($un ? $un : '[null]'));
    } catch (\Throwable $e) {
        $this->error('DB cache check failed: ' . $e->getMessage());
    }
})->purpose('Show PCL cache status');

// Clear Prime Central cache keys
Artisan::command('pcl:clear', function () {
    $this->warn('Clearing Prime Central v2 catA cache keys...');
    $prefix = config('cache.prefix', '');
    $keys = DB::table('cache')->where('key', 'like', $prefix . 'pcl:v2:catA:%')->pluck('key');
    foreach ($keys as $key) {
        DB::table('cache')->where('key', $key)->delete();
    }
    $this->info('Cleared ' . count($keys) . ' keys.');
})->purpose('Clear PCL v1 cache keys');

// Warm the cache for PropertyController homepage aggregates
Artisan::command('property:home-warm', function () {
    $this->info('Starting PropertyController home cache warm...');

    DB::connection()->disableQueryLog();
    $ttl = 60 * 60 * 24 * 45; // 45 days

    // Scopes used by multiple steps
    $primeScope = "EXISTS (SELECT 1 FROM prime_postcodes WHERE UPPER(land_registry.Postcode) LIKE CONCAT(UPPER(prime_postcodes.postcode),'%') AND category='Prime Central')";
    $ultraScope = "EXISTS (SELECT 1 FROM prime_postcodes WHERE UPPER(land_registry.Postcode) LIKE CONCAT(UPPER(prime_postcodes.postcode),'%') AND category='Ultra Prime')";

    // Define each warm step as a closure so we can show a progress bar like the district warmers
    $steps = collect([
        function () use ($ttl) {
            $data = DB::table('land_registry')
                ->selectRaw('YEAR(`Date`) as year, COUNT(*) as total')
                ->where('PPDCategoryType', 'A')
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('land_registry_sales_by_year:catA:v2', $data, $ttl);
        },
        function () use ($ttl) {
            $data = DB::table('land_registry')
                ->selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                ->where('PPDCategoryType', 'A')
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('land_registry_avg_price_by_year:catA:v2', $data, $ttl);
        },
        function () use ($ttl) {
            $sub = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Price`, CUME_DIST() OVER (PARTITION BY `YearDate` ORDER BY `Price`) as cd')
                ->where('PPDCategoryType', 'A')
                ->whereNotNull('Price')->where('Price','>',0);
            $data = DB::query()->fromSub($sub, 't')
                ->selectRaw('year, MIN(Price) as p90_price')
                ->where('cd','>=',0.9)
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('ew:p90:catA:v1', $data, $ttl);
        },
        function () use ($ttl) {
            $sub = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                ->where('PPDCategoryType','A')
                ->whereNotNull('Price')->where('Price','>',0);
            $data = DB::query()->fromSub($sub, 'r')
                ->selectRaw('year, ROUND(AVG(`Price`)) as top5_avg')
                ->whereColumn('rn','<=',DB::raw('CEIL(0.05*cnt)'))
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('ew:top5avg:catA:v1', $data, $ttl);
        },
        function () use ($ttl, $primeScope) {
            $data = DB::table('land_registry')
                ->selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                ->where('PPDCategoryType','A')->whereRaw($primeScope)
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('land_registry_avg_price_prime_central_by_year:catA:v2', $data, $ttl);
        },
        function () use ($ttl, $primeScope) {
            $data = DB::table('land_registry')
                ->selectRaw('YEAR(`Date`) as year, COUNT(*) as total_sales')
                ->where('PPDCategoryType','A')->whereRaw($primeScope)
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('prime_central_sales_by_year:catA:v2', $data, $ttl);
        },
        function () use ($ttl, $primeScope) {
            $sub = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Price`, CUME_DIST() OVER (PARTITION BY `YearDate` ORDER BY `Price`) as cd')
                ->where('PPDCategoryType','A')->whereRaw($primeScope)
                ->whereNotNull('Price')->where('Price','>',0);
            $data = DB::query()->fromSub($sub, 't')
                ->selectRaw('year, MIN(Price) as p90_price')
                ->where('cd','>=',0.9)
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('prime:p90:catA:v1', $data, $ttl);
        },
        function () use ($ttl, $primeScope) {
            $sub = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                ->where('PPDCategoryType','A')->whereRaw($primeScope)
                ->whereNotNull('Price')->where('Price','>',0);
            $data = DB::query()->fromSub($sub, 'r')
                ->selectRaw('year, ROUND(AVG(`Price`)) as top5_avg')
                ->whereColumn('rn','<=',DB::raw('CEIL(0.05*cnt)'))
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('prime:top5avg:catA:v1', $data, $ttl);
        },
        function () use ($ttl, $ultraScope) {
            $data = DB::table('land_registry')
                ->selectRaw('YEAR(`Date`) as year, ROUND(AVG(`Price`)) as avg_price')
                ->where('PPDCategoryType','A')->whereRaw($ultraScope)
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('land_registry_avg_price_ultra_prime_by_year:catA:v2', $data, $ttl);
        },
        function () use ($ttl, $ultraScope) {
            $data = DB::table('land_registry')
                ->selectRaw('YEAR(`Date`) as year, COUNT(*) as total_sales')
                ->where('PPDCategoryType','A')->whereRaw($ultraScope)
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('ultra_prime_sales_by_year:catA:v2', $data, $ttl);
        },
        function () use ($ttl, $ultraScope) {
            $sub = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Price`, CUME_DIST() OVER (PARTITION BY `YearDate` ORDER BY `Price`) as cd')
                ->where('PPDCategoryType','A')->whereRaw($ultraScope)
                ->whereNotNull('Price')->where('Price','>',0);
            $data = DB::query()->fromSub($sub, 't')
                ->selectRaw('year, MIN(Price) as p90_price')
                ->where('cd','>=',0.9)
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('ultra:p90:catA:v1', $data, $ttl);
        },
        function () use ($ttl, $ultraScope) {
            $sub = DB::table('land_registry')
                ->selectRaw('`YearDate` as year, `Price`, ROW_NUMBER() OVER (PARTITION BY `YearDate` ORDER BY `Price` DESC) as rn, COUNT(*) OVER (PARTITION BY `YearDate`) as cnt')
                ->where('PPDCategoryType','A')->whereRaw($ultraScope)
                ->whereNotNull('Price')->where('Price','>',0);
            $data = DB::query()->fromSub($sub, 'r')
                ->selectRaw('year, ROUND(AVG(`Price`)) as top5_avg')
                ->whereColumn('rn','<=',DB::raw('CEIL(0.05*cnt)'))
                ->groupBy('year')->orderBy('year')->get();
            Cache::put('ultra:top5avg:catA:v1', $data, $ttl);
        },
    ]);

    $this->withProgressBar($steps, function ($step) {
        $step();
    });

    $this->newLine(2);
    Cache::put('property:home:catA:last_warm', now()->toIso8601String(), $ttl);
    $this->info('PropertyController home cache warm complete.');
})->purpose('Warm the PropertyController homepage cache');

// Schedule: run monthly (2nd of the month at 02:30)
Schedule::command('property:home-warm')->monthlyOn(2, '02:30');

// Status command
Artisan::command('property:home-status', function () {
    $this->info('Property home cache status');
    $key = 'property:home:catA:last_warm';
    $v = Cache::get($key);
    $this->line('Cache::get(last_warm): ' . ($v ? $v : '[null]'));
})->purpose('Show Property home cache status');

// Clear command
Artisan::command('property:home-clear', function () {
    $this->warn('Clearing Property home catA cache keys...');
    $prefix = config('cache.prefix', '');
    $keys = DB::table('cache')->where('key','like',$prefix.'%catA%')->pluck('key');
    foreach ($keys as $key) DB::table('cache')->where('key',$key)->delete();
    $this->info('Cleared '.count($keys).' keys.');
})->purpose('Clear Property home cache keys');