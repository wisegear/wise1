<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WarmAllDistrictCaches extends Command
{
    protected $signature = 'reports:warm-districts
                            {--ppd=A : PPDCategoryType filter (A or B)}
                            {--limit=0 : Limit number of districts to warm}
                            {--only=all : Only warm one group: all|price|sales|types}';

    protected $description = 'Precompute and cache district-level datasets (price history, sales history, property types). Reuses county results for unitary authorities (District == County).';

    public function handle(): int
    {
        $ppd = strtoupper((string) $this->option('ppd')) === 'B' ? 'B' : 'A';
        $only = strtolower((string) $this->option('only'));
        $limit = (int) $this->option('limit');

        $ttl = now()->addDays(45);

        // Distinct non-empty districts
        $districts = DB::table('land_registry')
            ->selectRaw('TRIM(District) AS district')
            ->whereNotNull('District')
            ->whereRaw("TRIM(District) <> ''")
            ->distinct()
            ->orderBy('district')
            ->pluck('district');

        if ($limit > 0) {
            $districts = $districts->take($limit);
        }

        $steps = $districts->count() * (($only === 'all') ? 3 : 1);
        $this->info('Warming caches for ' . $districts->count() . ' districts (PPD=' . $ppd . ', only=' . $only . ')...');
        $bar = $this->output->createProgressBar($steps);
        // Fancy progress bar with ETA, elapsed time, memory, and current district name
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% elapsed | %estimated:-6s% eta | %memory:6s% | %message%');
        $bar->setBarCharacter('=');
        $bar->setEmptyBarCharacter(' ');
        $bar->setProgressCharacter('>');
        $bar->setRedrawFrequency(1);
        $bar->start();

        if ($only === 'all' || $only === 'price') {
            $priceRows = DB::table('land_registry')
                ->selectRaw('TRIM(District) AS district, YearDate as year, ROUND(AVG(Price)) as avg_price')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('District')
                ->whereRaw("TRIM(District) <> ''")
                ->groupBy('district', 'YearDate')
                ->orderBy('district')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('district');
            foreach ($priceRows as $district => $rows) {
                $district = trim((string) $district);
                Cache::put('district:priceHistory:v2:cat' . $ppd . ':' . $district, $rows, $ttl);
                $bar->setMessage('Price: ' . $district);
                $bar->advance();
            }
            // Additionally warm per-property-type district price history (v3)
            $priceRowsByType = DB::table('land_registry')
                ->selectRaw('TRIM(District) AS district, PropertyType, YearDate as year, ROUND(AVG(Price)) as avg_price')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('District')
                ->whereRaw("TRIM(District) <> ''")
                ->groupBy('district', 'PropertyType', 'YearDate')
                ->orderBy('district')
                ->orderBy('PropertyType')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('district');

            foreach ($priceRowsByType as $district => $rows) {
                $district = trim((string) $district);
                // Group rows by PropertyType within each district
                $rowsByType = $rows->groupBy('PropertyType');

                foreach ($rowsByType as $type => $series) {
                    // v3 district price history is keyed by district + property type
                    Cache::put('district:priceHistory:v3:cat' . $ppd . ':' . $district . ':type:' . $type, $series->values(), $ttl);
                    // We do not advance the progress bar here, as this is extra warming work.
                }
            }
        }

        if ($only === 'all' || $only === 'sales') {
            $salesRows = DB::table('land_registry')
                ->selectRaw('TRIM(District) AS district, YearDate as year, COUNT(*) as total_sales')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('District')
                ->whereRaw("TRIM(District) <> ''")
                ->groupBy('district', 'YearDate')
                ->orderBy('district')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('district');
            foreach ($salesRows as $district => $rows) {
                $district = trim((string) $district);
                Cache::put('district:salesHistory:v2:cat' . $ppd . ':' . $district, $rows, $ttl);
                $bar->setMessage('Sales: ' . $district);
                $bar->advance();
            }
            // Additionally warm per-property-type district sales history (v3)
            $salesRowsByType = DB::table('land_registry')
                ->selectRaw('TRIM(District) AS district, PropertyType, YearDate as year, COUNT(*) as total_sales')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('District')
                ->whereRaw("TRIM(District) <> ''")
                ->groupBy('district', 'PropertyType', 'YearDate')
                ->orderBy('district')
                ->orderBy('PropertyType')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('district');

            foreach ($salesRowsByType as $district => $rows) {
                $district = trim((string) $district);
                $rowsByType = $rows->groupBy('PropertyType');

                foreach ($rowsByType as $type => $series) {
                    Cache::put('district:salesHistory:v3:cat' . $ppd . ':' . $district . ':type:' . $type, $series->values(), $ttl);
                    // As above, we do not advance the progress bar for these extra series.
                }
            }
        }

        if ($only === 'all' || $only === 'types') {
            $map = [ 'D' => 'Detached', 'S' => 'Semi-Detached', 'T' => 'Terraced', 'F' => 'Flat', 'O' => 'Other' ];
            $typeRows = DB::table('land_registry')
                ->selectRaw('TRIM(District) AS district, PropertyType, COUNT(*) as property_count')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('District')
                ->whereRaw("TRIM(District) <> ''")
                ->groupBy('district', 'PropertyType')
                ->orderBy('district')
                ->get()
                ->groupBy('district');
            foreach ($typeRows as $district => $rows) {
                $district = trim((string) $district);
                $mapped = $rows->map(function ($row) use ($map) {
                    return [ 'label' => $map[$row->PropertyType] ?? $row->PropertyType, 'value' => (int) $row->property_count ];
                });
                Cache::put('district:types:v2:cat' . $ppd . ':' . $district, $mapped, $ttl);
                $bar->setMessage('Types: ' . $district);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('District cache warm complete.');
        return self::SUCCESS;
    }
}
