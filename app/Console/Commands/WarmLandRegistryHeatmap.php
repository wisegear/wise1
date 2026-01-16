<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarmLandRegistryHeatmap extends Command
{
    protected $signature = 'property:heatmap-warm {--force : Rebuild even if cache exists}';
    protected $description = 'Warm the Land Registry heatmap cache (England & Wales only).';

    public function handle(): int
    {
        $cacheKey = 'land_registry_heatmap:lsoa21:v2';

        if (Cache::has($cacheKey) && !$this->option('force')) {
            $this->info('Heatmap cache already exists. Use --force to rebuild.');
            return self::SUCCESS;
        }

        $this->info('Building heatmap cache. This may take a while...');

        $hasLsoaGeo = Schema::hasTable('lsoa21_ruc_geo');
        $hasMapTable = Schema::hasTable('lsoa_2011_to_2021');
        $lsoa21Expr = $hasMapTable ? "COALESCE(o.lsoa21, m11.LSOA21CD)" : "o.lsoa21";

        $query = DB::table('land_registry as lr')
            ->join('onspd as o', DB::raw("REPLACE(o.pcds, ' ', '')"), '=', DB::raw("REPLACE(lr.Postcode, ' ', '')"))
            ->whereIn('lr.PPDCategoryType', ['A', 'B'])
            ->whereNotNull(DB::raw($lsoa21Expr))
            ->where(function ($q) use ($lsoa21Expr) {
                $q->where(DB::raw($lsoa21Expr), 'like', 'E01%')
                    ->orWhere(DB::raw($lsoa21Expr), 'like', 'W01%');
            });

        if ($hasMapTable) {
            $query->leftJoin('lsoa_2011_to_2021 as m11', 'm11.LSOA11CD', '=', 'o.lsoa11');
        }

        if ($hasLsoaGeo) {
            $query->join('lsoa21_ruc_geo as g', DB::raw($lsoa21Expr), '=', 'g.LSOA21CD')
                ->groupBy('g.LSOA21CD', 'g.LAT', 'g.LONG')
                ->select([
                    'g.LAT as lat',
                    'g.LONG as lng',
                    DB::raw('COUNT(*) as count'),
                ]);
        } else {
            $query->whereNotNull('o.lat')
                ->whereNotNull('o.long')
                ->groupBy(DB::raw($lsoa21Expr))
                ->select([
                    DB::raw('AVG(o.lat) as lat'),
                    DB::raw('AVG(o.long) as lng'),
                    DB::raw('COUNT(*) as count'),
                ]);
        }

        $points = $query->get();

        Cache::put($cacheKey, $points, now()->addDays(45));

        $this->info('Heatmap cache written: ' . $points->count() . ' points.');

        return self::SUCCESS;
    }
}
