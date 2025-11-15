<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MlarArrear extends Model
{
    protected $table = 'mlar_arrears';

    protected $casts = [
        'year' => 'integer',
        'value' => 'decimal:3',
    ];

    protected $fillable = [
        'band',
        'description',
        'year',
        'quarter',
        'value',
    ];
}
