<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserRolesPivot;
use App\Models\BlogPosts;
use App\Models\Support;
use App\Models\DataUpdate;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {

        // User info
        $users = User::all();

        // Ridiculous workaround, count users, roles then add 1 as admin has two roles!!
        $users_count = User::all()->count();
        $roles_count = UserRolesPivot::all()->Count();
        $users_pending = UserRolesPivot::all()->where('role_id', '===', 2)->count();
        $users_banned = UserRolesPivot::all()->where('role_id', '===', 1)->count();
        $users_active = UserRolesPivot::all()->where('role_id', '===', 3)->count();
        $users_admin = UserRolesPivot::all()->where('role_id', '===', 4)->count();

        // Support tickets info
        $tickets = Support::all();
        $tickets_total = $tickets->count();

        // Match actual stored statuses (Open, Closed, Pending, Awaiting Reply)
        $tickets_open = $tickets->filter(function ($ticket) {
            return strcasecmp($ticket->status, 'Open') === 0;
        })->count();

        $tickets_pending = $tickets->filter(function ($ticket) {
            return strcasecmp($ticket->status, 'Pending') === 0;
        })->count();

        $tickets_awaiting = $tickets->filter(function ($ticket) {
            return strcasecmp($ticket->status, 'Awaiting Reply') === 0;
        })->count();

        $tickets_closed = $tickets->filter(function ($ticket) {
            return strcasecmp($ticket->status, 'Closed') === 0;
        })->count();
        //Blog info
        $blogposts = BlogPosts::all();
        $blogunpublished = BlogPosts::where('published', false)->get();

        // Upcoming data updates (next 3 by next_update_due_at from today onwards)
        $upcoming_updates = DataUpdate::whereNotNull('next_update_due_at')
            ->whereDate('next_update_due_at', '>=', now()->toDateString())
            ->orderBy('next_update_due_at', 'asc')
            ->take(3)
            ->get();

        $data = array(

            'users' => $users,
            'users_pending' => $users_pending,
            'users_active' => $users_active,
            'users_banned' => $users_banned,
            'users_admin' => $users_admin,
            'blogposts' => $blogposts,
            'blogunpublished' => $blogunpublished,
            'upcoming_updates' => $upcoming_updates,
            'tickets_total' => $tickets_total,
            'tickets_open' => $tickets_open,
            'tickets_pending' => $tickets_pending,
            'tickets_awaiting' => $tickets_awaiting,
            'tickets_closed' => $tickets_closed,
        );

        return view ('admin.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

