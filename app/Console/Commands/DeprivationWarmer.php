<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;

class DeprivationWarmer extends Command
{
    protected $signature = 'imd:warm {--pages=5} {--per=25} {--deciles=} {--sort=imd_decile} {--dir=asc}';

    protected $description = 'Warm cached pages for the IMD (Deprivation) index to speed up first loads.';

    public function handle(): int
    {
        $this->comment('Priming IMD index cache...');

        // Warm total rank cache
        Cache::rememberForever('imd.total_rank', function () {
            $n = (int) (DB::table('imd2019')
                ->where('measurement_norm', 'rank')
                ->where('iod_norm', 'like', 'a. index of multiple deprivation%')
                ->max('Value') ?? 0);
            return $n ?: 32844;
        });

        $pages  = max(1, (int) $this->option('pages'));
        $per    = max(5, (int) $this->option('per'));
        $sort   = (string) $this->option('sort');
        $dir    = strtolower((string) $this->option('dir')) === 'desc' ? 'desc' : 'asc';

        // Decile list: from option or sensible defaults
        $decilesOpt = trim((string) $this->option('deciles'));
        $deciles = $decilesOpt !== ''
            ? array_values(array_filter(array_map('intval', explode(',', $decilesOpt)), fn($d)=>$d>=1 && $d<=10))
            : [null, 1, 5, 10];

        $sortCombos = $sort === 'all'
            ? [['imd_decile','asc'], ['imd_rank','asc'], ['lsoa_name','asc']]
            : [[$sort, $dir]];

        $count = 0;

        foreach ($deciles as $decile) {
            foreach ($sortCombos as [$s,$d]) {
                for ($page = 1; $page <= $pages; $page++) {

                    $rows = DB::table('lsoa21_ruc_geo as g')
                        ->leftJoin('lsoa_2011_to_2021 as map', 'map.LSOA21CD', '=', 'g.LSOA21CD')
                        ->leftJoin('imd2019 as imd_dec', function ($j) {
                            $j->on('imd_dec.FeatureCode', '=', 'map.LSOA11CD')
                              ->where('imd_dec.measurement_norm', '=', 'decile')
                              ->where('imd_dec.iod_norm', 'like', 'a. index of multiple deprivation%');
                        })
                        ->leftJoin('imd2019 as imd_rank', function ($j) {
                            $j->on('imd_rank.FeatureCode', '=', 'map.LSOA11CD')
                              ->where('imd_rank.measurement_norm', '=', 'rank')
                              ->where('imd_rank.iod_norm', 'like', 'a. index of multiple deprivation%');
                        })
                        ->where('g.LSOA21CD', 'like', 'E%')
                        ->select([
                            'g.LSOA21CD as lsoa21cd',
                            'g.LSOA21NM as lsoa_name',
                            'g.RUC21CD', 'g.RUC21NM', 'g.Urban_rura',
                            'g.LAT', 'g.LONG',
                            'imd_dec.Value as imd_decile',
                            'imd_rank.Value as imd_rank',
                        ]);

                    if (!is_null($decile)) {
                        $rows->where('imd_dec.Value', '=', (int)$decile);
                    }

                    $rows->orderBy($s, $d)->orderBy('lsoa_name');

                    $params = [
                        'q' => '',
                        'decile' => $decile ?? '',
                        'ruc' => '',
                        'lad' => '',
                        'sort' => $s,
                        'dir' => $d,
                        'per' => $per,
                        'page' => $page,
                    ];

                    $cacheKey = 'imd:index:' . md5(json_encode($params));

                    Paginator::currentPageResolver(function () use ($page) {
                        return $page;
                    });

                    $ttl = now()->addDays(30);
                    Cache::remember($cacheKey, $ttl, function () use ($rows, $per, $params) {
                        return $rows->simplePaginate($per)->appends($params);
                    });

                    $count++;
                }
            }
        }

        $this->info("Warmed {$count} cache entries.");
        return self::SUCCESS;
    }
}

