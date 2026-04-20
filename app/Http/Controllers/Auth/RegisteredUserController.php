<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create()
    {
        return view('pages.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'email' => 'required|email|unique:'.User::class,
            'whatsapp' => 'required',
            'password' => ['required', Rules\Password::defaults()],
            'gender' => 'required|boolean',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
        ]);

        DB::transaction(function () use ($request, &$user) {
            $user = new User;
            $user->username = $request->email;
            $user->email = $request->email;
            $user->password = $request->password;
            $user->save();

            $user->assignRole('staff');

            $profile = new Staff;
            $profile->name = $request->nama;
            $profile->whatsapp = $request->whatsapp;
            $profile->gender = $request->gender;
            $profile->user_id = $user->id;
            $profile->save();

            if ($request->hasFile('document')) {
                $profile->document = $request->file('document')->store('documents', 'local');
                $profile->save();
            }
        });

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}
