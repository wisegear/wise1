<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoles extends Model
{
    protected $table = 'user_roles';
    use HasFactory;

    public function users() 
    {
        return $this->belongsToMany(UserRoles::class, 'user_roles_pivot', 'role_id', 'user_id');
    }
}
