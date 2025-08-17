<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InterestRate;

class InterestRateController extends Controller
{
    public function home() {

        $rates = InterestRate::all();

        return view('interest.home', compact('rates'));
    }
}
