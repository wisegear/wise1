<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EPC extends Model
{
    protected $table = 'epc_certificates';
    protected $primaryKey = 'lmk_key';
    public $incrementing = false; // lmk_key is a string, not auto-increment
    protected $keyType = 'string';

    protected $fillable = [
        'lmk_key',
        'building_reference_number',
        'uprn',
        'uprn_source',
        'postcode',
        'address',
        'posttown',
        'inspection_date',
        'lodgement_date',
        'lodgement_datetime',
        'local_authority',
        'local_authority_label',
        'property_type',
        'built_form',
        'construction_age_band',
        'floor_level',
        'flat_top_storey',
        'flat_storey_count',
        'current_energy_rating',
        'potential_energy_rating',
        'current_energy_efficiency',
        'potential_energy_efficiency',
        'total_floor_area',
        'transaction_type',
        'tenure',
        'number_habitable_rooms',
        'extension_count',
    ];

    protected $casts = [
        'inspection_date'          => 'date',
        'lodgement_date'           => 'date',
        'lodgement_datetime'       => 'datetime',
        'current_energy_efficiency'=> 'integer',
        'potential_energy_efficiency'=> 'integer',
        'total_floor_area'         => 'float',
        'number_habitable_rooms'   => 'integer',
        'extension_count'          => 'integer',
    ];
}
