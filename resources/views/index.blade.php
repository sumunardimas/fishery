@extends('layouts.layout')

@section('title', 'Beranda')

@section('content')
    <div class="row">
    <div class="col-md-12 grid-margin">
        <div class="row">
        <div class="col-12 col-xl-8 mb-4 mb-xl-0">
            <h3 class="font-weight-bold">Selamat datang di <br> Sistem Carenusa</h3>
            <h6 class="font-weight-normal mb-0"></h6>
        </div>
        <div class="col-12 col-xl-4">
            <div class="justify-content-end d-flex">
            <div class="dropdown flex-md-grow-1 flex-xl-grow-0">
            <button class="btn btn-sm btn-light bg-white dropdown-toggle" type="button" id="dropdownMenuDate2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <i class="mdi mdi-calendar"></i> Pilih Uji Kompetensi
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuDate2">
                {{-- @foreach($ujian as $item)
                    <a class="dropdown-item" href="{!! $item->id !!}">{!! $item->periode !!}</a>
                @endforeach --}}
                <a class="dropdown-item" href="#">II 2025</a>
                <a class="dropdown-item" href="#">I 2025</a>
            </div>
            </div>
            </div>
        </div>
        </div>
    </div>
    </div>
    <div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card tale-bg">
        <div class="card-people mt-auto">
            <img src="images/dashboard/peoples.svg" alt="people">
            <div class="weather-info">
            <div class="d-flex">
                <div>
                <h2 class="mb-0 font-weight-normal"><i class="icon-sun mr-2"></i>26<sup>C</sup></h2>
                </div>
                <div class="ml-2">
                <h4 class="location font-weight-normal">Jakarta</h4>
                <h6 class="font-weight-normal">Indonesia</h6>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>
    <div class="col-md-6 grid-margin transparent">
        <div class="row">
        <div class="col-md-6 mb-4 stretch-card transparent">
            <div class="card card-tale">
            <div class="card-body">
                <p class="mb-4">Jumlah Mahasiswa</p>
                <p class="fs-30 mb-2">20</p>
                <p></p>
            </div>
            </div>
        </div>
        <div class="col-md-6 mb-4 stretch-card transparent">
            <div class="card card-dark-blue">
            <div class="card-body">
                <p class="mb-4">Jumlah Institusi</p>
                <p class="fs-30 mb-2">5</p>
                <p></p>
            </div>
            </div>
        </div>
        </div>
        <div class="row">
        <div class="col-md-6 stretch-card transparent">
        <div class="card card-light-danger">
        <div class="card-body">
            <p class="mb-4">Tahapan Terakhir</p>
            <p class="fs-30 mb-2">Tahap 2</p>
            <p></p>
        </div>
        </div>
        </div>
        <div class="col-md-6 mb-4 mb-lg-0 stretch-card transparent">
            <div class="card card-light-blue">
            <div class="card-body">
                <p class="mb-4">Persentase Kelulusan</p>
                <p class="fs-30 mb-2">%</p>
                <p></p>
            </div>
            </div>
        </div>

        </div>
    </div>
    </div>
    <div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
            <p class="card-title mb-0">Jadwal Pelaksanaan Ujian</p>
            <div class="table-responsive">
            <table class="table table-striped table-borderless">
                <thead>
                <tr>
                    <th>Tahapan</th>
                    <th>Mulai</th>
                    <th>Berakhir</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Tahap I: Pendaftaran Peserta</td>
                    <td>H-60</td>
                    <td>H-51</td>
                    <td class="font-weight-medium"><div class="badge badge-info">Selesai</div></td>
                </tr>

                <tr>
                    <td>Tahap 2: Desk Evaluation</td>
                    <td>H-50</td>
                    <td>H-21</td>
                    <td class="font-weight-medium"><div class="badge badge-success">Aktif</div></td>
                </tr>

                <tr>
                    <td>Tahap 3: Konfirmasi</td>
                    <td>H-20</td>
                    <td>H-4</td>
                    <td class="font-weight-medium"><div class="badge badge-warning">Pending</div></td>
                </tr>

                <tr>
                    <td>Tahap 4: Uji Kompetensi</td>
                    <td>H-1</td>
                    <td>H+1</td>
                    <td class="font-weight-medium"><div class="badge badge-warning">Pending</div></td>
                </tr>

                </tbody>
            </table>
            </div>
        </div>
        </div>
    </div>
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between">
            <p class="card-title">Gender Balance Peserta Ujian</p>
            <a href="#" class="text-info"></a>
            </div>
            <p class="font-weight-500"></p>
            <div id="sales-legend" class="chartjs-legend mt-4 mb-2"></div>
            <canvas id="sales-chart"></canvas>
        </div>
        </div>
    </div>
    </div>

    <div class="row">
        <div class="col-md-4 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-0">Panitia Ujian</p>
                    <div class="table-responsive">
                    <table id="panitia-table" class="table table-borderless">
                        <thead>
                        <tr>
                            <th class="pl-0  pb-2 border-bottom">Nama</th>
                            <th class="border-bottom pb-2">Institusi</th>
                            <th class="border-bottom pb-2">Jabatan</th>
                        </tr>
                        </thead>
                        <tbody>
                        {{-- <tr>
                            <td class="pl-0"></td>
                            <td><p class="mb-0"><span class="font-weight-bold mr-2"></p></td>
                            <td></td>
                        </tr> --}}
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-0">Penguji</p>
                    <div class="table-responsive">
                    <table id="staff-table" class="table table-borderless">
                        <thead>
                        <tr>
                            <th class="pl-0  pb-2 border-bottom">Nama</th>
                            <th class="border-bottom pb-2">Institusi</th>
                        </tr>
                        </thead>
                        <tbody>
                        {{-- <tr>
                            <td class="pl-0"></td>
                            <td><p class="mb-0"><span class="font-weight-bold mr-2"></p></td>
                            <td></td>
                        </tr> --}}
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-0">Jadwal Visitasi</p>
                    <div class="table-responsive">
                    <table id="visitasi-table" class="table table-borderless">
                        <thead>
                        <tr>
                            <th class="pl-0  pb-2 border-bottom">Nama Mahasiswa</th>
                            <th class="border-bottom pb-2">Waktu Visitasi</th>
                            <th class="border-bottom pb-2">Hasil Visitasi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="pl-0"></td>
                            <td><p class="mb-0"><span class="font-weight-bold mr-2"></p></td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                    <div class="card-body">
                        <p class="card-title">Peserta Uji Kompetensi</p>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table id="tabel-pendaftaran" class="display expandable-table w-100">
                                        <thead>
                                        <tr>
                                            <th>No</th>
                                            <th style="min-width: 180px">Nama Mahasiswa</th>
                                            <th style="min-width: 120px">Institusi</th>
                                            <th>Tanggal Pendaftaran</th>
                                            <th>Status Validasi Data</th>
                                            <th>Catatan</th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>



        <!-- content-wrapper ends -->


@endsection

@push('scripts')
<script>
$(function () {
    $('#panitia-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 50,
        lengthChange: false,
        searching: false,
        info: false,
        paging: false,
        ajax: '{{ route('users.panitia.data') }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'institution', name: 'institution' },
            { data: 'committee', name: 'committee' }
        ],
        order: [[1, 'asc']],
    });

    $('#staff-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 50,
        lengthChange: false,
        searching: false,
        info: false,
        paging: false,
        ajax: '{{ route('users.staff.data') }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'institution', name: 'institution' },
        ],
        order: [[1, 'asc']],
    });

    $('#visitasi-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 50,
        lengthChange: false,
        searching: false,
        info: false,
        paging: false,
        ajax: '{{ route('visitasi.data') }}',
        columns: [
            { data: 'mahasiswa', name: 'mahasiswa' },
            { data: 'waktu', name: 'waktu' },
            { data: null, name: 'empty_column', render: () => '' },
        ]
    });

    $('#tabel-pendaftaran').DataTable({
        ...window.dataTableGeneralConfig,
        ajax: '{{ route('pendaftaran.data') }}',
        order: [[1, 'asc']],
        columns: [
            {
                data: null,
                name: 'rownum',
                orderable: false,
                searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'nama_mahasiswa',
            },
            {
                data: 'nama_institusi',
            },
            {
                data: 'tanggal_pendaftaran',
            },
            {
                data: 'validasi_dikti',
            },
            {
                data: 'catatan',
                searchable: false,
            },
        ]
    })

});
</script>
@endpush
