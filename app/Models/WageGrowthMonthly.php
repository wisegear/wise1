<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WageGrowthMonthly extends Model
{
    protected $table = 'wage_growth_monthly';

    protected $fillable = [
        'date',
        'single_month_yoy',
        'three_month_avg_yoy',
    ];

    protected $casts = [
        'date' => 'date',
        'single_month_yoy' => 'float',
        'three_month_avg_yoy' => 'float',
    ];
}
