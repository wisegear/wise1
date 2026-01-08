<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScottishHousingStock extends Model
{
    protected $table = 'scottish_housing_stock';

    protected $fillable = [
        'year',
        'council',
        'total_stock',

        'house',
        'all_flats',
        'high_rise_flat',
        'tenement',
        'four_in_a_block',
        'other_flat',
        'property_type_unknown',

        'pre_1919',
        'y1919_44',
        'y1945_64',
        'y1965_1982',
        'post_1982',
        'build_period_unknown',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_stock' => 'integer',

        'house' => 'integer',
        'all_flats' => 'integer',
        'high_rise_flat' => 'integer',
        'tenement' => 'integer',
        'four_in_a_block' => 'integer',
        'other_flat' => 'integer',
        'property_type_unknown' => 'integer',

        'pre_1919' => 'integer',
        'y1919_44' => 'integer',
        'y1945_64' => 'integer',
        'y1965_1982' => 'integer',
        'post_1982' => 'integer',
        'build_period_unknown' => 'integer',
    ];

    public $timestamps = true;
}
