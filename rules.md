# Project Rules

Conventions and constraints for the **Sistem Manajemen Perikanan** Laravel 12 codebase.

---

## Stack

| Layer | Choice |
|---|---|
| PHP | 8.2+ |
| Framework | Laravel 12 |
| Auth / Roles | Spatie Laravel Permission (`admin`, `staff`, `kasir`) |
| Frontend | Blade + Livewire Flux + Alpine.js + Tailwind CSS v4 |
| Build | Vite 6 (`npm run dev` / `npm run build`) |
| Database | MySQL / MariaDB |
| PDF | barryvdh/laravel-dompdf |
| Tables | yajra/laravel-datatables-oracle 12 |

---

## Naming Conventions

### Routes
- Use **kebab-case** prefixes (`operasional-kantor`, `kas-bon-pegawai`).
- Named routes follow the pattern `{prefix}.{action}`, e.g. `pelayaran.sisa.index`, `master.perbekalan.transaksi`.
- Master-data route groups use the `master.` prefix: `master.ikan.*`, `master.perbekalan.*`, `master.customer.*`, `master.ikan-tangkapan.*`.

### Controllers
- One controller per resource/feature area.
- Return type hints required: `View`, `RedirectResponse`, `JsonResponse`.
- Validation is done inline via `$request->validate([...])` for simple forms, or via a dedicated `FormRequest` class when reused.

### Models
- Table name explicitly set via `$table`.
- Primary key explicitly set via `$primaryKey` (e.g. `id_kapal`, `id_pelayaran`).
- All date columns cast in `$casts`; monetary columns cast to `float` or `decimal`.
- `$fillable` must be explicitly declared — no `$guarded = []`.

### Database
- Migration filenames: `YYYY_MM_DD_HHMMSS_<verb>_<description>.php`.
- Column names use **snake_case** Indonesian terminology consistent with existing tables (e.g. `tanggal_berangkat`, `jumlah_trip`, `nama_kapal`).
- Foreign keys use the pattern `id_{parent_table_singular}`.

### Views
- Located under `resources/views/{module}/`.
- One Blade file per action: `index.blade.php`, `create.blade.php`, `transaksi.blade.php`, `history.blade.php`.
- All user-visible text is in **Bahasa Indonesia**.

---

## Module Structure Pattern

Each business module follows the same three-file pattern (mirroring `master/perbekalan`):

```
Controller method  →  View file
index()            →  {module}/index.blade.php      (master list / settings)
transaksi()        →  {module}/transaksi.blade.php  (input form)
history()          →  {module}/history.blade.php    (read-only log, filterable by date)
```

Menu config in `config/menu.php` mirrors the same three submenus: **Master**, **Transaksi**, **Riwayat In Out**.

---

## Security

- Self-registration is **disabled** by default (`AUTH_ALLOW_SELF_REGISTRATION=false`). If ever re-enabled, `RegisteredUserController` must only assign the `staff` role — never allow a client-supplied role.
- Profile photos and user documents are stored on **private local disk** and served through authenticated routes only. Never expose them via a public disk URL.
- Never add unauthenticated debug routes that expose user or model data.
- All routes are protected by the `auth` middleware and `staff.menu.access`. Admin-only routes additionally use `->middleware('role:admin')`.
- Deletion in master controllers must check for FK references via `DB::table(...)->where(fk, ...)->exists()` before proceeding and return `withErrors(['message' => ...])` on conflict.

---

## Code Style

- **PSR-12**. Run `vendor/bin/pint` before committing.
- No `var_dump`, `dd`, `dump`, or `print_r` left in committed code.
- Return type declarations are required on all controller methods.
- Avoid raw `DB::statement` for DML outside of migrations.
- Prefer `redirect()->to(route('name').'#anchor')` for redirects with URL fragments — do not string-concatenate onto a `RedirectResponse`.

---

## Testing

- All new features must have at least one feature test in `tests/Feature/`.
- Run: `php artisan test` or `vendor/bin/phpunit`.
- Factories must exist for every Eloquent model used in tests.

---

## Arus Kas (Double-Entry Rule)

Every financial transaction that affects cash or bank **must** write a corresponding row to the `arus_kas` table with the correct `akun` value (`kas` or `bank`). Do not insert into domain tables (penjualan, pembelian, perbekalan, operasional, etc.) without also recording the arus kas entry.

---

## Menu Configuration

- `config/menu.php` is the single source of truth for the sidebar.
- Each item supports: `title`, `icon` (ThemeIcons CSS class), `route`, `type` (`route`|`url`), `roles` (array), `children`.
- Add new modules to the menu config before wiring up the controller/routes.
