<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class EpcController extends Controller
{
    /**
     * EPC Dashboard
     *
     * Shows high‑level stats and a few quick charts/tables backed by simple,
     * index‑friendly queries. All queries read from epc_certificates only.
     */
    public function home()
    {
        // Time windows we'll reuse
        $today         = Carbon::today();
        $last30        = $today->copy()->subDays(30);
        $last365       = $today->copy()->subDays(365);
        $since2008     = Carbon::create(2008, 1, 1); // include all data since 2008
        $ttl           = 60 * 60 * 24 * 45;          // cache for 45 days (monthly feed)

        // 1) Totals & recency
        $stats = Cache::remember('epc.stats', $ttl, function () use ($today, $last30, $last365) {
            return [
                'total'           => (int) DB::table('epc_certificates')->count(),
                'latest_lodgement'=> DB::table('epc_certificates')->max('lodgement_date'),
                'last30_count'    => (int) DB::table('epc_certificates')
                                            ->whereBetween('lodgement_date', [$last30, $today])
                                            ->count(),
                'last365_count'   => (int) DB::table('epc_certificates')
                                            ->whereBetween('lodgement_date', [$last365, $today])
                                            ->count(),
            ];
        });

        // 2) EPCs by year (last ~10 years for speed)
        $byYear = Cache::remember('epc.byYear', $ttl, function () use ($since2008) {
            return DB::table('epc_certificates')
                ->selectRaw('YEAR(lodgement_date) as yr, COUNT(*) as cnt')
                ->whereNotNull('lodgement_date')
                ->where('lodgement_date', '>=', $since2008)
                ->groupBy('yr')
                ->orderBy('yr', 'asc')
                ->get();
        });

        // 3) Distribution of current energy ratings (A–G, plus NULL/other)
        $ratingDist = Cache::remember('epc.ratingDist', $ttl, function () {
            return DB::table('epc_certificates')
                ->selectRaw("
                    CASE
                        WHEN current_energy_rating IN ('A','B','C','D','E','F','G') THEN current_energy_rating
                        WHEN current_energy_rating IS NULL THEN 'Unknown'
                        ELSE 'Other'
                    END as rating,
                    COUNT(*) as cnt
                ")
                ->groupBy('rating')
                ->orderByRaw("FIELD(rating, 'A','B','C','D','E','F','G','Other','Unknown')")
                ->get();
        });

        // 4) Average floor area by property type (top 10 by count)
        $avgFloorArea = Cache::remember('epc.avgFloorArea', $ttl, function () {
            return DB::table('epc_certificates')
                ->selectRaw('property_type, COUNT(*) as cnt, ROUND(AVG(total_floor_area),1) as avg_m2')
                ->whereNotNull('property_type')
                ->groupBy('property_type')
                ->orderByDesc('cnt')
                ->limit(10)
                ->get();
        });

        // 5) Busiest postcodes in the last 12 months (top 10)
        $topPostcodes = Cache::remember('epc.topPostcodes', $ttl, function () use ($last365, $today) {
            return DB::table('epc_certificates')
                ->selectRaw('postcode, COUNT(*) as cnt')
                ->whereNotNull('postcode')
                ->whereBetween('lodgement_date', [$last365, $today])
                ->groupBy('postcode')
                ->orderByDesc('cnt')
                ->limit(10)
                ->get();
        });


        return view('epc.home', [
            'stats'        => $stats,
            'byYear'       => $byYear,
            'ratingDist'   => $ratingDist,
            'avgFloorArea' => $avgFloorArea,
            'topPostcodes' => $topPostcodes,
        ]);
    }

    // Search for EPC certificates by postcode (exact match)
    public function search(Request $request)
    {
        // If no postcode provided, just render the form
        $postcodeInput = (string) $request->query('postcode', '');
        if (trim($postcodeInput) === '') {
            return view('epc.search');
        }

        // Validate: require a plausible UK postcode
        $request->validate([
            'postcode' => ['required','string','max:16','regex:/^[A-Za-z]{1,2}\\d[A-Za-z\\d]?\\s*\\d[A-Za-z]{2}$/'],
        ], [
            'postcode.regex' => 'Please enter a full UK postcode (e.g. W11 3TH).',
        ]);

        // Normalise to canonical form: uppercase and single space before last 3 chars
        $postcode = $this->normalisePostcode($postcodeInput);

        // Sorting (whitelist fields to avoid SQL injection)
        $allowedSorts = [
            'lodgement_date'          => 'lodgement_date',
            'address'                 => 'address',
            'current_energy_rating'   => 'current_energy_rating',
            'potential_energy_rating' => 'potential_energy_rating',
            'property_type'           => 'property_type',
            'total_floor_area'        => 'total_floor_area',
        ];
        $sort = $request->query('sort', 'lodgement_date');
        $dir  = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortCol = $allowedSorts[$sort] ?? 'lodgement_date';

        // Query by postcode only with dynamic sorting
        $query = DB::table('epc_certificates')
            ->select('lmk_key','address','postcode','lodgement_date','current_energy_rating','potential_energy_rating','property_type','total_floor_area','local_authority_label')
            ->where('postcode', $postcode)
            ->orderBy($sortCol, $dir);

        // Secondary tiebreaker to keep results stable
        if ($sortCol !== 'lodgement_date') {
            $query->orderBy('lodgement_date', 'desc');
        }

        $results = $query->paginate(50)->withQueryString();

        return view('epc.search', compact('results'));
    }

    /**
     * Normalise a UK postcode to uppercase with a single space before the final 3 characters.
     */
    protected function normalisePostcode(string $pc): string
    {
        $pc = strtoupper(preg_replace('/\s+/', '', $pc));
        if (strlen($pc) >= 5) {
            return substr($pc, 0, -3) . ' ' . substr($pc, -3);
        }
        return $pc;
    }
}
