<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WarmAllLocalityCaches extends Command
{
    protected $signature = 'reports:warm-localities
                            {--ppd=A : PPDCategoryType filter (A or B)}
                            {--limit=0 : Limit number of localities to warm}
                            {--only=all : Only warm one group: all|price|sales|types}';

    protected $description = 'Precompute and cache locality-level datasets (price history, sales history, property types).';

    public function handle(): int
    {
        $ppd   = strtoupper((string) $this->option('ppd')) === 'B' ? 'B' : 'A';
        $only  = strtolower((string) $this->option('only'));
        $limit = (int) $this->option('limit');

        $ttl = now()->addDays(45);

        // Avoid Laravel keeping every result in memory
        DB::connection()->disableQueryLog();

        // Get distinct localities
        $localities = DB::table('land_registry')
            ->select('Locality')
            ->whereNotNull('Locality')
            ->where('Locality', '!=', '')
            ->distinct()
            ->orderBy('Locality')
            ->pluck('Locality');

        if ($limit > 0) {
            $localities = $localities->take($limit);
        }

        $count = $localities->count();
        if ($count === 0) {
            $this->info('No localities found.');
            return self::SUCCESS;
        }

        $sections = match ($only) {
            'price', 'sales', 'types' => 1,
            default => 3,
        };

        $steps = $count * $sections;

        $this->info("Warming caches for {$count} localities (PPD={$ppd}, only={$only})...");
        $bar = $this->output->createProgressBar($steps);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% elapsed | %estimated:-6s% eta | %memory:6s% | %message%');
        $bar->start();

        foreach ($localities as $locality) {

            // ---- PRICE HISTORY (v2 + v3) ----
            if ($only === 'all' || $only === 'price') {
                // v2: all property types for this locality
                $price = DB::table('land_registry')
                    ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('PPDCategoryType', $ppd)
                    ->where('Locality', $locality)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate')
                    ->get();

                Cache::put('locality:priceHistory:v2:cat' . $ppd . ':' . $locality, $price, $ttl);
                $bar->setMessage('Price: ' . $locality);
                $bar->advance();

                // v3: per-property-type for this locality
                $priceByType = DB::table('land_registry')
                    ->select('PropertyType', 'YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('PPDCategoryType', $ppd)
                    ->where('Locality', $locality)
                    ->groupBy('PropertyType', 'YearDate')
                    ->orderBy('PropertyType')
                    ->orderBy('YearDate')
                    ->get()
                    ->groupBy('PropertyType');

                foreach ($priceByType as $type => $series) {
                    Cache::put(
                        'locality:priceHistory:v3:cat' . $ppd . ':' . $locality . ':type:' . $type,
                        $series->values(),
                        $ttl
                    );
                }
            }

            // ---- SALES HISTORY (v2 + v3) ----
            if ($only === 'all' || $only === 'sales') {
                // v2: all property types for this locality
                $sales = DB::table('land_registry')
                    ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('PPDCategoryType', $ppd)
                    ->where('Locality', $locality)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate')
                    ->get();

                Cache::put('locality:salesHistory:v2:cat' . $ppd . ':' . $locality, $sales, $ttl);
                $bar->setMessage('Sales: ' . $locality);
                $bar->advance();

                // v3: per-property-type for this locality
                $salesByType = DB::table('land_registry')
                    ->select('PropertyType', 'YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('PPDCategoryType', $ppd)
                    ->where('Locality', $locality)
                    ->groupBy('PropertyType', 'YearDate')
                    ->orderBy('PropertyType')
                    ->orderBy('YearDate')
                    ->get()
                    ->groupBy('PropertyType');

                foreach ($salesByType as $type => $series) {
                    Cache::put(
                        'locality:salesHistory:v3:cat' . $ppd . ':' . $locality . ':type:' . $type,
                        $series->values(),
                        $ttl
                    );
                }
            }

            // ---- PROPERTY TYPES (v2) ----
            if ($only === 'all' || $only === 'types') {
                $map = [
                    'D' => 'Detached',
                    'S' => 'Semi-Detached',
                    'T' => 'Terraced',
                    'F' => 'Flat',
                    'O' => 'Other',
                ];

                $rows = DB::table('land_registry')
                    ->select('PropertyType', DB::raw('COUNT(*) as property_count'))
                    ->where('PPDCategoryType', $ppd)
                    ->where('Locality', $locality)
                    ->groupBy('PropertyType')
                    ->get();

                $mapped = $rows->map(function ($row) use ($map) {
                    return [
                        'label' => $map[$row->PropertyType] ?? $row->PropertyType,
                        'value' => (int) $row->property_count,
                    ];
                });

                Cache::put('locality:types:v2:cat' . $ppd . ':' . $locality, $mapped, $ttl);
                $bar->setMessage('Types: ' . $locality);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Locality cache warm complete.');

        return self::SUCCESS;
    }
}
