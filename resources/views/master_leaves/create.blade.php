@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Tambah Tipe Cuti</h5>
            <small class="text-muted float-end">Form Pengaturan Master Cuti</small>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('master_leaves.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Nama Tipe Cuti <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: Cuti Menikah, Cuti Ibadah" value="{{ old('name') }}" required />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="gender_restriction">Batasan Gender <span class="text-danger">*</span></label>
                        <select name="gender_restriction" id="gender_restriction" class="form-select @error('gender_restriction') is-invalid @enderror" required>
                            <option value="All" {{ old('gender_restriction') == 'All' ? 'selected' : '' }}>Semua Gender (Semua Karyawan)</option>
                            <option value="Male" {{ old('gender_restriction') == 'Male' ? 'selected' : '' }}>Pria Saja (Male Only)</option>
                            <option value="Female" {{ old('gender_restriction') == 'Female' ? 'selected' : '' }}>Wanita Saja (Female Only)</option>
                        </select>
                        @error('gender_restriction')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="default_quota">Kuota Default (Hari / Tahun) <span class="text-danger">*</span></label>
                        <input type="number" name="default_quota" id="default_quota" class="form-control @error('default_quota') is-invalid @enderror" min="0" value="{{ old('default_quota', 0) }}" required />
                        <div class="form-text">Jumlah kuota hari cuti dasar per tahun.</div>
                        @error('default_quota')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label d-block">Batas Kuota</label>
                        <div class="form-check form-switch d-flex align-items-center pt-2">
                            <input class="form-check-input me-2" type="checkbox" role="switch" name="is_unlimited" id="is_unlimited" value="1" {{ old('is_unlimited') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_unlimited">
                                Kuota Tidak Terbatas
                            </label>
                        </div>
                        <div class="form-text">Aktifkan jika tipe cuti ini bebas batas hari.</div>
                    </div>
                </div>

                <x-form-actions :cancelHref="route('master_leaves.index')" submitText="Simpan Tipe Cuti" />
            </form>
        </div>
    </div>
</div>
@endsection
