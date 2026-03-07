<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UpdateProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/test', function () {
        $user = Auth::user();
        $rolename = $user->rolename;
        $profile = $user->profile;

        $institusi = $user->profile?->institusi;
        $institusi_id = $institusi?->id;

        return compact([
            'user',
            'rolename',
            'profile',
            'institusi',
            'institusi_id',
        ]);
    });

    Route::get('/', [HomeController::class, 'index']);

    Route::view('/button', 'pages.ui-features.buttons');
    Route::view('/forms', 'pages.forms.basic_elements');
    Route::view('/typography', 'pages.ui-features.typography');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('role:admin');
        Route::get('/data', [UserController::class, 'data'])->name('data');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('role:admin');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('role:admin');

        // add these:
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

        // (optional) if you referenced users.toggle anywhere:
        // Route::patch('/{user}/toggle', [UserController::class, 'toggle'])->name('toggle');
    });

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    Route::get('/profile/edit', function () {
        $user = auth()->user();
        $profile = $user->getProfile();
        $institusis = \App\Models\Institusi::all();

        return view('pages.updateprofile', compact('user', 'profile', 'institusis'));
    })->name('profile.edit');

    Route::put('/profile', [UpdateProfileController::class, 'update'])->name('profile.update');

    Route::get('/homeadmin', [HomeController::class, 'adminHome'])->name('home.admin')->middleware('role:admin');

});

Route::view('/resetpassword', 'pages.resetpassword')->name('pages.resetpassword');
Route::view('/newpassword', 'pages.newpassword')->name('password.reset');

require __DIR__.'/auth.php';
