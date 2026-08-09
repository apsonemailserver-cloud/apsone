@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Edit Unit</h5>
        </div>
        <div class="card-body pt-4">
            <form action="{{ route('master_data.units.update', $unit->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Unit <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $unit->name) }}" required />
                    @error('name')
                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                    @enderror
                </div>

                <x-form-actions :cancelHref="route('master_data.units.index')" submitText="Simpan Perubahan" />
            </form>
        </div>
    </div>
</div>
@endsection
