@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Tambah Sertifikat Training</h5>
        </div>
        <div class="card-body pt-4">
            <form method="POST" action="{{ route('admin.training.certificates.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="user_id" class="form-label fw-semibold">Pilih Staff <span class="text-danger">*</span></label>
                    @if(Auth::user()->isAdmin() || Auth::user()->role === 'Admin')
                        <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                            <option value="">-- Pilih Staff --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->fullname }} (NIP: {{ $user->id }})
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="form-control" value="{{ Auth::user()->fullname }} (NIP: {{ Auth::user()->id }})" disabled>
                        <input type="hidden" name="user_id" value="{{ Auth::user()->id }}">
                    @endif
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="certificate_name" class="form-label fw-semibold">Nama Sertifikat Training <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('certificate_name') is-invalid @enderror" id="certificate_name" name="certificate_name" value="{{ old('certificate_name') }}" required placeholder="Contoh: Diklat Basic AVSEC 2026">
                    @error('certificate_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="certificate_type" class="form-label fw-semibold">Jenis Sertifikat</label>
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
                        <label for="start_date" class="form-label fw-semibold">Masa Berlaku Awal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label fw-semibold">Masa Berlaku Akhir <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-4">
                    <label for="certificate_file" class="form-label fw-semibold">File Sertifikat (PDF, JPG, PNG)</label>
                    <input type="file" class="form-control @error('certificate_file') is-invalid @enderror" id="certificate_file" name="certificate_file" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="form-text mt-1">Format didukung: PDF, JPG, PNG (Maksimal 2MB).</div>
                    @error('certificate_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <x-form-actions :cancelHref="route('admin.training.certificates.index')" submitText="Simpan Sertifikat" />
            </form>
        </div>
    </div>
</div>"
@endsection
