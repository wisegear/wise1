<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PropertyController extends Controller
{
    public function show(Request $request)
    {
        // Full match on address parts
        $postcode = strtoupper(trim($request->input('postcode')));
        $paon     = strtoupper(trim($request->input('paon')));
        $street   = strtoupper(trim($request->input('street')));
        $saon     = $request->filled('saon') ? strtoupper(trim($request->input('saon'))) : null;

        $query = DB::table(DB::raw('land_registry USE INDEX (idx_full_property, idx_postcode_date)'))
            ->select('Date', 'Price', 'PropertyType', 'NewBuild', 'Duration', 'PAON', 'SAON', 'Street', 'Postcode', 'TownCity', 'District', 'County', 'PPDCategoryType')
            ->where('Postcode', $postcode)
            ->where('PAON', $paon)
            ->where('Street', $street);

        if (!empty($saon)) {
            $query->where('SAON', $saon);
        } else {
            // treat empty string and NULL the same to maximize matches and use index
            $query->where(function ($q) {
                $q->whereNull('SAON')->orWhere('SAON', '');
            });
        }

        $records = $query->orderBy('Date', 'desc')->limit(100)->get();

        if ($records->isEmpty()) {
            abort(404, 'Property not found');
        }

        // Build address
        $first = $records->first();
        $addressParts = [];
        $addressParts[] = trim($first->PAON);
        if (!empty(trim($first->SAON))) {
            $addressParts[] = trim($first->SAON);
        }
        $addressParts[] = trim($first->Street);
        $addressParts[] = trim($first->Postcode);
        $address = implode(', ', $addressParts);

        $priceHistoryQuery = DB::table(DB::raw('land_registry USE INDEX (idx_full_property)'))
            ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
            ->where('Postcode', $postcode)
            ->where('PAON', $paon)
            ->where('Street', $street);

        if (!empty($saon)) {
            $priceHistoryQuery->where('SAON', $saon);
        } else {
            $priceHistoryQuery->where(function ($q) {
                $q->whereNull('SAON')->orWhere('SAON', '');
            });
        }

        $priceHistory = $priceHistoryQuery->groupBy('YearDate')->orderBy('YearDate', 'asc')->get();

        $postcodePriceHistory = DB::table(DB::raw('land_registry USE INDEX (idx_postcode_yeardate)'))
            ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
            ->where('Postcode', $postcode)
            ->groupBy('YearDate')
            ->orderBy('YearDate', 'asc')
            ->get();

        $postcodeSalesHistory = DB::table(DB::raw('land_registry USE INDEX (idx_postcode_yeardate)'))
            ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
            ->where('Postcode', $postcode)
            ->groupBy('YearDate')
            ->orderBy('YearDate', 'asc')
            ->get();

        $countyPriceHistory = Cache::remember(
            'county:priceHistory:v1:' . $first->County,
            60 * 60 * 24 * 7,
            function () use ($first) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county_yeardate)'))
                    ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('County', $first->County)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        $countySalesHistory = Cache::remember(
            'county:salesHistory:v1:' . $first->County,
            60 * 60 * 24 * 7,
            function () use ($first) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county_yeardate)'))
                    ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('County', $first->County)
                    ->groupBy('YearDate')
                    ->orderBy('YearDate', 'asc')
                    ->get();
            }
        );

        $propertyTypeMap = [
            'D' => 'Detached',
            'S' => 'Semi',
            'T' => 'Terraced',
            'F' => 'Flat',
            'O' => 'Other',
        ];

        $countyPropertyTypes = Cache::remember(
            'county:types:v1:' . $first->County,
            60 * 60 * 24 * 7,
            function () use ($first, $propertyTypeMap) {
                return DB::table(DB::raw('land_registry FORCE INDEX (idx_county)'))
                    ->select('PropertyType', DB::raw('COUNT(DISTINCT CONCAT(PAON, Street, Postcode)) as property_count'))
                    ->where('County', $first->County)
                    ->groupBy('PropertyType')
                    ->orderByDesc('property_count')
                    ->get()
                    ->map(function ($row) use ($propertyTypeMap) {
                        return [
                            'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                            'value' => $row->property_count
                        ];
                    });
            }
        );

        return view('property.show', [
            'results' => $records,
            'address' => $address,
            'priceHistory' => $priceHistory,
            'postcodePriceHistory' => $postcodePriceHistory,
            'postcodeSalesHistory' => $postcodeSalesHistory,
            'countyPriceHistory' => $countyPriceHistory,
            'countySalesHistory' => $countySalesHistory,
            'countyPropertyTypes' => $countyPropertyTypes
        ]);


    }


}
