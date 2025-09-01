<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HpiMonthly extends Model
{
    protected $table = 'hpi_monthly';

    // No auto IDs, no timestamps, and we won't rely on a single PK
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;

    // We’re only reading via Eloquent; imports use DB::table()->upsert()
    protected $guarded = [];

    // Useful casts (you can add more if you like)
    protected $casts = [
        'Date'           => 'date:Y-m-d',
        'AveragePrice'   => 'float',
        'Index'          => 'float',
        'IndexSA'        => 'float',
        'AveragePriceSA' => 'float',
        'SalesVolume'    => 'integer',
    ];
    /** Mapping for UK + Nations (ordered for charts) */
    public static function ukAndNationAreas(): array
    {
        return [
            'United Kingdom'   => 'K02000001',
            'England'          => 'E92000001',
            'Scotland'         => 'S92000003',
            'Wales'            => 'W92000004',
            'Northern Ireland' => 'N92000002',
        ];
    }

    /** Time series of average prices by property type for a given area */
    public static function typePriceSeries(string $areaCode): \Illuminate\Support\Collection
    {
        return static::query()
            ->select(['Date','DetachedPrice','SemiDetachedPrice','TerracedPrice','FlatPrice'])
            ->where('AreaCode', $areaCode)
            ->orderBy('Date')
            ->get();
    }

    /** Chart-ready series (avg prices) for UK + Nations: dates (YYYY-MM) + per-type arrays */
    public static function typePriceSeriesByArea(): array
    {
        $areas = self::ukAndNationAreas();
        $out = [];
        foreach ($areas as $name => $code) {
            $rows = self::typePriceSeries($code);
            $out[] = [
                'name'  => $name,
                'code'  => $code,
                'dates' => $rows->pluck('Date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m'))->all(),
                'types' => [
                    'Detached'     => $rows->pluck('DetachedPrice')->map(fn($v) => is_null($v) ? null : (float)$v)->all(),
                    'SemiDetached' => $rows->pluck('SemiDetachedPrice')->map(fn($v) => is_null($v) ? null : (float)$v)->all(),
                    'Terraced'     => $rows->pluck('TerracedPrice')->map(fn($v) => is_null($v) ? null : (float)$v)->all(),
                    'Flat'         => $rows->pluck('FlatPrice')->map(fn($v) => is_null($v) ? null : (float)$v)->all(),
                ],
            ];
        }
        return $out;
    }
    /** Latest date (global across all areas) */
    public static function latestDate(): ?string
    {
        return static::query()->max('Date');
    }

    /** Latest date for a specific area code */
    public static function latestDateFor(string $areaCode): ?string
    {
        return static::query()->where('AreaCode', $areaCode)->max('Date');
    }

    /** Nation codes used across the dashboard (alias of ukAndNationAreas) */
    public static function nationCodes(): array
    {
        return self::ukAndNationAreas();
    }

    /** Nations snapshot at their own latest dates (avoids misaligned months) */
    public static function latestNations(): \Illuminate\Support\Collection
    {
        return collect(self::nationCodes())
            ->map(function ($code, $name) {
                $d = self::latestDateFor($code);
                if (!$d) return null;
                return static::query()
                    ->select([
                        'RegionName','AreaCode','Date',
                        'AveragePrice',
                        DB::raw('`1m%Change` as one_m_change'),
                        DB::raw('`12m%Change` as twelve_m_change'),
                        'SalesVolume',
                    ])
                    ->where('AreaCode', $code)
                    ->where('Date', $d)
                    ->first();
            })
            ->filter()
            ->values();
    }

    /** UK time series (for charts) */
    public static function ukSeries(): \Illuminate\Support\Collection
    {
        return static::query()
            ->select([
                'Date','AveragePrice','Index',
                DB::raw('`1m%Change` as one_m_change'),
                DB::raw('`12m%Change` as twelve_m_change'),
                'SalesVolume',
            ])
            ->where('AreaCode', 'K02000001')
            ->orderBy('Date')
            ->get();
    }
}
