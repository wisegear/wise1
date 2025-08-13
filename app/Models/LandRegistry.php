<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandRegistry extends Model
{
    // Table name since it doesn't follow Laravel's plural naming convention
    protected $table = 'land_registry';

    // No created_at / updated_at columns
    public $timestamps = false;

    // Your table doesn't have a primary key set, but you can use TransactionID
    protected $primaryKey = 'TransactionID';
    public $incrementing = false;
    protected $keyType = 'string';

    // Casts for data types
    protected $casts = [
        'Price' => 'integer',
        'Date'  => 'datetime',
    ];

    // Fillable if you plan on mass-assigning
    protected $fillable = [
        'TransactionID',
        'Price',
        'Date',
        'Postcode',
        'PropertyType',
        'NewBuild',
        'Duration',
        'PAON',
        'SAON',
        'Street',
        'Locality',
        'TownCity',
        'District',
        'County',
        'PPDCategoryType',
        'RecordStatus',
    ];
}
