<?php

namespace App\Http\Controllers;

use App\Models\MasterCustomer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MasterCustomerController extends Controller
{
    public function index(): View
    {
        $items = MasterCustomer::query()->orderByDesc('created_at')->get();

        return view('master.customer.index', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_customer' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
        ]);

        MasterCustomer::create($data);

        return redirect()->route('master.customer.index')->with('success', 'Master customer berhasil ditambahkan.');
    }

    public function update(Request $request, MasterCustomer $customer): RedirectResponse
    {
        $data = $request->validate([
            'nama_customer' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
        ]);

        $customer->update($data);

        return redirect()->route('master.customer.index')->with('success', 'Master customer berhasil diperbarui.');
    }

    public function destroy(MasterCustomer $customer): RedirectResponse
    {
        $idCustomer = (int) $customer->id_customer;

        if (DB::table('penjualan')->where('id_customer', $idCustomer)->exists()) {
            return redirect()->route('master.customer.index')->withErrors([
                'message' => 'Data tidak bisa dihapus karena sudah digunakan pada transaksi penjualan.',
            ]);
        }

        $customer->delete();

        return redirect()->route('master.customer.index')->with('success', 'Master customer berhasil dihapus.');
    }
}
