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

        // Reduce memory usage in long runs
        DB::connection()->disableQueryLog();

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

        // ---- PRICE HISTORY (streamed)
        if ($only === 'all' || $only === 'price') {
            // v2: town price history (stream rows, flush per-town)
            $cursor = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, YearDate AS year, ROUND(AVG(Price)) AS avg_price')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'YearDate')
                ->orderBy('town')
                ->orderBy('YearDate')
                ->cursor();

            $currentTown = null;
            $bucket = [];

            foreach ($cursor as $row) {
                $town = trim((string) $row->town);
                if ($skipDup && $dupTowns->contains($town)) {
                    continue;
                }

                if ($currentTown === null) {
                    $currentTown = $town;
                }

                // Town changed -> flush previous
                if ($town !== $currentTown) {
                    $bar->setMessage('Price: ' . $currentTown);
                    Cache::put('town:priceHistory:v2:cat' . $ppd . ':' . $currentTown, collect($bucket), $ttl);
                    $bar->advance();

                    $currentTown = $town;
                    $bucket = [];
                }

                $bucket[] = $row;
            }

            // Flush last town
            if ($currentTown !== null) {
                $bar->setMessage('Price: ' . $currentTown);
                Cache::put('town:priceHistory:v2:cat' . $ppd . ':' . $currentTown, collect($bucket), $ttl);
                $bar->advance();
            }

            // v3: per-property-type town price history (stream rows, flush per (town,type))
            $cursorByType = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, PropertyType, YearDate AS year, ROUND(AVG(Price)) AS avg_price')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'PropertyType', 'YearDate')
                ->orderBy('town')
                ->orderBy('PropertyType')
                ->orderBy('YearDate')
                ->cursor();

            $currentTown = null;
            $currentType = null;
            $bucket = [];

            foreach ($cursorByType as $row) {
                $town = trim((string) $row->town);
                $type = (string) $row->PropertyType;

                if ($skipDup && $dupTowns->contains($town)) {
                    continue;
                }

                if ($currentTown === null) {
                    $currentTown = $town;
                    $currentType = $type;
                }

                // (town,type) changed -> flush previous
                if ($town !== $currentTown || $type !== $currentType) {
                    if ($currentTown !== null && $currentType !== null) {
                        Cache::put(
                            'town:priceHistory:v3:cat' . $ppd . ':' . $currentTown . ':type:' . $currentType,
                            collect($bucket)->values(),
                            $ttl
                        );
                    }

                    $currentTown = $town;
                    $currentType = $type;
                    $bucket = [];
                }

                $bucket[] = $row;
            }

            // Flush last (town,type)
            if ($currentTown !== null && $currentType !== null) {
                Cache::put(
                    'town:priceHistory:v3:cat' . $ppd . ':' . $currentTown . ':type:' . $currentType,
                    collect($bucket)->values(),
                    $ttl
                );
            }
        }

        // ---- SALES HISTORY (streamed)
        if ($only === 'all' || $only === 'sales') {
            // v2: town sales history (stream rows, flush per-town)
            $cursor = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, YearDate AS year, COUNT(*) AS total_sales')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'YearDate')
                ->orderBy('town')
                ->orderBy('YearDate')
                ->cursor();

            $currentTown = null;
            $bucket = [];

            foreach ($cursor as $row) {
                $town = trim((string) $row->town);
                if ($skipDup && $dupTowns->contains($town)) {
                    continue;
                }

                if ($currentTown === null) {
                    $currentTown = $town;
                }

                if ($town !== $currentTown) {
                    $bar->setMessage('Sales: ' . $currentTown);
                    Cache::put('town:salesHistory:v2:cat' . $ppd . ':' . $currentTown, collect($bucket), $ttl);
                    $bar->advance();

                    $currentTown = $town;
                    $bucket = [];
                }

                $bucket[] = $row;
            }

            if ($currentTown !== null) {
                $bar->setMessage('Sales: ' . $currentTown);
                Cache::put('town:salesHistory:v2:cat' . $ppd . ':' . $currentTown, collect($bucket), $ttl);
                $bar->advance();
            }

            // v3: per-property-type town sales history (stream rows, flush per (town,type))
            $cursorByType = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, PropertyType, YearDate AS year, COUNT(*) AS total_sales')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'PropertyType', 'YearDate')
                ->orderBy('town')
                ->orderBy('PropertyType')
                ->orderBy('YearDate')
                ->cursor();

            $currentTown = null;
            $currentType = null;
            $bucket = [];

            foreach ($cursorByType as $row) {
                $town = trim((string) $row->town);
                $type = (string) $row->PropertyType;

                if ($skipDup && $dupTowns->contains($town)) {
                    continue;
                }

                if ($currentTown === null) {
                    $currentTown = $town;
                    $currentType = $type;
                }

                if ($town !== $currentTown || $type !== $currentType) {
                    if ($currentTown !== null && $currentType !== null) {
                        Cache::put(
                            'town:salesHistory:v3:cat' . $ppd . ':' . $currentTown . ':type:' . $currentType,
                            collect($bucket)->values(),
                            $ttl
                        );
                    }

                    $currentTown = $town;
                    $currentType = $type;
                    $bucket = [];
                }

                $bucket[] = $row;
            }

            if ($currentTown !== null && $currentType !== null) {
                Cache::put(
                    'town:salesHistory:v3:cat' . $ppd . ':' . $currentTown . ':type:' . $currentType,
                    collect($bucket)->values(),
                    $ttl
                );
            }
        }

        // ---- PROPERTY TYPES (streamed)
        if ($only === 'all' || $only === 'types') {
            $map = [ 'D' => 'Detached', 'S' => 'Semi', 'T' => 'Terraced', 'F' => 'Flat', 'O' => 'Other' ];

            $cursor = DB::table('land_registry')
                ->selectRaw('TRIM(TownCity) AS town, PropertyType, COUNT(*) AS property_count')
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('TownCity')
                ->whereRaw("TRIM(TownCity) <> ''")
                ->groupBy('town', 'PropertyType')
                ->orderBy('town')
                ->orderBy('PropertyType')
                ->cursor();

            $currentTown = null;
            $bucket = [];

            foreach ($cursor as $row) {
                $town = trim((string) $row->town);

                if ($skipDup && $dupTowns->contains($town)) {
                    continue;
                }

                if ($currentTown === null) {
                    $currentTown = $town;
                }

                if ($town !== $currentTown) {
                    $bar->setMessage('Types: ' . $currentTown);
                    $mapped = collect($bucket)->map(function ($r) use ($map) {
                        return [ 'label' => $map[$r->PropertyType] ?? $r->PropertyType, 'value' => (int) $r->property_count ];
                    });
                    Cache::put('town:types:v2:cat' . $ppd . ':' . $currentTown, $mapped, $ttl);
                    $bar->advance();

                    $currentTown = $town;
                    $bucket = [];
                }

                $bucket[] = $row;
            }

            if ($currentTown !== null) {
                $bar->setMessage('Types: ' . $currentTown);
                $mapped = collect($bucket)->map(function ($r) use ($map) {
                    return [ 'label' => $map[$r->PropertyType] ?? $r->PropertyType, 'value' => (int) $r->property_count ];
                });
                Cache::put('town:types:v2:cat' . $ppd . ':' . $currentTown, $mapped, $ttl);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Town cache warm complete.');
        return self::SUCCESS;
    }
}