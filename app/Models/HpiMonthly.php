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

    /* ---------------------- Scopes / helpers ---------------------- */

    /** Filter by area code */
    public function scopeArea(Builder $q, string $code): Builder
    {
        return $q->where('AreaCode', $code);
    }

    /** UK rollup (K02000001) */
    public function scopeUk(Builder $q): Builder
    {
        return $q->where('AreaCode', 'K02000001');
    }

    /** Nations rollups */
    public static function nationCodes(): array
    {
        return [
            'United Kingdom'   => 'K02000001',
            'England'          => 'E92000001',
            'Scotland'         => 'S92000003',
            'Wales'            => 'W92000004',
            'Northern Ireland' => 'N92000002',
        ];
    }

    /** Latest date (global) */
    public static function latestDate(): ?string
    {
        return static::query()->max('Date');
    }

    /** Latest date for a specific area */
    public static function latestDateFor(string $areaCode): ?string
    {
        return static::query()->where('AreaCode', $areaCode)->max('Date');
    }

    /** Latest snapshot for a specific area (returns a single row or null) */
    public static function latestSnapshotFor(string $areaCode): ?self
    {
        $d = self::latestDateFor($areaCode);
        if (!$d) return null;

        return static::query()
            ->where('AreaCode', $areaCode)
            ->where('Date', $d)
            ->first();
    }

    /** Nations snapshot at their own latest dates (avoids misaligned months) */
    public static function latestNations(): \Illuminate\Support\Collection
    {
        $rows = collect(self::nationCodes())
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
            ->filter();

        return $rows->values();
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
