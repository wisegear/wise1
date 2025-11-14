<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnemploymentMonthly extends Model
{
    protected $table = 'unemployment_monthly';

    protected $fillable = [
        'date',
        'rate',
    ];

    protected $casts = [
        'date' => 'date',
        'rate' => 'float',
    ];
}
