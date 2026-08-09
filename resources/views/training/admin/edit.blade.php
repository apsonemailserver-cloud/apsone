@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Edit Sertifikat Training</h5>
        </div>
        <div class="card-body pt-4">
            <form action="{{ route('admin.training.certificates.update', $certificate) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="user_id" class="form-label fw-semibold">Pilih Staff <span class="text-danger">*</span></label>
                    @if(Auth::user()->isAdmin() || Auth::user()->role === 'Admin')
                        <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                            <option value="">-- Pilih Staff --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ (old('user_id', $certificate->user_id) == $user->id) ? 'selected' : '' }}>
                                    {{ $user->fullname }} (NIP: {{ $user->id }})
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="form-control" value="{{ $certificate->user->fullname ?? Auth::user()->fullname }} (NIP: {{ $certificate->user_id }})" disabled>
                        <input type="hidden" name="user_id" value="{{ $certificate->user_id }}">
                    @endif
                    @error('user_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="certificate_name" class="form-label fw-semibold">Nama Sertifikat Training <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('certificate_name') is-invalid @enderror" id="certificate_name" name="certificate_name" value="{{ old('certificate_name', $certificate->certificate_name) }}" required>
                    @error('certificate_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="certificate_type" class="form-label fw-semibold">Jenis Sertifikat</label>
                    <select class="form-select @error('certificate_type') is-invalid @enderror" id="certificate_type" name="certificate_type">
                        <option value="">-- Pilih Jenis Sertifikat --</option>
                        @foreach (\App\Models\Certificate::TYPES as $type)
                            <option value="{{ $type }}" {{ old('certificate_type', $certificate->certificate_type) == $type ? 'selected' : '' }}>
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
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', $certificate->start_date->format('Y-m-d')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label fw-semibold">Masa Berlaku Akhir <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', $certificate->end_date->format('Y-m-d')) }}" required>
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
                    @if ($certificate->certificate_file)
                        <div class="mt-2">
                            File saat ini: <a href="{{ Storage::url($certificate->certificate_file) }}" target="_blank">Lihat File</a>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remove_file" id="remove_file" value="1">
                                <label class="form-check-label" for="remove_file">
                                    Hapus File Saat Ini
                                </label>
                            </div>
                        </div>
                    @endif
                </div>
                <x-form-actions :cancelHref="route('admin.training.certificates.index')" submitText="Perbarui Sertifikat" />
            </form>
        </div>
    </div>
</div>"
@endsection