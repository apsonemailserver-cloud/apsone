@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Tambah Cluster Baru</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('master_data.clusters.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nama Cluster <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Masukkan Nama Cluster" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <x-form-actions :cancelHref="route('master_data.clusters.index')" submitText="Simpan Data" />
            </form>
        </div>
    </div>
</div>
@endsection
