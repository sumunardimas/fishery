<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\KapalController;
use App\Http\Controllers\MasterCustomerController;
use App\Http\Controllers\MasterIkanController;
use App\Http\Controllers\MasterItemPembelianController;
use App\Http\Controllers\MasterOperasionalController;
use App\Http\Controllers\MasterPerbekalanController;
use App\Http\Controllers\OperasionalController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PelayaranController;
use App\Http\Controllers\PelayaranSisaController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StokController;
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

    Route::prefix('kapal')->name('kapal.')->group(function () {
        Route::get('/', [KapalController::class, 'index'])->name('index');
        Route::get('/create', [KapalController::class, 'create'])->name('create');
        Route::post('/', [KapalController::class, 'store'])->name('store');
        Route::get('/{kapal}/edit', [KapalController::class, 'edit'])->name('edit');
        Route::put('/{kapal}', [KapalController::class, 'update'])->name('update');
        Route::delete('/{kapal}', [KapalController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pelayaran')->name('pelayaran.')->group(function () {
        Route::get('/', [PelayaranController::class, 'index'])->name('index');
        Route::get('/create', [PelayaranController::class, 'create'])->name('create');
        Route::post('/', [PelayaranController::class, 'store'])->name('store');
        Route::get('/sisa', [PelayaranSisaController::class, 'index'])->name('sisa.index');
        Route::post('/sisa', [PelayaranSisaController::class, 'store'])->name('sisa.store');
        Route::get('/{pelayaran}/edit', [PelayaranController::class, 'edit'])->name('edit');
        Route::put('/{pelayaran}', [PelayaranController::class, 'update'])->name('update');
        Route::delete('/{pelayaran}', [PelayaranController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('penjualan')->name('penjualan.')->group(function () {
        Route::get('/', [PenjualanController::class, 'index'])->name('index');
        Route::post('/', [PenjualanController::class, 'store'])->name('store');
        Route::post('/open-kas', [PenjualanController::class, 'openKas'])->name('open-kas');
        Route::post('/close-kas', [PenjualanController::class, 'closeKas'])->name('close-kas');
        Route::get('/report', [PenjualanController::class, 'report'])->name('report');
    });

    Route::prefix('operasional')->name('operasional.')->group(function () {
        Route::get('/', [OperasionalController::class, 'index'])->name('index');
        Route::post('/', [OperasionalController::class, 'store'])->name('store');
    });

    Route::prefix('pembelian')->name('pembelian.')->group(function () {
        Route::get('/', [PembelianController::class, 'index'])->name('index');

        Route::post('/items', [PembelianController::class, 'storeItem'])->name('items.store');
        Route::put('/items/{item}', [PembelianController::class, 'updateItem'])->name('items.update');
        Route::delete('/items/{item}', [PembelianController::class, 'destroyItem'])->name('items.destroy');

        Route::post('/transactions', [PembelianController::class, 'storeTransaction'])->name('transactions.store');
        Route::delete('/transactions/{transaction}', [PembelianController::class, 'destroyTransaction'])->name('transactions.destroy');
    });

    Route::prefix('stok')->name('stok.')->group(function () {
        Route::get('/ikan', [StokController::class, 'ikan'])->name('ikan.index');
        Route::get('/barang', [StokController::class, 'barang'])->name('barang.index');
    });

    Route::prefix('master/perbekalan')->name('master.perbekalan.')->group(function () {
        Route::get('/', [MasterPerbekalanController::class, 'index'])->name('index');
        Route::post('/', [MasterPerbekalanController::class, 'store'])->name('store');
        Route::put('/{perbekalan}', [MasterPerbekalanController::class, 'update'])->name('update');
        Route::delete('/{perbekalan}', [MasterPerbekalanController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('master/ikan')->name('master.ikan.')->group(function () {
        Route::get('/', [MasterIkanController::class, 'index'])->name('index');
        Route::post('/', [MasterIkanController::class, 'store'])->name('store');
        Route::put('/{ikan}', [MasterIkanController::class, 'update'])->name('update');
        Route::delete('/{ikan}', [MasterIkanController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('master/customer')->name('master.customer.')->group(function () {
        Route::get('/', [MasterCustomerController::class, 'index'])->name('index');
        Route::post('/', [MasterCustomerController::class, 'store'])->name('store');
        Route::put('/{customer}', [MasterCustomerController::class, 'update'])->name('update');
        Route::delete('/{customer}', [MasterCustomerController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('master/operasional')->name('master.operasional.')->group(function () {
        Route::get('/', [MasterOperasionalController::class, 'index'])->name('index');
        Route::post('/', [MasterOperasionalController::class, 'store'])->name('store');
        Route::put('/{operasional}', [MasterOperasionalController::class, 'update'])->name('update');
        Route::delete('/{operasional}', [MasterOperasionalController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('master/item-pembelian')->name('master.item-pembelian.')->group(function () {
        Route::get('/', [MasterItemPembelianController::class, 'index'])->name('index');
        Route::post('/', [MasterItemPembelianController::class, 'store'])->name('store');
        Route::put('/{itemPembelian}', [MasterItemPembelianController::class, 'update'])->name('update');
        Route::delete('/{itemPembelian}', [MasterItemPembelianController::class, 'destroy'])->name('destroy');
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
