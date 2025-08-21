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
            ->select('Date', 'Price', 'PropertyType', 'NewBuild', 'Duration', 'PAON', 'SAON', 'Street', 'Postcode', 'Locality', 'TownCity', 'District', 'County', 'PPDCategoryType')
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

        // Build address (PAON, SAON, Street, Locality, Postcode, TownCity, District, County)
        $first = $records->first();
        $district = $first->District;
        $addressParts = [];
        $addressParts[] = trim($first->PAON);
        if (!empty(trim($first->SAON))) {
            $addressParts[] = trim($first->SAON);
        }
        $addressParts[] = trim($first->Street);
        if (!empty(trim($first->Locality))) {
            $addressParts[] = trim($first->Locality);
        }
        $addressParts[] = trim($first->Postcode);
        if (!empty(trim($first->TownCity))) {
            $addressParts[] = trim($first->TownCity);
        }
        if (!empty(trim($first->District))) {
            $addressParts[] = trim($first->District);
        }
        if (!empty(trim($first->County))) {
            $addressParts[] = trim($first->County);
        }
        $address = implode(', ', $addressParts);

        // --- Locality visibility gate to avoid unnecessary locality queries ---
        $locality     = trim((string) $first->Locality);
        $town         = trim((string) $first->TownCity);
        $districtName = trim((string) $first->District);
        $countyName   = trim((string) $first->County);
        $norm = function ($v) {
            return strtolower(trim((string) $v));
        };
        $showLocalityCharts = ($locality !== '')
            && ($norm($locality) !== $norm($town))
            && ($norm($locality) !== $norm($districtName))
            && ($norm($locality) !== $norm($countyName));

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

        $districtPriceHistory = Cache::remember(
            'district:priceHistory:v1:' . $first->District,
            60 * 60 * 24 * 7,
            function () use ($first) {
                return DB::table('land_registry')
                    ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                    ->where('District', $first->District)
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

        $districtSalesHistory = Cache::remember(
            'district:salesHistory:v1:' . $first->District,
            60 * 60 * 24 * 7,
            function () use ($first) {
                return DB::table('land_registry')
                    ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                    ->where('District', $first->District)
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
                    ->select('PropertyType', DB::raw("COUNT(DISTINCT CONCAT_WS('-', SAON, PAON, Street, Postcode)) as property_count"))
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

        $districtPropertyTypes = Cache::remember(
            'district:types:v1:' . $first->District,
            60 * 60 * 24 * 7,
            function () use ($first, $propertyTypeMap) {
                return DB::table('land_registry')
                    ->select('PropertyType', DB::raw("COUNT(DISTINCT CONCAT_WS('-', SAON, PAON, Street, Postcode)) as property_count"))
                    ->where('District', $first->District)
                    ->groupBy('PropertyType')
                    ->orderByDesc('property_count')
                    ->get()
                    ->map(function ($row) use ($propertyTypeMap) {
                        return [
                            'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                            'value' => $row->property_count,
                        ];
                    });
            }
        );

        // --- Town/City datasets (mirrors district/locality structures) ---
        if (!empty(trim((string) $first->TownCity))) {
            $townPriceHistory = Cache::remember(
                'town:priceHistory:v1:' . $first->TownCity,
                60 * 60 * 24 * 7,
                function () use ($first) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                        ->where('TownCity', $first->TownCity)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $townSalesHistory = Cache::remember(
                'town:salesHistory:v1:' . $first->TownCity,
                60 * 60 * 24 * 7,
                function () use ($first) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                        ->where('TownCity', $first->TownCity)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $townPropertyTypes = Cache::remember(
                'town:types:v1:' . $first->TownCity,
                60 * 60 * 24 * 7,
                function () use ($first, $propertyTypeMap) {
                    return DB::table('land_registry')
                        ->select('PropertyType', DB::raw("COUNT(DISTINCT CONCAT_WS('-', SAON, PAON, Street, Postcode)) as property_count"))
                        ->where('TownCity', $first->TownCity)
                        ->groupBy('PropertyType')
                        ->orderByDesc('property_count')
                        ->get()
                        ->map(function ($row) use ($propertyTypeMap) {
                            return [
                                'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                                'value' => $row->property_count,
                            ];
                        });
                }
            );
        } else {
            $townPriceHistory = collect();
            $townSalesHistory = collect();
            $townPropertyTypes = collect();
        }

        // Locality datasets (only compute when locality is meaningful & distinct)
        if ($showLocalityCharts) {
            $localityPriceHistory = Cache::remember(
                'locality:priceHistory:v1:' . $first->Locality,
                60 * 60 * 24 * 7,
                function () use ($first) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('ROUND(AVG(Price)) as avg_price'))
                        ->where('Locality', $first->Locality)
                        ->groupBy('YearDate')
                        ->orderBy('YearDate', 'asc')
                        ->get();
                }
            );

            $localitySalesHistory = Cache::remember(
                'locality:salesHistory:v1:' . $first->Locality,
                60 * 60 * 24 * 7,
                function () use ($first) {
                    return DB::table('land_registry')
                        ->select('YearDate as year', DB::raw('COUNT(*) as total_sales'))
                        ->where('Locality', $first->Locality)
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

            $localityPropertyTypes = Cache::remember(
                'locality:types:v1:' . $first->Locality,
                60 * 60 * 24 * 7,
                function () use ($first, $propertyTypeMap) {
                    return DB::table('land_registry')
                        ->select('PropertyType', DB::raw("COUNT(DISTINCT CONCAT_WS('-', SAON, PAON, Street, Postcode)) as property_count"))
                        ->where('Locality', $first->Locality)
                        ->groupBy('PropertyType')
                        ->orderByDesc('property_count')
                        ->get()
                        ->map(function ($row) use ($propertyTypeMap) {
                            return [
                                'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                                'value' => $row->property_count,
                            ];
                        });
                }
            );
        } else {
            // Keep view happy but avoid work
            $localityPriceHistory = collect();
            $localitySalesHistory = collect();
            $localityPropertyTypes = collect();
        }

        return view('property.show', [
            'results' => $records,
            'address' => $address,
            'priceHistory' => $priceHistory,
            'postcodePriceHistory' => $postcodePriceHistory,
            'postcodeSalesHistory' => $postcodeSalesHistory,
            'countyPriceHistory' => $countyPriceHistory,
            'countySalesHistory' => $countySalesHistory,
            'countyPropertyTypes' => $countyPropertyTypes,
            'districtPriceHistory' => $districtPriceHistory,
            'districtSalesHistory' => $districtSalesHistory,
            'districtPropertyTypes' => $districtPropertyTypes,
            'townPriceHistory' => $townPriceHistory,
            'townSalesHistory' => $townSalesHistory,
            'townPropertyTypes' => $townPropertyTypes,
            'localityPriceHistory' => $localityPriceHistory,
            'localitySalesHistory' => $localitySalesHistory,
            'localityPropertyTypes' => $localityPropertyTypes,
        ]);


    }


}
