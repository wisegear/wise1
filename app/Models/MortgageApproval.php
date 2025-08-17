<?php
// app/Models/MortgageApproval.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MortgageApproval extends Model
{
    // Table name matches the default pluralization, but keep this for clarity.
    protected $table = 'mortgage_approvals';

    protected $fillable = [
        'series_code',   // e.g. LPMVTVX
        'period',        // date (YYYY-MM-01)
        'value',         // integer count
        'unit',          // e.g. "count"
        'source',        // e.g. "BoE"
    ];

    protected $casts = [
        'period' => 'date',
        'value'  => 'integer',
    ];

    /** Friendly labels for charts/UI */
    public const SERIES_LABELS = [
        'LPMVTVX' => 'House purchase',
        'LPMB4B3' => 'Remortgaging',
        'LPMB4B4' => 'Other secured lending',
        'LPMB3C8' => 'Total approvals',
    ];

    /** Get a human label for a code */
    public static function labelFor(string $code): string
    {
        return self::SERIES_LABELS[$code] ?? $code;
    }

    /* ---------------------- Scopes ---------------------- */

    /** Filter by one or more series codes */
    public function scopeSeries(Builder $q, string|array $codes): Builder
    {
        return is_array($codes)
            ? $q->whereIn('series_code', $codes)
            : $q->where('series_code', $codes);
    }

    /** Limit by period range (accepts strings or Carbon) */
    public function scopeBetween(Builder $q, string|Carbon $from, string|Carbon $to): Builder
    {
        return $q->whereBetween('period', [
            $from instanceof Carbon ? $from->toDateString() : $from,
            $to   instanceof Carbon ? $to->toDateString()   : $to,
        ]);
    }

    /** Filter by year (integer) */
    public function scopeYear(Builder $q, int $year): Builder
    {
        return $q->whereYear('period', $year);
    }

    /** Order by time (ascending by default) */
    public function scopeChrono(Builder $q, string $dir = 'asc'): Builder
    {
        return $q->orderBy('period', $dir);
    }
}