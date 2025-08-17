<?php
// app/Models/InterestRate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterestRate extends Model
{
    protected $fillable = ['effective_date','rate','source','notes'];
    protected $casts = ['effective_date' => 'date', 'rate' => 'decimal:2'];
}
