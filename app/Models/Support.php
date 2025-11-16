<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    use HasFactory;
    protected $table = 'support';

    public function comments() {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function users() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }


    public static function has_waiting_ticket($id) {

        $tickets = Support::all()->where('user_id', '===', $id);

        foreach ($tickets as $ticket)
        {
            if ($ticket->status === 'Awaiting Reply')
                return true;
        }

        return false;
    }

}
