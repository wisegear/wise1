<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarmAllCountyCaches extends Command
{
    protected $signature = 'reports:warm-counties
                            {--ppd=A : PPDCategoryType filter (A or B)}
                            {--limit=0 : Limit number of counties to warm}
                            {--only=all : Only warm one group: all|price|sales|types}';

    protected $description = 'Precompute and cache county-level datasets (price history, sales history, property types).';

    public function handle(): int
    {
        $ppd = strtoupper((string)$this->option('ppd')) === 'B' ? 'B' : 'A';
        $only = strtolower((string)$this->option('only'));
        $limit = (int)$this->option('limit');

        $ttl = now()->addDays(45);

        // Fetch the counties list from the fact table (distinct non-empty)
        // IMPORTANT: TRIM() so cache keys match controller usage
        $counties = DB::table('land_registry')
            ->selectRaw('TRIM(County) AS county')
            ->whereNotNull('County')
            ->whereRaw("TRIM(County) <> ''")
            ->distinct()
            ->orderBy('county')
            ->pluck('county');

        if ($limit > 0) {
            $counties = $counties->take($limit);
        }

        $this->info('Warming caches for ' . $counties->count() . ' counties (PPD=' . $ppd . ', only=' . $only . ')...');
        $bar = $this->output->createProgressBar($counties->count());
        $bar->start();

        foreach ($counties as $county) {
            $county = trim((string) $county);
            // PRICE HISTORY (Yearly AVG)
            if ($only === 'all' || $only === 'price') {
                $price = DB::table('land_registry')
                    ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('County', $county)
                    ->where('PPDCategoryType', $ppd)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
                Cache::put('county:priceHistory:v2:cat' . $ppd . ':' . $county, $price, $ttl);

                // Additionally warm per-property-type county price history (v3)
                $priceByType = DB::table(DB::raw('land_registry FORCE INDEX (idx_county)'))
                    ->selectRaw('PropertyType, YearDate AS year, ROUND(AVG(Price)) AS avg_price')
                    ->where('County', $county)
                    ->where('PPDCategoryType', $ppd)
                    ->groupBy('PropertyType', 'YearDate')
                    ->orderBy('PropertyType')
                    ->orderBy('YearDate')
                    ->get()
                    ->groupBy('PropertyType');

                foreach ($priceByType as $type => $rows) {
                    Cache::put('county:priceHistory:v3:cat' . $ppd . ':' . $county . ':type:' . $type, $rows->values(), $ttl);
                }
            }

            // SALES HISTORY (Yearly COUNT)
            if ($only === 'all' || $only === 'sales') {
                $sales = DB::table('land_registry')
                    ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('County', $county)
                    ->where('PPDCategoryType', $ppd)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
                Cache::put('county:salesHistory:v2:cat' . $ppd . ':' . $county, $sales, $ttl);

                // Additionally warm per-property-type county sales history (v3)
                $salesByType = DB::table(DB::raw('land_registry FORCE INDEX (idx_county)'))
                    ->selectRaw('PropertyType, YearDate AS year, COUNT(*) AS total_sales')
                    ->where('County', $county)
                    ->where('PPDCategoryType', $ppd)
                    ->groupBy('PropertyType', 'YearDate')
                    ->orderBy('PropertyType')
                    ->orderBy('YearDate')
                    ->get()
                    ->groupBy('PropertyType');

                foreach ($salesByType as $type => $rows) {
                    Cache::put('county:salesHistory:v3:cat' . $ppd . ':' . $county . ':type:' . $type, $rows->values(), $ttl);
                }
            }

            // PROPERTY TYPES (Count of rows by type over full dataset)
            if ($only === 'all' || $only === 'types') {
                $propertyTypeMap = [
                    'D' => 'Detached',
                    'S' => 'Semi-Detached',
                    'T' => 'Terraced',
                    'F' => 'Flat',
                    'O' => 'Other',
                ];

                $types = DB::table(DB::raw('land_registry FORCE INDEX (idx_county)'))
                    ->select('PropertyType', DB::raw('COUNT(*) as property_count'))
                    ->where('County', $county)
                    ->where('PPDCategoryType', $ppd)
                    ->groupBy('PropertyType')
                    ->orderByDesc('property_count')
                    ->get()
                    ->map(function ($row) use ($propertyTypeMap) {
                        return [
                            'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                            'value' => (int)$row->property_count,
                        ];
                    });
                Cache::put('county:types:v2:cat' . $ppd . ':' . $county, $types, $ttl);
            }

            // Gentle throttle to avoid hammering local MySQL
            usleep(100_000); // 100ms
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('County cache warm complete.');
        return self::SUCCESS;
    }
}
