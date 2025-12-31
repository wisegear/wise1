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
                'roomsCol'     => 'number_habitable_rooms',
                'ageCol'       => 'construction_age_band',
                'since'        => Carbon::create(2008, 1, 1),
            ],
            'scotland' => [
                'table'        => 'epc_certificates_scotland',
                'dateExpr'     => "STR_TO_DATE(LODGEMENT_DATE, '%Y-%m-%d')",
                'yearExpr'     => "SUBSTRING(LODGEMENT_DATE,1,4)",
                'currentCol'   => 'CURRENT_ENERGY_RATING',
                'potentialCol' => 'POTENTIAL_ENERGY_RATING',
                'roomsCol'     => 'NUMBER_HABITABLE_ROOMS',
                'ageCol'       => 'CONSTRUCTION_AGE_BAND',
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

            // Normalise tenure values for both nations
            $tenureLabels    = ['Owner-occupied','Rented (private)','Rented (social)'];
            $tenureRawValues = [
                'Owner-occupied','owner-occupied',
                'Rented (private)','rental (private)',
                'Rented (social)','rental (social)',
            ];

            // 3c) Tenure by year (normalised)
            $tenureByYear = DB::table($cfg['table'])
                ->selectRaw("
                    {$cfg['yearExpr']} as yr,
                    CASE
                        WHEN tenure IN ('Owner-occupied','owner-occupied') THEN 'Owner-occupied'
                        WHEN tenure IN ('Rented (private)','rental (private)') THEN 'Rented (private)'
                        WHEN tenure IN ('Rented (social)','rental (social)') THEN 'Rented (social)'
                        ELSE NULL
                    END as tenure,
                    COUNT(*) as cnt
                ")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->whereIn('tenure', $tenureRawValues)
                ->groupBy('yr', 'tenure')
                ->orderBy('yr', 'asc')
                ->orderByRaw("FIELD(tenure, '" . implode("','", $tenureLabels) . "')")
                ->get();

            Cache::put($ck($nation, 'tenureByYear'), $tenureByYear, $ttl);
            $this->line("✔ {$nation}: tenureByYear cached");

            // 3d) Habitable rooms by year (distribution for stacked chart)
            $roomsExpr = ($cfg['table'] === 'epc_certificates_scotland')
                ? "CAST(NULLIF({$cfg['roomsCol']}, '') AS UNSIGNED)"
                : "CAST({$cfg['roomsCol']} AS UNSIGNED)";

            $roomsByYear = DB::table($cfg['table'])
                ->selectRaw("{$cfg['yearExpr']} as yr, {$roomsExpr} as rooms, COUNT(*) as cnt")
                ->whereRaw("{$cfg['dateExpr']} IS NOT NULL")
                ->whereRaw("{$cfg['dateExpr']} >= ?", [$cfg['since']])
                ->whereRaw("{$roomsExpr} BETWEEN 1 AND 20")
                ->groupBy('yr', 'rooms')
                ->orderBy('yr', 'asc')
                ->orderBy('rooms', 'asc')
                ->get();

            Cache::put($ck($nation, 'roomsByYear'), $roomsByYear, $ttl);
            $this->line("✔ {$nation}: roomsByYear cached");

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

            // 5) Construction age distribution (matches controller logic)
            if ($nation === 'scotland') {
                // Scotland: official bands, including "2008 onwards"
                $labels = [
                    'before 1919',
                    '1919-1929',
                    '1930-1949',
                    '1950-1964',
                    '1965-1975',
                    '1976-1983',
                    '1984-1991',
                    '1992-1998',
                    '1999-2002',
                    '2003-2007',
                    '2008 onwards',
                ];

                $ageDist = DB::table($cfg['table'])
                    ->selectRaw("{$cfg['ageCol']} as band, COUNT(*) as cnt")
                    ->whereNotNull($cfg['ageCol'])
                    ->whereIn($cfg['ageCol'], $labels)
                    ->groupBy('band')
                    ->orderByRaw("FIELD(band, '" . implode("','", $labels) . "')")
                    ->get();
            } else {
                // England & Wales: bucket years / year-ranges / 'before' / 'onwards'
                $col = $cfg['ageCol'];
                $valExpr = "TRIM(CASE WHEN {$col} LIKE 'England and Wales:%' THEN SUBSTRING({$col}, LOCATE(':', {$col}) + 1) ELSE {$col} END)";

                $yearExpr = "CASE\n"
                    . "  WHEN {$col} IS NULL THEN NULL\n"
                    . "  WHEN {$valExpr} IN ('INVALID!', 'NO DATA!', 'Not applicable') THEN NULL\n"
                    . "  WHEN LOWER({$valExpr}) LIKE 'before %' THEN CAST(REGEXP_REPLACE(LOWER({$valExpr}), '[^0-9]', '') AS UNSIGNED) - 1\n"
                    . "  WHEN {$valExpr} REGEXP '^[0-9]{4}\\s*-\\s*[0-9]{4}$' THEN CAST(SUBSTRING_INDEX(REPLACE({$valExpr}, ' ', ''), '-', 1) AS UNSIGNED)\n"
                    . "  WHEN {$valExpr} REGEXP '^[0-9]{4}\\s*onwards$' THEN CAST(REGEXP_REPLACE({$valExpr}, '[^0-9]', '') AS UNSIGNED)\n"
                    . "  WHEN {$valExpr} REGEXP '^[0-9]{4}$' THEN CAST({$valExpr} AS UNSIGNED)\n"
                    . "  ELSE NULL\n"
                    . "END";

                $bucketExpr = "CASE\n"
                    . "  WHEN y IS NULL THEN NULL\n"
                    . "  WHEN y > 2025 THEN NULL\n"
                    . "  WHEN y < 1900 THEN '< 1900'\n"
                    . "  WHEN y BETWEEN 1900 AND 1949 THEN '1900–1949'\n"
                    . "  WHEN y BETWEEN 1950 AND 1999 THEN '1950–1999'\n"
                    . "  WHEN y BETWEEN 2000 AND 2009 THEN '2000–2009'\n"
                    . "  WHEN y BETWEEN 2010 AND 2019 THEN '2010–2019'\n"
                    . "  WHEN y BETWEEN 2020 AND 2025 THEN '2020–2025'\n"
                    . "  ELSE NULL\n"
                    . "END";

                $order = [
                    '< 1900',
                    '1900–1949',
                    '1950–1999',
                    '2000–2009',
                    '2010–2019',
                    '2020–2025',
                ];

                $ageDist = DB::query()
                    ->fromSub(function ($q) use ($cfg, $yearExpr) {
                        $q->from($cfg['table'])
                            ->selectRaw("{$yearExpr} as y")
                            ->whereNotNull($cfg['ageCol']);
                    }, 't')
                    ->selectRaw("{$bucketExpr} as band, COUNT(*) as cnt")
                    ->havingRaw('band IS NOT NULL')
                    ->groupBy('band')
                    ->orderByRaw("FIELD(band, '" . implode("','", $order) . "')")
                    ->get();
            }

            // Controller now uses ageDist:v2 to bust previous cached results
            Cache::put($ck($nation, 'ageDist:v2'), $ageDist, $ttl);
            $this->line("✔ {$nation}: ageDist:v2 cached");
        }

        $elapsed = round((microtime(true) - $started), 2);
        $this->info("Done in {$elapsed}s");
        return self::SUCCESS;
    }
}
