@extends('layout.admin')

@section('content')
<div class="d-flex align-items-center mb-4">
    <a href="{{ route('admin.training.certificates.index') }}" class="btn btn-icon btn-outline-secondary me-3 rounded-circle shadow-xs" title="Back to Training List">
        <i class="ti ti-arrow-left fs-4"></i>
    </a>
    <div>
        <h4 class="fw-bold mb-0 text-dark">
            <span class="text-muted fw-light">General /</span> Tambah Sertifikat Training Baru
        </h4>
    </div>
</div>

<div class="card mb-4">
    <h5 class="card-header">Formulir Tambah Sertifikat</h5>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.training.certificates.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="user_id" class="form-label">Pilih Staff</label>
                <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                    <option value="">-- Pilih Staff --</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->fullname }} (NIP: {{ $user->id }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="certificate_name" class="form-label">Nama Sertifikat Training</label>
                <input type="text" class="form-control @error('certificate_name') is-invalid @enderror" id="certificate_name" name="certificate_name" value="{{ old('certificate_name') }}" required placeholder="Contoh: Diklat Basic AVSEC 2026">
                @error('certificate_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="certificate_type" class="form-label">Jenis Sertifikat</label>
                <select class="form-select @error('certificate_type') is-invalid @enderror" id="certificate_type" name="certificate_type">
                    <option value="">-- Pilih Jenis Sertifikat --</option>
                    @foreach (\App\Models\Certificate::TYPES as $type)
                        <option value="{{ $type }}" {{ old('certificate_type') == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
                @error('certificate_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="start_date" class="form-label">Masa Berlaku Awal</label>
                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="end_date" class="form-label">Masa Berlaku Akhir</label>
                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                    @error('end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="certificate_file" class="form-label">File Sertifikat (PDF, JPG, PNG)</label>
                <input type="file" class="form-control @error('certificate_file') is-invalid @enderror" id="certificate_file" name="certificate_file">
                @error('certificate_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Simpan Sertifikat</button>
        </form>
    </div>
</div>
@endsection
