<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    /** Keys managed on this page (order determines display order). */
    private const KEYS = [
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'invoice_notes',
    ];

    public function index(): View
    {
        $rows = DB::table('app_settings')
            ->whereIn('key', self::KEYS)
            ->pluck('value', 'key');

        $settings = collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => $rows[$k] ?? '']);

        return view('pengaturan.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name'         => ['nullable', 'string', 'max:120'],
            'company_address'      => ['nullable', 'string', 'max:255'],
            'company_phone'        => ['nullable', 'string', 'max:30'],
            'company_email'        => ['nullable', 'email', 'max:120'],
            'bank_name'            => ['nullable', 'string', 'max:80'],
            'bank_account_number'  => ['nullable', 'string', 'max:50'],
            'bank_account_holder'  => ['nullable', 'string', 'max:80'],
            'invoice_notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $now = now();

        foreach ($validated as $key => $value) {
            DB::table('app_settings')->upsert(
                [['key' => $key, 'value' => $value ?? '', 'created_at' => $now, 'updated_at' => $now]],
                ['key'],
                ['value', 'updated_at']
            );
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    /**
     * Retrieve all settings as a key→value array (used by other controllers/views).
     */
    public static function getAll(): array
    {
        return DB::table('app_settings')->pluck('value', 'key')->toArray();
    }
}
