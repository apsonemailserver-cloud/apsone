@extends('layout.admin')

@section('title', 'Edit Data Role')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0 fw-bold">Edit Data Role: {{ $role->name }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('roles.update', $role->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="roleName">Nama Role</label>
                    <input type="text" id="roleName" class="form-control" value="{{ $role->name }}" disabled>
                    <div class="form-text">Nama peranan tidak dapat diubah karena merupakan kunci sistem.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="roleDescription">Deskripsi</label>
                    <textarea name="description" id="roleDescription" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Deskripsi peranan...">{{ old('description', $role->description) }}</textarea>
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
