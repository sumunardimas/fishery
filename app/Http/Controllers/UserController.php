<?php

namespace App\Http\Controllers;

use App\Models\Institusi;
use App\Models\Kasir;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index'); // resources/views/users/index.blade.php
    }

    /**
     * Data provider for server-side DataTable
     *
     * @return \Illuminate\Http\JsonResponse|\Yajra\DataTables\DataTableAbstract
     */
    public function data()
    {
        $response = Gate::inspect('read', User::class);

        if ($response->allowed()) {
            // 'profile' is an accessor, not an Eloquent relationship, so cannot be eager-loaded.
            // load roles; profile data will be retrieved via accessors when rendering columns.
            $query = User::with('roles');

            return DataTables::of($query)
                ->addIndexColumn() // DT_RowIndex for numbering
                ->addColumn('nama', fn ($user) => $user->display_name)
                ->addColumn('email', fn ($user) => $user->email)
                ->addColumn('whatsapp', fn ($user) => $user->profile?->whatsapp)
                ->addColumn('institusi', fn ($user) => $user->profile?->institusi?->nama)
                ->addColumn('role', fn ($user) => $user->role_name)
                ->addColumn('action', function ($user) {
                    return view('components.table-actions', [
                        'canEdit' => auth()->user()->can('update user'),
                        'editUrl' => route('users.edit', $user->id),
                        'canDelete' => auth()->user()->can('delete user'),
                        'deleteUrl' => route('users.destroy', $user->id),
                        'deleteName' => 'user '.$user->display_name,
                    ])->render();
                })
                ->make(true);
        }

        return response()->json(['message' => $response->message()]);
    }

    public function create()
    {
        // If you want to prefetch institusi in the view instead, you can skip compact
        $institusi = Institusi::all();

        return view('users.create', compact('institusi'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'gender' => ['required'],
            'role' => ['required'],
            'committee' => ['nullable', 'string', 'max:100'],
            'institusi_id' => ['required', 'integer', 'exists:institusis,id'],
            'document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
        ]);

        $normalizeGender = fn ($g) => ($g === 1 || $g === '1' || $g === 'Laki-laki') ? 1 : 0;

        $normalizeRole = function ($r) {
            $map = [
                'Admin' => 'Admin', 'panitia' => 'Admin',
                'Kasir' => 'Kasir', 'kasir' => 'Kasir',
                'Staff' => 'Staff', 'staff' => 'Staff',
            ];

            return $map[$r] ?? 'Staff';
        };

        $makeUsername = function (?string $email, string $nama) {
            $base = $email ? Str::before($email, '@') : Str::slug($nama);
            $base = $base ?: 'user';
            $candidate = $base;
            $i = 1;
            while (User::where('username', $candidate)->exists()) {
                $candidate = $base.$i;
                $i++;
            }

            return $candidate;
        };

        DB::beginTransaction();
        try {
            // users table only has: username, email, password
            $user = User::create([
                'username' => $makeUsername($data['email'] ?? null, $data['nama']),
                'email' => $data['email'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            $documentPath = $request->file('document')
                ? $request->file('document')->store('documents', 'public')
                : null;

            $gender = $normalizeGender($data['gender']);
            $role = $normalizeRole($data['role']);

            switch ($role) {
                case 'Admin':
                    Panitia::create([
                        'user_id' => $user->id,
                        'name' => $data['nama'],
                        'whatsapp' => $data['whatsapp'],
                        'gender' => $gender,
                        'institusi_id' => $data['institusi_id'],
                        'document' => $documentPath,
                    ]);
                    break;

                case 'Kasir':
                    Kasir::create([
                        'user_id' => $user->id,
                        'name' => $data['nama'],
                        'whatsapp' => $data['whatsapp'],
                        'gender' => $gender,
                        'institusi_id' => $data['institusi_id'],
                        'document' => $documentPath,
                    ]);
                    break;

                default: // Staff
                    Staff::create([
                        'user_id' => $user->id,
                        'name' => $data['nama'],
                        'whatsapp' => $data['whatsapp'],
                        'gender' => $gender,
                        'institusi_id' => $data['institusi_id'],
                        'document' => $documentPath,
                    ]);
                    break;
            }

            DB::commit();

            return redirect()->route('users.index')->with('status', 'Pengguna berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors(['message' => $e->getMessage()])->withInput();
        }
    }
}
