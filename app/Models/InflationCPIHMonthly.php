<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InflationCPIHMonthly extends Model
{
    protected $table = 'inflation_cpih_monthly';

    protected $fillable = [
        'date',
        'rate',
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'float',
    ];

    // Convenience scopes/helpers if you want them later:
    // public function scopeLatestFirst($query)
    // {
    //     return $query->orderByDesc('date'); Test
    // }
}
