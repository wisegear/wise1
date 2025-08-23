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
        $keyBase = 'upcl:v3:' . $district . ':';

        // Average price by year
        $avgPrice = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'avgPrice', $avgPrice, $ttl);

        // Sales count by year
        $sales = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, COUNT(*) as sales')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->groupBy('YearDate')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'sales', $sales, $ttl);

        // Property types by year
        $propertyTypes = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->groupBy('YearDate', 'type')
            ->orderBy('YearDate')
            ->get();
        Cache::put($keyBase . 'propertyTypes', $propertyTypes, $ttl);
    });

    $this->newLine(2);

    // Record global last warm timestamp
    Cache::put('upcl:v3:last_warm', now()->toIso8601String(), $ttl);

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

    $key = 'upcl:v3:last_warm';
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

// Clear Ultra Prime v3 cache keys (use with caution)
Artisan::command('upcl:clear', function () {
    $this->warn('Clearing Ultra Prime v3 cache keys...');
    $prefix = config('cache.prefix', '');
    $keys = DB::table('cache')->where('key', 'like', $prefix . 'upcl:v3:%')->pluck('key');
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
        $keyBase = 'pcl:v1:' . $district . ':';

        // Average price by year
        $avgPrice = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, ROUND(AVG(`Price`)) as avg_price')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->groupBy('YearDate')->orderBy('YearDate')->get();
        Cache::put($keyBase . 'avgPrice', $avgPrice, $ttl);

        // Sales count by year
        $sales = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, COUNT(*) as sales')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->groupBy('YearDate')->orderBy('YearDate')->get();
        Cache::put($keyBase . 'sales', $sales, $ttl);

        // Property types by year
        $propertyTypes = DB::table('land_registry')
            ->selectRaw('`YearDate` as year, `PropertyType` as type, COUNT(*) as count')
            ->whereRaw("SUBSTRING_INDEX(`Postcode`, ' ', 1) LIKE CONCAT(?, '%')", [$district])
            ->groupBy('YearDate','type')->orderBy('YearDate')->get();
        Cache::put($keyBase . 'propertyTypes', $propertyTypes, $ttl);
    });

    $this->newLine(2);

    // Record global last warm timestamp
    Cache::put('pcl:v1:last_warm', now()->toIso8601String(), $ttl);

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

    $key = 'pcl:v1:last_warm';
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
    $this->warn('Clearing Prime Central v1 cache keys...');
    $prefix = config('cache.prefix', '');
    $keys = DB::table('cache')->where('key', 'like', $prefix . 'pcl:v1:%')->pluck('key');
    foreach ($keys as $key) {
        DB::table('cache')->where('key', $key)->delete();
    }
    $this->info('Cleared ' . count($keys) . ' keys.');
})->purpose('Clear PCL v1 cache keys');