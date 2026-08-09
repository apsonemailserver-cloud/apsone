@extends('layout.admin')
@section('title', 'Tambah Sertifikat Training')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Tambah Sertifikat Training</h5>
        </div>
        <div class="card-body pt-4">
            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <strong>Periksa Inputan Anda:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('training.certificates.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="certificate_name">Nama Sertifikat / Pelatihan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="certificate_name" id="certificate_name"
                            placeholder="Contoh: Basic Safety & SMS 2026" value="{{ old('certificate_name') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="certificate_type">Kategori / Tipe Training <span class="text-danger">*</span></label>
                        <select class="form-select" name="certificate_type" id="certificate_type" required>
                            <option value="">Pilih Kategori</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" {{ old('certificate_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="start_date">Tanggal Mulai / Diterbitkan <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="start_date" id="start_date"
                            value="{{ old('start_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="end_date">Tanggal Masa Kadaluarsa <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="end_date" id="end_date"
                            value="{{ old('end_date') }}" required>
                    </div>

                    <div class="col-12 mb-2">
                        <label class="form-label fw-semibold" for="certificate_file">Upload File Sertifikat (PDF / Gambar) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="certificate_file" id="certificate_file"
                            accept=".pdf,.jpg,.jpeg,.png" required>
                        <div class="form-text">Format didukung: PDF, JPG, PNG (Maksimal 2MB).</div>
                    </div>
                </div>

                <x-form-actions :cancelHref="route('my.certificates')" submitText="Kirim Pengajuan Sertifikat" />
            </form>
        </div>
    </div>
</div>"
@endsection
