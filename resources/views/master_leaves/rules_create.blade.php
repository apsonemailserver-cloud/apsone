@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Tambah Aturan Skala Masa Kerja ({{ $leaveType->name }})</h5>
        </div>
        <div class="card-body pt-4">
            <form action="{{ route('master_leaves.rules.store', $leaveType->id) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Min Masa Kerja (Tahun) <span class="text-danger">*</span></label>
                    <input type="number" name="min_tenure_years" class="form-control" min="0" placeholder="Misal: 1" value="{{ old('min_tenure_years') }}" required />
                    <div class="form-text">Batas minimal masa kerja karyawan dalam satuan tahun.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Max Masa Kerja (Tahun)</label>
                    <input type="number" name="max_tenure_years" class="form-control" min="0" placeholder="Kosongkan jika tidak ada batas atas (&ge; Min)" value="{{ old('max_tenure_years') }}" />
                    <div class="form-text">Kosongkan jika aturan berlaku untuk masa kerja minimal tersebut ke atas.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kuota Hari Cuti Khusus <span class="text-danger">*</span></label>
                    <input type="number" name="quota_days" class="form-control" min="0" placeholder="Contoh: 14" value="{{ old('quota_days') }}" required />
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Keterangan / Deskripsi</label>
                    <input type="text" name="description" class="form-control" placeholder="Contoh: Masa kerja 1-2 tahun jatah 14 hari" value="{{ old('description') }}" />
                </div>

                <x-form-actions :cancelHref="route('master_leaves.rules.index', $leaveType->id)" submitText="Simpan Aturan" />
            </form>
        </div>
    </div>
</div>
@endsection
