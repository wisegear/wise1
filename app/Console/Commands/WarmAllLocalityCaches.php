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

        // Distinct non-empty localities
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

        $count  = $localities->count();
        $steps  = $count * (($only === 'all') ? 3 : 1);

        $this->info("Warming caches for {$count} localities (PPD={$ppd}, only={$only})...");
        $bar = $this->output->createProgressBar($steps);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% elapsed | %estimated:-6s% eta | %memory:6s% | %message%');
        $bar->setBarCharacter('=');
        $bar->setEmptyBarCharacter(' ');
        $bar->setProgressCharacter('>');
        $bar->setRedrawFrequency(1);
        $bar->start();

        // ---- PRICE HISTORY ----
        if ($only === 'all' || $only === 'price') {
            $priceRows = DB::table('land_registry')
                ->select('Locality', 'YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('Locality')
                ->where('Locality', '!=', '')
                ->groupBy('Locality', 'YearDate')
                ->orderBy('Locality')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('Locality');

            foreach ($priceRows as $locality => $rows) {
                Cache::put('locality:priceHistory:v2:cat' . $ppd . ':' . $locality, $rows, $ttl);
                $bar->setMessage('Price: ' . $locality);
                $bar->advance();
            }

            // Additionally warm per-property-type locality price history (v3)
            $priceRowsByType = DB::table('land_registry')
                ->select('Locality', 'PropertyType', 'YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('Locality')
                ->where('Locality', '!=', '')
                ->groupBy('Locality', 'PropertyType', 'YearDate')
                ->orderBy('Locality')
                ->orderBy('PropertyType')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('Locality');

            foreach ($priceRowsByType as $locality => $rows) {
                $rowsByType = $rows->groupBy('PropertyType');

                foreach ($rowsByType as $type => $series) {
                    Cache::put(
                        'locality:priceHistory:v3:cat' . $ppd . ':' . $locality . ':type:' . $type,
                        $series->values(),
                        $ttl
                    );
                    // No progress advance here – this is extra warming work.
                }
            }
        }

        // ---- SALES HISTORY ----
        if ($only === 'all' || $only === 'sales') {
            $salesRows = DB::table('land_registry')
                ->select('Locality', 'YearDate as year', DB::raw('COUNT(*) as total_sales'))
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('Locality')
                ->where('Locality', '!=', '')
                ->groupBy('Locality', 'YearDate')
                ->orderBy('Locality')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('Locality');

            foreach ($salesRows as $locality => $rows) {
                Cache::put('locality:salesHistory:v2:cat' . $ppd . ':' . $locality, $rows, $ttl);
                $bar->setMessage('Sales: ' . $locality);
                $bar->advance();
            }

            // Additionally warm per-property-type locality sales history (v3)
            $salesRowsByType = DB::table('land_registry')
                ->select('Locality', 'PropertyType', 'YearDate as year', DB::raw('COUNT(*) as total_sales'))
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('Locality')
                ->where('Locality', '!=', '')
                ->groupBy('Locality', 'PropertyType', 'YearDate')
                ->orderBy('Locality')
                ->orderBy('PropertyType')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('Locality');

            foreach ($salesRowsByType as $locality => $rows) {
                $rowsByType = $rows->groupBy('PropertyType');

                foreach ($rowsByType as $type => $series) {
                    Cache::put(
                        'locality:salesHistory:v3:cat' . $ppd . ':' . $locality . ':type:' . $type,
                        $series->values(),
                        $ttl
                    );
                    // No progress advance here either.
                }
            }
        }

        // ---- PROPERTY TYPES ----
        if ($only === 'all' || $only === 'types') {
            $map = [
                'D' => 'Detached',
                'S' => 'Semi-Detached',
                'T' => 'Terraced',
                'F' => 'Flat',
                'O' => 'Other',
            ];

            $typeRows = DB::table('land_registry')
                ->select('Locality', 'PropertyType', DB::raw('COUNT(*) as property_count'))
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('Locality')
                ->where('Locality', '!=', '')
                ->groupBy('Locality', 'PropertyType')
                ->orderBy('Locality')
                ->get()
                ->groupBy('Locality');

            foreach ($typeRows as $locality => $rows) {
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
