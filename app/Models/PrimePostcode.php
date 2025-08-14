<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PrimePostcode extends Model
{
    protected $fillable = ['postcode', 'category'];

    public function landRegistryEntries()
    {
        return $this->hasMany(LandRegistry::class, 'Postcode', 'postcode');
    }

    protected function postcode(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => strtoupper(str_replace(' ', '', trim($value)))
        );
    }
}
