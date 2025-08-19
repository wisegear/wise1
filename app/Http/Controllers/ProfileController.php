<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserRoles;
use App\Models\BlogPosts;
use App\Models\BlogCategories;
use App\Models\BlogTags;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;
use App\Services\ImageService;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $name_slug)
    {
  

        $user = User::where('name_slug', $name_slug)->first();

        return view('profile.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $name_slug)
    {

        if (Auth::user()->name_slug === $name_slug Or Gate::authorize('Admin'))
        {
            $user = User::where('name_slug', $name_slug)->first();
            $roles = UserRoles::all();

            return view('profile.edit', compact('user', 'roles'));
        
        } else {

            return back();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $name_slug)
    {

        Gate::authorize('Member');

        $user = User::where('name_slug', $name_slug)->first();

        // Check if there is a new avatar

        if ($request->hasFile('image'))
        {

            //Find the old file and delete it unless it is the defailt image

            if ($user->avatar && $user->avatar != 'default.png') {
                $avatarPath = public_path('assets/images/avatars/' . $user->avatar);
                
                if (File::exists($avatarPath)) {
                    File::delete($avatarPath);
                }
            }

            //Get the new image and assign it to a variable called $pic

            $pic = $request->file('image');

            //Assign a unique name to the new avatar

            $pic_name = time() . '-' . $pic->getClientOriginalName();

            //Move the file to the avatars directory and rename it.

            $pic->move(public_path() . '/assets/images/avatars/', $pic_name);

            //Crop or upsize the image to fit the 100x100 requirement

            $resize = Image::read(sprintf(public_path() . '/assets/images/avatars/' . '%s', $pic_name))
                ->resize(100,100, function($constraint) {
                    // $constraint->aspectRatio();
                    // $constraint->upsize();
                })
            ->save(public_path() . '/assets/images/avatars/' . $pic_name);

            //Update the avatar name in the user model

            $user->avatar = $pic_name;

        }        

        $user->email = $request->email;
        $user->website = $request->website;
        $user->location = $request->location;
        $user->bio = $request->bio;
        $user->linkedin = $request->linkedin;
        $user->facebook = $request->facebook;
        $user->x = $request->x;

            //Check if the email is to be displayed.
            if (isset($request->email_visible)) {

                $user->email_visible = 1;

            } else {

                $user->email_visible = 0;

            }

            // Only an Admin can update these.

            if (Gate::allows('Admin')) {
        
                //Check if the user is trusted.
                if (isset($request->trusted)) {

                    $user->trusted = 1;

                } else {

                    $user->trusted = 0;

                }

                //Check if the user is locked.
                if (isset($request->lock)) {

                    $user->lock = 1;

                } else {

                    $user->lock = 0;

                }


                $user->notes = $request->notes;

                //Sync Roles only if user is Admin

                    $user->user_roles()->sync($request->roles);

            }

                $user->save();

                return back()->with('status', 'User Profile has been updated.');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
