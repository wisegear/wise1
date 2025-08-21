<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    protected $table = 'wards';
    protected $primaryKey = 'wd25cd';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'wd25cd',
        'wd25nm',
        'wd25nmw',
        'lad25cd',
        'lad25nm',
        'lad25nmw',
        'bng_e',
        'bng_n',
        'long',
        'lat',
        'globalid',
        'shape__area',
        'shape__length',
    ];

    // A ward belongs to a LAD
    public function lad()
    {
        return $this->belongsTo(LocalAuthorityDistrict::class, 'lad25cd', 'lad25cd');
    }
}
