<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Support;

class AdminSupportController extends Controller
{
    public function index()
    {

        if (isset($_GET['closed'])) {

            $tickets = Support::with('comments', 'users')->where('status', 'Closed')->paginate(15);

        } else {

        $tickets = Support::with('comments', 'users')->where('status', '!=', 'Closed')->paginate(15);

        }


        return view('admin.support.index', compact('tickets'));

    }
}
