<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class EpcWarmer extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'epc:warm-dashboard';

    /**
     * The console command description.
     */
    protected $description = 'Precompute and cache EPC dashboard queries for faster page loads';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Warming EPC dashboard cache...');
        $started = microtime(true);
        DB::connection()->disableQueryLog();

        // Time windows
        $today     = Carbon::today();
        $last30    = $today->copy()->subDays(30);
        $last365   = $today->copy()->subDays(365);
        $since2008 = Carbon::create(2008, 1, 1);

        // Cache keys (must match controller keys)
        $kStats        = 'epc.stats';
        $kByYear       = 'epc.byYear';
        $kRatingDist   = 'epc.ratingDist';
        $kAvgFloorArea = 'epc.avgFloorArea';
        $kTopPostcodes = 'epc.topPostcodes';

        // TTL (match controller TTL)
        $ttl = now()->addDays(45); // monthly feed cadence – cache for 45 days

        // 1) Totals & recency
        $maxDate = DB::table('epc_certificates')->max('lodgement_date');
        $last30FromLatest = $maxDate
            ? Carbon::parse($maxDate)->copy()->subDays(30)
            : $today->copy()->subDays(30);

        $stats = [
            'total'            => (int) DB::table('epc_certificates')->count(),
            'latest_lodgement' => $maxDate,
            'last30_count'     => $maxDate
                ? (int) DB::table('epc_certificates')->whereBetween('lodgement_date', [$last30FromLatest, $maxDate])->count()
                : 0,
            // keep last 12 months as rolling from today for now
            'last365_count'    => (int) DB::table('epc_certificates')->whereBetween('lodgement_date', [$last365, $today])->count(),
        ];
        Cache::put($kStats, $stats, $ttl);
        $this->line('✔ stats cached');

        // 2) EPCs by year (since 2008)
        $byYear = DB::table('epc_certificates')
            ->selectRaw('YEAR(lodgement_date) as yr, COUNT(*) as cnt')
            ->whereNotNull('lodgement_date')
            ->where('lodgement_date', '>=', $since2008)
            ->groupBy('yr')
            ->orderBy('yr', 'asc')
            ->get();
        Cache::put($kByYear, $byYear, $ttl);
        $this->line('✔ byYear cached');

        // 3) Distribution of current energy ratings (single pass, no GROUP BY)
        $ratingCounts = DB::table('epc_certificates')
            ->selectRaw("
                SUM(current_energy_rating = 'A') as A,
                SUM(current_energy_rating = 'B') as B,
                SUM(current_energy_rating = 'C') as C,
                SUM(current_energy_rating = 'D') as D,
                SUM(current_energy_rating = 'E') as E,
                SUM(current_energy_rating = 'F') as F,
                SUM(current_energy_rating = 'G') as G,
                SUM(current_energy_rating IS NULL) as Unknown,
                SUM(current_energy_rating IS NOT NULL AND current_energy_rating NOT IN ('A','B','C','D','E','F','G')) as Other
            ")
            ->first();

        $ratingDist = collect(['A','B','C','D','E','F','G','Other','Unknown'])->map(function ($key) use ($ratingCounts) {
            return (object) [
                'rating' => $key,
                'cnt'    => (int) ($ratingCounts->{$key} ?? 0),
            ];
        });
        Cache::put($kRatingDist, $ratingDist, $ttl);
        $this->line('✔ ratingDist cached');

        // 4) Average floor area by property type (top 10)
        $avgFloorArea = DB::table('epc_certificates')
            ->selectRaw('property_type, COUNT(*) as cnt, ROUND(AVG(total_floor_area),1) as avg_m2')
            ->whereNotNull('property_type')
            ->groupBy('property_type')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();
        Cache::put($kAvgFloorArea, $avgFloorArea, $ttl);
        $this->line('✔ avgFloorArea cached');

        // 5) Busiest postcodes in the last 12 months (top 10)
        $topPostcodes = DB::table('epc_certificates')
            ->selectRaw('postcode, COUNT(*) as cnt')
            ->whereNotNull('postcode')
            ->whereBetween('lodgement_date', [$last365, $today])
            ->groupBy('postcode')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();
        Cache::put($kTopPostcodes, $topPostcodes, $ttl);
        $this->line('✔ topPostcodes cached');

        $elapsed = round((microtime(true) - $started), 2);
        $this->info("Done in {$elapsed}s");
        return self::SUCCESS;
    }
}
