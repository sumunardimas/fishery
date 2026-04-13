<div class="row mb-4">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1">Mutasi {{ $title }}</h4>
                <p class="card-description mb-3">Filter periode mutasi akun {{ strtolower($title) }}.</p>

                <form method="GET" action="{{ url('/keuangan/' . $akun) }}" class="mb-0">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-4">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" id="start_date" name="start_date" class="form-control"
                                value="{{ $startDate }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="end_date">Tanggal Akhir</label>
                            <input type="date" id="end_date" name="end_date" class="form-control"
                                value="{{ $endDate }}">
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-center">
                            <button type="submit" class="btn btn-outline-primary mr-2">Terapkan</button>
                            <a href="{{ url('/keuangan/' . $akun) }}" class="btn btn-light">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Saldo {{ $title }} Terkini</p>
                <h4 class="text-primary">Rp {{ number_format($summary['saldo_terkini'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Total Debit</p>
                <h4 class="text-success">Rp {{ number_format($summary['total_debit'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Total Kredit</p>
                <h4 class="text-danger">Rp {{ number_format($summary['total_kredit'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Mutasi Bersih</p>
                <h4>Rp {{ number_format($summary['net'], 2, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Input Transaksi {{ $title }}</h4>
                <p class="card-description">Gunakan debit untuk penambahan saldo dan kredit untuk pengurangan saldo.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('keuangan.cash.store') }}">
                    @csrf
                    <input type="hidden" name="akun" value="{{ $akun }}">

                    <div class="form-group">
                        <label for="tanggal">Tanggal</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control"
                            value="{{ old('tanggal', now()->toDateString()) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="kategori">Kategori</label>
                        <select id="kategori" name="kategori" class="form-control" required>
                            @php
                                $kategoriOptions = [
                                    'Modal Disetor',
                                    'Penjualan',
                                    'Pelunasan Piutang',
                                    'Biaya Operasional',
                                    'Transfer Antar Akun',
                                    'Penyesuaian',
                                    'Pinjam Modal Bu Uum',
                                    'Pinjam Modal Jons Group',
                                    'Kas Bon Pegawai',
                                ];
                                $selectedKategori = old('kategori');
                            @endphp
                            <option value="">Pilih kategori</option>
                            @foreach ($kategoriOptions as $opt)
                                <option value="{{ $opt }}"
                                    {{ $selectedKategori === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Transaksi kategori Pinjam Modal Bu Uum dan Pinjam Modal Jons Group otomatis dicatat
                            sebagai debit pada akun ini, menambah saldo akun, dan membentuk kredit pada
                            <a href="{{ url('/keuangan/hutang-modal') }}">halaman Hutang Modal</a>.
                            Kategori Kas Bon Pegawai otomatis masuk ke
                            <a href="{{ url('/keuangan/kas-bon-pegawai') }}">halaman Kas Bon Pegawai</a>.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <input type="text" id="deskripsi" name="deskripsi" class="form-control"
                            value="{{ old('deskripsi') }}" maxlength="255" placeholder="Contoh: Setoran modal awal">
                        <small class="form-text text-muted">
                            Untuk Kas Bon Pegawai, isi nama pegawai pada kolom deskripsi.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="debit">Debit (menambah saldo)</label>
                        <input type="text" id="debit" name="debit" class="form-control" data-rupiah-input
                            value="{{ old('debit') }}" placeholder="0,00">
                    </div>

                    <div class="form-group">
                        <label for="kredit">Kredit (mengurangi saldo)</label>
                        <input type="text" id="kredit" name="kredit" class="form-control" data-rupiah-input
                            value="{{ old('kredit') }}" placeholder="0,00">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Simpan Transaksi</button>
                </form>

            </div>
        </div>
    </div>

    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Mutasi {{ $title }}</h4>
                <p class="card-description">Riwayat transaksi dan perubahan saldo akun {{ strtolower($title) }}.</p>

                <div class="table-responsive">
                    <table id="ledger-table" class="display expandable-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Debit</th>
                                <th>Kredit</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row->id_kas }}</td>
                                    <td>{{ $row->tanggal }}</td>
                                    <td>{{ $row->kategori }}</td>
                                    <td>{{ $row->deskripsi }}</td>
                                    <td>Rp {{ number_format((float) $row->uang_masuk, 2, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $row->uang_keluar, 2, ',', '.') }}</td>
                                    <td>Rp {{ number_format((float) $row->saldo, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada transaksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            const forceDebitCategories = [
                'Pinjam Modal Bu Uum',
                'Pinjam Modal Jons Group',
            ];

            const forceCreditCategories = [
                'Kas Bon Pegawai',
            ];

            const $kategori = $('#kategori');
            const $debit = $('#debit');
            const $kredit = $('#kredit');

            const syncNominalRule = () => {
                const kategori = $kategori.val();
                const forceDebit = forceDebitCategories.includes(kategori);
                const forceCredit = forceCreditCategories.includes(kategori);

                if (forceDebit) {
                    $kredit.val('');
                    $kredit.prop('disabled', true);
                    $kredit.attr('placeholder', 'Tidak digunakan untuk kategori ini');
                    $debit.prop('disabled', false);
                    $debit.attr('placeholder', '0');
                    $debit.focus();
                } else if (forceCredit) {
                    $debit.val('');
                    $debit.prop('disabled', true);
                    $debit.attr('placeholder', 'Tidak digunakan untuk kategori ini');
                    $kredit.prop('disabled', false);
                    $kredit.attr('placeholder', '0');
                    $kredit.focus();
                } else {
                    $debit.prop('disabled', false);
                    $debit.attr('placeholder', '0');
                    $kredit.prop('disabled', false);
                    $kredit.attr('placeholder', '0');
                }
            };

            $kategori.on('change', syncNominalRule);
            syncNominalRule();

            $('#ledger-table').DataTable({
                ...window.dataTableGeneralConfig,
                processing: false,
                serverSide: false,
                order: [
                    [1, 'desc'],
                    [0, 'desc']
                ],
            });
        });
    </script>
@endpush
