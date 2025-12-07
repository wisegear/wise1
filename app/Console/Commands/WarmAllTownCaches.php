<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WarmAllTownCaches extends Command
{
    protected $signature = 'reports:warm-towns
                            {--ppd=A : PPDCategoryType filter (A or B)}
                            {--limit=0 : Limit number of TownCity values to warm}
                            {--only=all : Only warm one group: all|price|sales|types}
                            {--skip-duplicates : Skip towns that are identical to their District (e.g., LIVERPOOL/LIVERPOOL)}';

    protected $description = 'Precompute and cache TownCity-level datasets (price history, sales history, property types) using bulk queries.';

    public function handle(): int
    {
        $ppd  = strtoupper((string) $this->option('ppd')) === 'B' ? 'B' : 'A';
        $only = strtolower((string) $this->option('only'));
        $limit = (int) $this->option('limit');
        $skipDup = (bool) $this->option('skip-duplicates');

        $ttl = now()->addDays(45);

        // Build the canonical Town list (trimmed, non-empty)
        $towns = DB::table('land_registry')
            ->selectRaw('TRIM(TownCity) AS town')
            ->whereNotNull('TownCity')
            ->whereRaw("TRIM(TownCity) <> ''")
            ->distinct()
            ->orderBy('town')
            ->pluck('town');

        // Optionally skip Towns that are effectively the same as their District
        $dupTowns = collect();
        if ($skipDup) {
            $dupTowns = DB::table('land_registry')
                ->selectRaw('DISTINCT TRIM(TownCity) AS town')
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->whereRaw('TRIM(TownCity) = TRIM(District)')
                ->pluck('town');
            $towns = $towns->reject(fn ($t) => $dupTowns->contains($t))->values();
        }

        if ($limit > 0) {
            $towns = $towns->take($limit);
        }

        $metrics = match ($only) {
            'price' => 1,
            'sales' => 1,
            'types' => 1,
            default => 3,
        };

        $this->info('Warming caches for ' . $towns->count() . ' towns (PPD=' . $ppd . ', only=' . $only . ($skipDup ? ', skip-duplicates' : '') . ')...');
        $bar = $this->output->createProgressBar($towns->count() * $metrics);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% elapsed | %estimated:-6s% eta | %memory:6s% | %message%');
        $bar->setBarCharacter('=');
        $bar->setEmptyBarCharacter(' ');
        $bar->setProgressCharacter('>');
        $bar->setRedrawFrequency(1);
        $bar->start();

        // ---- PRICE HISTORY (all towns at once)
        if ($only === 'all' || $only === 'price') {
            $priceRows = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, YearDate AS year, ROUND(AVG(Price)) AS avg_price')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'YearDate')
                ->orderBy('town')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('town');

            foreach ($priceRows as $town => $rows) {
                if ($skipDup && $dupTowns->contains($town)) continue;
                $bar->setMessage('Price: ' . $town);
                Cache::put('town:priceHistory:v2:cat' . $ppd . ':' . $town, $rows, $ttl);
                $bar->advance();
            }

            // Additionally warm per-property-type town price history for v3 keys
            $priceRowsByType = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, PropertyType, YearDate AS year, ROUND(AVG(Price)) AS avg_price')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'PropertyType', 'YearDate')
                ->orderBy('town')
                ->orderBy('PropertyType')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('town');

            foreach ($priceRowsByType as $town => $rows) {
                if ($skipDup && $dupTowns->contains($town)) continue;

                // Group rows by PropertyType within each town
                $rowsByType = $rows->groupBy('PropertyType');

                foreach ($rowsByType as $type => $series) {
                    // v3 town price history is keyed by town + property type
                    Cache::put('town:priceHistory:v3:cat' . $ppd . ':' . $town . ':type:' . $type, $series->values(), $ttl);
                    // We intentionally do not advance the progress bar here to avoid
                    // over-counting beyond the precomputed max; this is extra warming.
                }
            }
        }

        // ---- SALES HISTORY (all towns at once)
        if ($only === 'all' || $only === 'sales') {
            $salesRows = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, YearDate AS year, COUNT(*) AS total_sales')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'YearDate')
                ->orderBy('town')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('town');

            foreach ($salesRows as $town => $rows) {
                if ($skipDup && $dupTowns->contains($town)) continue;
                $bar->setMessage('Sales: ' . $town);
                Cache::put('town:salesHistory:v2:cat' . $ppd . ':' . $town, $rows, $ttl);
                $bar->advance();
            }

            // Additionally warm per-property-type town sales history for v3 keys
            $salesRowsByType = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, PropertyType, YearDate AS year, COUNT(*) AS total_sales')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'PropertyType', 'YearDate')
                ->orderBy('town')
                ->orderBy('PropertyType')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('town');

            foreach ($salesRowsByType as $town => $rows) {
                if ($skipDup && $dupTowns->contains($town)) continue;

                $rowsByType = $rows->groupBy('PropertyType');

                foreach ($rowsByType as $type => $series) {
                    Cache::put('town:salesHistory:v3:cat' . $ppd . ':' . $town . ':type:' . $type, $series->values(), $ttl);
                    // As above, we do not advance the progress bar for these extra series.
                }
            }
        }

        // ---- PROPERTY TYPES (all towns at once)
        if ($only === 'all' || $only === 'types') {
            $map = [ 'D' => 'Detached', 'S' => 'Semi', 'T' => 'Terraced', 'F' => 'Flat', 'O' => 'Other' ];

            $typeRows = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, PropertyType, COUNT(*) AS property_count')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'PropertyType')
                ->orderBy('town')
                ->get()
                ->groupBy('town');

            foreach ($typeRows as $town => $rows) {
                if ($skipDup && $dupTowns->contains($town)) continue;
                $bar->setMessage('Types: ' . $town);
                $mapped = $rows->map(function ($row) use ($map) {
                    return [ 'label' => $map[$row->PropertyType] ?? $row->PropertyType, 'value' => (int) $row->property_count ];
                });
                Cache::put('town:types:v2:cat' . $ppd . ':' . $town, $mapped, $ttl);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Town cache warm complete.');
        return self::SUCCESS;
    }
}