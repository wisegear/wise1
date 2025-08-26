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
            ->select('District')
            ->whereNotNull('District')
            ->where('District', '!=', '')
            ->distinct()
            ->orderBy('District')
            ->pluck('District');

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
                ->select('District', 'YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('District')
                ->where('District', '!=', '')
                ->groupBy('District', 'YearDate')
                ->orderBy('District')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('District');
            foreach ($priceRows as $district => $rows) {
                Cache::put('district:priceHistory:v2:cat' . $ppd . ':' . $district, $rows, $ttl);
                $bar->setMessage('Price: ' . $district);
                $bar->advance();
            }
        }

        if ($only === 'all' || $only === 'sales') {
            $salesRows = DB::table('land_registry')
                ->select('District', 'YearDate as year', DB::raw('COUNT(*) as total_sales'))
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('District')
                ->where('District', '!=', '')
                ->groupBy('District', 'YearDate')
                ->orderBy('District')
                ->orderBy('YearDate')
                ->get()
                ->groupBy('District');
            foreach ($salesRows as $district => $rows) {
                Cache::put('district:salesHistory:v2:cat' . $ppd . ':' . $district, $rows, $ttl);
                $bar->setMessage('Sales: ' . $district);
                $bar->advance();
            }
        }

        if ($only === 'all' || $only === 'types') {
            $map = [ 'D' => 'Detached', 'S' => 'Semi-Detached', 'T' => 'Terraced', 'F' => 'Flat', 'O' => 'Other' ];
            $typeRows = DB::table('land_registry')
                ->select('District', 'PropertyType', DB::raw('COUNT(*) as property_count'))
                ->where('PPDCategoryType', $ppd)
                ->whereNotNull('District')
                ->where('District', '!=', '')
                ->groupBy('District', 'PropertyType')
                ->orderBy('District')
                ->get()
                ->groupBy('District');
            foreach ($typeRows as $district => $rows) {
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
