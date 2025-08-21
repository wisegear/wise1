<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocalAuthorityDistrict extends Model
{
    protected $table = 'local_authority_district';
    protected $primaryKey = 'lad25cd';
    public $incrementing = false;       // GSS codes are strings, not integers
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
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

    // A LAD has many wards
    public function wards()
    {
        return $this->hasMany(Ward::class, 'lad25cd', 'lad25cd');
    }
}
