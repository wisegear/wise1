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
        if (function_exists('set_time_limit')) { set_time_limit(0); }

        $today = Carbon::today();
        $ttl   = now()->addDays(45); // match controller's 45-day TTL cadence
        $ratings = ['A','B','C','D','E','F','G'];

        // Nations to warm: England & Wales (ew) and Scotland (scotland)
        $nations = [
            'ew' => [
                'table'        => 'epc_certificates',
                'dateExpr'     => 'lodgement_date',
                'yearExpr'     => 'YEAR(lodgement_date)',
                'currentCol'   => 'current_energy_rating',
                'potentialCol' => 'potential_energy_rating',
                'since'        => Carbon::create(2008, 1, 1),
            ],
            'scotland' => [
                'table'        => 'epc_certificates_scotland',
                'dateExpr'     => "STR_TO_DATE(LODGEMENT_DATE, '%Y-%m-%d')",
                'yearExpr'     => "SUBSTRING(LODGEMENT_DATE,1,4)",
                'currentCol'   => 'CURRENT_ENERGY_RATING',
                'potentialCol' => 'POTENTIAL_ENERGY_RATING',
                'since'        => Carbon::create(2015, 1, 1),
            ],
        ];

        $ck = function (string $nation, string $key) {
            return "epc:{$nation}:{$key}";
        };

        foreach ($nations as $nation => $cfg) {
            $this->line("→ Warming {$nation} ({$cfg['table']})...");

            // 1) Stats (totals & recency)
            $maxDate = DB::table($cfg['table'])
                ->selectRaw("MAX({$cfg['dateExpr']}) as d")
                ->value('d');

            $last30FromLatest = $maxDate ? Carbon::parse($maxDate)->copy()->subDays(30) : $today->copy()->subDays(30);

            $last30Count = $maxDate
                ? (int) DB::table($cfg['table'])
                    ->whereBetween(DB::raw($cfg['dateExpr']), [$last30FromLatest, $maxDate])
                    ->count()
                : 0;

            $last365Count = (int) DB::table($cfg['table'])
                ->whereBetween(DB::raw($cfg['dateExpr']), [$today->copy()->subDays(365), $today])
                ->count();

            $stats = [
                'total'            => (int) DB::table($cfg['table'])->count(),
                'latest_lodgement' => $maxDate,
                'last30_count'     => $last30Count,
                'last365_count'    => $last365Count,
            ];
            Cache::put($ck($nation, 'stats'), $stats, $ttl);
            $this->line("✔ {$nation}: stats cached");

            // 2) Certificates by year
            $byYear = DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->groupBy('yr')
                ->orderBy('yr', 'asc')
                ->get();
            Cache::put($ck($nation, 'byYear'), $byYear, $ttl);
            $this->line("✔ {$nation}: byYear cached");

            // 3) Current energy ratings by year (A–G)
            $ratingByYear = DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, {$cfg['currentCol']} as rating, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->whereIn($cfg['currentCol'], $ratings)
                ->groupBy('yr', 'rating')
                ->orderBy('yr', 'asc')
                ->orderByRaw("FIELD({$cfg['currentCol']}, 'A','B','C','D','E','F','G')")
                ->get();
            Cache::put($ck($nation, 'ratingByYear'), $ratingByYear, $ttl);
            $this->line("✔ {$nation}: ratingByYear cached");

            // 3b) Potential energy ratings by year (A–G)
            $potentialByYear = DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, {$cfg['potentialCol']} as rating, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->whereIn($cfg['potentialCol'], $ratings)
                ->groupBy('yr', 'rating')
                ->orderBy('yr', 'asc')
                ->orderByRaw("FIELD({$cfg['potentialCol']}, 'A','B','C','D','E','F','G')")
                ->get();
            Cache::put($ck($nation, 'potentialByYear'), $potentialByYear, $ttl);
            $this->line("✔ {$nation}: potentialByYear cached");

            // 4) Distribution of current ratings (A–G, Other, Unknown)
            $ratingDist = DB::table($cfg['table'])
                ->selectRaw("
                    CASE
                        WHEN {$cfg['currentCol']} IN ('A','B','C','D','E','F','G') THEN {$cfg['currentCol']}
                        WHEN {$cfg['currentCol']} IS NULL THEN 'Unknown'
                        ELSE 'Other'
                    END as rating,
                    COUNT(*) as cnt
                ")
                ->groupBy('rating')
                ->orderByRaw("FIELD(rating, 'A','B','C','D','E','F','G','Other','Unknown')")
                ->get();
            Cache::put($ck($nation, 'ratingDist'), $ratingDist, $ttl);
            $this->line("✔ {$nation}: ratingDist cached");
        }

        $elapsed = round((microtime(true) - $started), 2);
        $this->info("Done in {$elapsed}s");
        return self::SUCCESS;
    }
}
