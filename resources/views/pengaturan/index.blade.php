@extends('layouts.layout')

@section('title', 'Pengaturan Sistem')

@section('content')
    <div class="row">
        <div class="col-12">

            @if (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            <form action="{{ route('pengaturan.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Profil Perusahaan --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Profil Perusahaan / TPI</h4>
                        <p class="card-description mb-4">Informasi ini ditampilkan pada header invoice dan laporan.</p>

                        <div class="form-group">
                            <label for="company_name">Nama Perusahaan / TPI</label>
                            <input type="text" id="company_name" name="company_name"
                                class="form-control @error('company_name') is-invalid @enderror"
                                value="{{ old('company_name', $settings['company_name']) }}"
                                placeholder="Contoh: TPI Sadeng">
                            @error('company_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="company_address">Alamat</label>
                            <textarea id="company_address" name="company_address" rows="2"
                                class="form-control @error('company_address') is-invalid @enderror" placeholder="Jalan, Desa, Kecamatan, Kabupaten">{{ old('company_address', $settings['company_address']) }}</textarea>
                            @error('company_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="company_phone">Nomor Telepon</label>
                                <input type="text" id="company_phone" name="company_phone"
                                    class="form-control @error('company_phone') is-invalid @enderror"
                                    value="{{ old('company_phone', $settings['company_phone']) }}"
                                    placeholder="+62 8xx xxxx xxxx">
                                @error('company_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="company_email">Email</label>
                                <input type="email" id="company_email" name="company_email"
                                    class="form-control @error('company_email') is-invalid @enderror"
                                    value="{{ old('company_email', $settings['company_email']) }}"
                                    placeholder="info@example.com">
                                @error('company_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Informasi Bank --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Informasi Bank</h4>
                        <p class="card-description mb-4">Ditampilkan pada bagian pembayaran transfer di invoice.</p>

                        <div class="form-group">
                            <label for="bank_name">Nama Bank</label>
                            <input type="text" id="bank_name" name="bank_name"
                                class="form-control @error('bank_name') is-invalid @enderror"
                                value="{{ old('bank_name', $settings['bank_name']) }}" placeholder="Contoh: Bank BRI">
                            @error('bank_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="bank_account_number">Nomor Rekening</label>
                                <input type="text" id="bank_account_number" name="bank_account_number"
                                    class="form-control @error('bank_account_number') is-invalid @enderror"
                                    value="{{ old('bank_account_number', $settings['bank_account_number']) }}"
                                    placeholder="xxxx-xx-xxxxxx-xx-x">
                                @error('bank_account_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="bank_account_holder">Atas Nama</label>
                                <input type="text" id="bank_account_holder" name="bank_account_holder"
                                    class="form-control @error('bank_account_holder') is-invalid @enderror"
                                    value="{{ old('bank_account_holder', $settings['bank_account_holder']) }}"
                                    placeholder="Nama pemilik rekening">
                                @error('bank_account_holder')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Invoice --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Catatan Invoice</h4>
                        <p class="card-description mb-4">Teks tambahan yang muncul di bagian bawah invoice.</p>

                        <div class="form-group">
                            <label for="invoice_notes">Catatan / Footer</label>
                            <textarea id="invoice_notes" name="invoice_notes" rows="3"
                                class="form-control @error('invoice_notes') is-invalid @enderror"
                                placeholder="Contoh: Terima kasih atas kepercayaan Anda.">{{ old('invoice_notes', $settings['invoice_notes']) }}</textarea>
                            @error('invoice_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Simpan Pengaturan</button>
            </form>

        </div>
    </div>
@endsection
