<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    // Home views
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return view('home.admin', compact('user'));
        }
        if ($user->hasRole('kasir')) {
            return view('home.kasir', compact('user'));
        }
        if ($user->hasRole('staff')) {
            return view('home.staff', compact('user'));
        }

        // Default fallback if no matching role
        abort(403, 'Unauthorized access');
    }
}
