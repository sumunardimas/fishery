<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\KapalController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\KeuanganController;
use App\Http\Controllers\MasterCustomerController;
use App\Http\Controllers\MasterIkanController;
use App\Http\Controllers\MasterIkanTangkapanController;
use App\Http\Controllers\MasterItemPembelianController;
use App\Http\Controllers\MasterOperasionalController;
use App\Http\Controllers\MasterPerbekalanController;
use App\Http\Controllers\OperasionalController;
use App\Http\Controllers\OperasionalKantorController;
use App\Http\Controllers\PelayaranController;
use App\Http\Controllers\PelayaranSisaController;
use App\Http\Controllers\PelayaranSisa2Controller;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\ProfileDocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\UpdateProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'staff.menu.access'])->group(function () {
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
        Route::get('/sisa2', [PelayaranSisa2Controller::class, 'index'])->name('sisa2.index');
        Route::post('/sisa2/tangkapan-pribadi', [PelayaranSisa2Controller::class, 'storePersonalTangkapan'])->name('sisa2.tangkapan-pribadi.store');
        Route::get('/sisa', [PelayaranSisaController::class, 'index'])->name('sisa.index');
        Route::get('/sisa/history', [PelayaranSisaController::class, 'history'])->name('sisa.history');
        Route::get('/sisa/history/{pelayaran}', [PelayaranSisaController::class, 'showHistoryDetail'])->name('sisa.history.show');
        Route::get('/laporan/{pelayaran}', [PelayaranSisaController::class, 'report'])->name('report.show');
        Route::post('/sisa/perbekalan', [PelayaranSisaController::class, 'storePerbekalan'])->name('sisa.perbekalan.store');
        Route::post('/sisa/tangkapan', [PelayaranSisaController::class, 'storeTangkapan'])->name('sisa.tangkapan.store');
        Route::post('/sisa/operasional', [PelayaranSisaController::class, 'storeOperasional'])->name('sisa.operasional.store');
        Route::post('/sisa/close', [PelayaranSisaController::class, 'closePelayaran'])->name('sisa.close');
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
        Route::get('/riwayat', [PenjualanController::class, 'riwayat'])->name('riwayat');
        Route::get('/selisih', [PenjualanController::class, 'discrepancies'])->name('selisih.index');
        Route::post('/selisih/manual-adjustment', [PenjualanController::class, 'storeManualAdjustment'])->name('selisih.manual');
        Route::get('/selisih/{id}', [PenjualanController::class, 'showDiscrepancy'])->name('selisih.show')->where('id', '[0-9]+');
        Route::post('/selisih/{id}/resolve', [PenjualanController::class, 'resolveDiscrepancy'])->name('selisih.resolve')->where('id', '[0-9]+');
        Route::get('/report', [PenjualanController::class, 'report'])->name('report');
        Route::get('/{id}/invoice', [PenjualanController::class, 'downloadInvoice'])->name('invoice')->where('id', '[0-9]+');
        Route::get('/{id}/invoice/preview', [PenjualanController::class, 'previewInvoice'])->name('invoice.preview')->where('id', '[0-9]+');
    });

    Route::prefix('operasional')->name('operasional.')->group(function () {
        Route::get('/', [OperasionalController::class, 'index'])->name('index');
        Route::get('/transaksi', [OperasionalController::class, 'transaksi'])->name('transaksi');
        Route::get('/master', [MasterOperasionalController::class, 'index'])->name('master');
        Route::post('/', [OperasionalController::class, 'store'])->name('store');
    });

    Route::prefix('operasional-kantor')->name('operasional-kantor.')->group(function () {
        Route::get('/', [OperasionalKantorController::class, 'index'])->name('index');
        Route::get('/transaksi', [OperasionalKantorController::class, 'transaksi'])->name('transaksi');
        Route::get('/history', [OperasionalKantorController::class, 'history'])->name('history');
        Route::post('/master-items', [OperasionalKantorController::class, 'storeMaster'])->name('master.store');
        Route::put('/master-items/{masterItem}', [OperasionalKantorController::class, 'updateMaster'])->name('master.update');
        Route::delete('/master-items/{masterItem}', [OperasionalKantorController::class, 'destroyMaster'])->name('master.destroy');
        Route::post('/', [OperasionalKantorController::class, 'store'])->name('store');
        Route::delete('/transactions/{transaction}', [OperasionalKantorController::class, 'destroyTransaction'])->name('transactions.destroy');
    });

    Route::prefix('keuangan')->name('keuangan.')->group(function () {
        Route::get('/lap-penjualan', [PenjualanController::class, 'keuanganPenjualanSummary'])->name('lap-penjualan.index');
        Route::get('/arus-kas', [KeuanganController::class, 'arusKas'])->name('arus-kas.index');
        Route::get('/kas', [KeuanganController::class, 'kas'])->name('kas.index');
        Route::get('/bank', [KeuanganController::class, 'bank'])->name('bank.index');
        Route::get('/kas-induk', [KeuanganController::class, 'kasInduk'])->name('kas-induk.index');
        Route::post('/cash/transactions', [KeuanganController::class, 'storeCashTransaction'])->name('cash.store');
        Route::post('/kas-induk', [KeuanganController::class, 'storeKasIndukTransfer'])->name('kas-induk.store');
        Route::get('/kas-bon-pegawai', [KeuanganController::class, 'kasBonPegawai'])->name('kas-bon-pegawai.index');
        Route::post('/kas-bon-pegawai/bayar', [KeuanganController::class, 'bayarKasBonPegawai'])->name('kas-bon-pegawai.bayar');
        Route::get('/hutang-modal', [KeuanganController::class, 'jonsGroupDebt'])->name('hutang-modal.index');
        Route::post('/hutang-modal/bayar', [KeuanganController::class, 'bayarJonsGroupDebt'])->name('hutang-modal.bayar');
        Route::get('/hutang-jons-group', fn () => redirect()->route('keuangan.hutang-modal.index'))->name('hutang-jons-group.index');
        Route::post('/hutang-jons-group/bayar', [KeuanganController::class, 'bayarJonsGroupDebt'])->name('hutang-jons-group.bayar');
        Route::get('/laba', [KeuanganController::class, 'labaRugi'])->name('laba.index');
        Route::get('/lap-selisih-bongkaran', [KeuanganController::class, 'selisihBongkar'])->name('lap-selisih-bongkaran.index');
        Route::post('/lap-selisih-bongkaran/berat-lelang', [KeuanganController::class, 'storeBeratLelang'])->name('lap-selisih-bongkaran.store');
        Route::get('/piutang', [KeuanganController::class, 'piutang'])->name('piutang.index');
        Route::post('/piutang/bayar', [KeuanganController::class, 'bayarPiutang'])->name('piutang.bayar');
    });

    Route::prefix('pembelian')->name('pembelian.')->group(function () {
        Route::get('/', [PembelianController::class, 'index'])->name('index');
        Route::get('/transaksi', [PembelianController::class, 'transaksi'])->name('transaksi');
        Route::get('/riwayat', [PembelianController::class, 'riwayat'])->name('riwayat');

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
        Route::get('/transaksi', [MasterPerbekalanController::class, 'transaksi'])->name('transaksi');
        Route::get('/history', [MasterPerbekalanController::class, 'history'])->name('history');
        Route::post('/', [MasterPerbekalanController::class, 'store'])->name('store');
        Route::put('/{perbekalan}', [MasterPerbekalanController::class, 'update'])->name('update');
        Route::delete('/{perbekalan}', [MasterPerbekalanController::class, 'destroy'])->name('destroy');
        Route::post('/transactions', [MasterPerbekalanController::class, 'storeTransaction'])->name('transactions.store');
        Route::delete('/transactions/{transaction}', [MasterPerbekalanController::class, 'destroyTransaction'])->name('transactions.destroy');
    });

    Route::prefix('master/ikan')->name('master.ikan.')->group(function () {
        Route::get('/', [MasterIkanController::class, 'index'])->name('index');
        Route::post('/', [MasterIkanController::class, 'store'])->name('store');
        Route::put('/{ikan}', [MasterIkanController::class, 'update'])->name('update');
        Route::delete('/{ikan}', [MasterIkanController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('master/ikan-tangkapan')->name('master.ikan-tangkapan.')->group(function () {
        Route::get('/', [MasterIkanTangkapanController::class, 'index'])->name('index');
        Route::post('/', [MasterIkanTangkapanController::class, 'store'])->name('store');
        Route::put('/{ikanTangkapan}', [MasterIkanTangkapanController::class, 'update'])->name('update');
        Route::delete('/{ikanTangkapan}', [MasterIkanTangkapanController::class, 'destroy'])->name('destroy');
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
    Route::get('/profile/document', [ProfileDocumentController::class, 'show'])->name('profile.document.show');

    Route::get('/profile/edit', function () {
        $user = auth()->user();
        $profile = $user->getProfile();

        return view('pages.updateprofile', compact('user', 'profile'));
    })->name('profile.edit');

    Route::put('/profile', [UpdateProfileController::class, 'update'])->name('profile.update');

    Route::get('/homeadmin', [HomeController::class, 'adminHome'])->name('home.admin')->middleware('role:admin');

    Route::view('/panduan', 'panduan.index')->name('panduan.index');

    Route::prefix('pengaturan')->name('pengaturan.')->group(function () {
        Route::get('/', [PengaturanController::class, 'index'])->name('index');
        Route::put('/', [PengaturanController::class, 'update'])->name('update');
    });

});

Route::view('/resetpassword', 'pages.resetpassword')->name('pages.resetpassword');
Route::view('/newpassword', 'pages.newpassword')->name('password.reset');
Route::get('/uat-checklist', function () {
    return response()->file(base_path('uat-checklist.html'));
})->name('uat.checklist');

require __DIR__.'/auth.php';
