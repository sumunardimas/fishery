<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
// import profile models so PHP resolves them correctly
use App\Models\Institusi;
use App\Models\Kasir;
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
        $institusis = Institusi::all();

        return view('pages.register', compact([
            'institusis',
        ]));
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
            'role' => 'required|in:admin,kasir,staff',
            'institusi_id' => 'required|exists:institusis,id',
            'document' => 'nullable|file',
        ]);

        DB::transaction(function () use ($request, &$user) {
            $user = new User;
            $user->username = $request->email;
            $user->email = $request->email;
            $user->password = $request->password;
            $user->save();

            $user->assignRole($request->role);

            $profile = match ($request->role) {
                'admin' => Admin::class,
                'staff' => Staff::class,
                'kasir' => Kasir::class,
                default => throw new \Exception('Role tidak ditemukan'),
            };

            $profile = new $profile;
            $profile->name = $request->nama;
            $profile->whatsapp = $request->whatsapp;
            $profile->gender = $request->gender;
            $profile->user_id = $user->id;
            if ($request->role == 'admin') {
                $profile->committee = $request->committee;
            }
            $profile->institusi_id = $request->institusi_id;
            $profile->save();

            if ($request->hasFile('document')) {
                $request->file('document')->storeAs(
                    'users/profile/document', $user->id
                );

                $profile->document = $request->document->getClientOriginalName();
                $profile->save();
            }
        });

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}
