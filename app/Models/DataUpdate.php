<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataUpdate extends Model
{
    protected $fillable = [
        'name',
        'last_updated_at',
        'next_update_due_at',
        'notes',
        'data_link',
    ];

    protected $casts = [
        'last_updated_at'    => 'date',
        'next_update_due_at' => 'date',
    ];
}
