@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Tambah Sub Unit</h5>
        </div>
        <div class="card-body pt-4">
            <form action="{{ route('master_data.sub_units.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Sub Unit <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: PORTER APRON" value="{{ old('name') }}" required />
                    @error('name')
                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                    @enderror
                </div>

                <x-form-actions :cancelHref="route('master_data.sub_units.index')" submitText="Simpan Data" />
            </form>
        </div>
    </div>
</div>
@endsection
