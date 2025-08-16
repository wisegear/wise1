<?php
// app/Models/RepoLaQuarterly.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class RepoLaQuarterly extends Model
{
    protected $table = 'repo_la_quarterlies';

    // Keep it open for mass import if you ever use Eloquent inserts
    protected $guarded = [];

    protected $casts = [
        'year'  => 'integer',
        'value' => 'integer',
    ];

    /**
     * Display-only: "Blackburn with Darwen UA" -> "Blackburn with Darwen"
     */
    protected function countyDisplay(): Attribute
    {
        return Attribute::get(
            fn () => trim(str_replace(' UA', '', (string) $this->county_ua))
        );
    }

    /* --------------------------- Scopes (filters) --------------------------- */

    public function scopeYear(Builder $q, int $year): Builder
    {
        return $q->where('year', $year);
    }

    public function scopeBetweenYears(Builder $q, int $from, int $to): Builder
    {
        return $q->whereBetween('year', [$from, $to]);
    }

    public function scopeQuarter(Builder $q, string $quarter): Builder
    {
        return $q->where('quarter', $quarter); // 'Q1'..'Q4'
    }

    public function scopeCounty(Builder $q, string $countyUa): Builder
    {
        return $q->where('county_ua', $countyUa);
    }

    public function scopeRegion(Builder $q, string $region): Builder
    {
        return $q->where('region', $region);
    }

    public function scopeType(Builder $q, string $possessionType): Builder
    {
        return $q->where('possession_type', $possessionType); // Mortgage/Private/Social/Accelerated_Landlord
    }

    public function scopeAction(Builder $q, string $possessionAction): Builder
    {
        return $q->where('possession_action', $possessionAction); // Claims/Orders/Warrants/Repossessions
    }

    /* ----------------------- Helpers / common queries ----------------------- */

    /**
     * Returns [year, quarter] for the most recent period present in the table.
     */
    public static function latestPeriod(): array
    {
        $row = static::query()
            ->select('year', 'quarter')
            ->orderByDesc('year')
            ->orderByRaw("FIELD(quarter,'Q4','Q3','Q2','Q1')") // Q4 > Q3 > Q2 > Q1
            ->first();

        return $row ? [(int) $row->year, (string) $row->quarter] : [null, null];
    }

    /**
     * Yearly rollup by county & reason (type vs stage).
     * $by = 'type' (possession_type) or 'action' (possession_action)
     */
    public static function yearlyByCounty(string $by = 'type'): Builder
    {
        $col = $by === 'action' ? 'possession_action' : 'possession_type';

        return static::query()
            ->selectRaw("year, county_ua, {$col} AS reason, SUM(value) AS cases")
            ->groupBy('year', 'county_ua', $col)
            ->orderBy('year');
    }

    /**
     * Quarterly rollup for a given period.
     */
    public static function quarterlyByCounty(int $year, string $quarter, string $by = 'type'): Builder
    {
        $col = $by === 'action' ? 'possession_action' : 'possession_type';

        return static::query()
            ->selectRaw("county_ua, {$col} AS reason, SUM(value) AS cases")
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->groupBy('county_ua', $col)
            ->orderBy('county_ua');
    }
}
