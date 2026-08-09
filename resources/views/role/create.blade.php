@extends('layout.admin')

@section('title', 'Tambah Role Baru')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 fw-bold">Tambah Role Baru</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="roleName">Nama Kode Role <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="roleName" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: Work Order CS" required>
                    <div class="form-text">Gunakan nama kode unik peranan role dalam sistem.</div>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>



                <div class="mb-3">
                    <label class="form-label fw-semibold" for="roleDescription">Deskripsi</label>
                    <textarea name="description" id="roleDescription" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Deskripsi peranan...">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <x-form-actions :cancelHref="route('roles.index')" submitText="Simpan Data" />
            </form>
        </div>
    </div>
</div>
@endsection
