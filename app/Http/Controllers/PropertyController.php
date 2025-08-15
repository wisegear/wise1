<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function show(Request $request)
    {
        // Full match on address parts
        $postcode = strtoupper(trim($request->input('postcode')));
        $paon     = strtoupper(trim($request->input('paon')));
        $street   = strtoupper(trim($request->input('street')));
        $saon     = $request->filled('saon') ? strtoupper(trim($request->input('saon'))) : null;

        $query = DB::table('land_registry')
            ->select('Date', 'Price', 'PropertyType', 'NewBuild', 'Duration', 'PAON', 'SAON', 'Street', 'Postcode', 'TownCity', 'District', 'County')
            ->where('Postcode', $postcode)
            ->where('PAON', $paon)
            ->where('Street', $street);

        if (!empty($saon)) {
            $query->where('SAON', $saon);
        } else {
            $query->whereNull('SAON');
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
        $address = implode(' ', $addressParts);

        $priceHistoryQuery = DB::table('land_registry')
            ->selectRaw('YEAR(Date) as year, ROUND(AVG(Price)) as avg_price')
            ->where('Postcode', $postcode)
            ->where('PAON', $paon)
            ->where('Street', $street);

        if (!empty($saon)) {
            $priceHistoryQuery->where('SAON', $saon);
        } else {
            $priceHistoryQuery->whereNull('SAON');
        }

        $priceHistory = $priceHistoryQuery->groupBy('year')->orderBy('year', 'asc')->get();

        $postcodePriceHistory = DB::table('land_registry')
            ->selectRaw('YEAR(Date) as year, ROUND(AVG(Price)) as avg_price')
            ->where('Postcode', $postcode)
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get();

        $postcodeSalesHistory = DB::table('land_registry')
            ->select(DB::raw('YEAR(Date) as year'), DB::raw('COUNT(*) as total_sales'))
            ->where('Postcode', $postcode)
            ->groupBy(DB::raw('YEAR(Date)'))
            ->orderBy('year', 'asc')
            ->get();

        $countyPriceHistory = DB::table('land_registry')
            ->where('County', $first->County)
            ->select(DB::raw('YEAR(Date) as year'), DB::raw('ROUND(AVG(Price)) as avg_price'))
            ->groupBy(DB::raw('YEAR(Date)'))
            ->orderBy('year', 'asc')
            ->get();

        $countySalesHistory = DB::table('land_registry')
            ->select(DB::raw('YEAR(Date) as year'), DB::raw('COUNT(*) as total_sales'))
            ->where('County', $first->County)
            ->groupBy(DB::raw('YEAR(Date)'))
            ->orderBy('year', 'asc')
            ->get();

        $propertyTypeMap = [
            'D' => 'Detached',
            'S' => 'Semi',
            'T' => 'Terraced',
            'F' => 'Flat',
            'O' => 'Other',
        ];

        $countyPropertyTypes = DB::table('land_registry')
            ->select('PropertyType', DB::raw('COUNT(DISTINCT CONCAT(PAON, Street, Postcode)) as property_count'))
            ->where('County', $first->County)
            ->groupBy('PropertyType')
            ->orderBy('property_count', 'desc')
            ->get()
            ->map(function ($row) use ($propertyTypeMap) {
                return [
                    'label' => $propertyTypeMap[$row->PropertyType] ?? $row->PropertyType,
                    'value' => $row->property_count
                ];
            });

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
