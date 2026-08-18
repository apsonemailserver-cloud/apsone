@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Edit Sub Unit</h5>
        </div>
        <div class="card-body pt-4">
            <form action="{{ route('master_data.sub_units.update', $subUnit->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Unit Induk <span class="text-danger">*</span></label>
                    <select name="unit_id" class="form-select" required>
                        <option value="">-- Pilih Unit Induk --</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $subUnit->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Sub Unit <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $subUnit->name) }}" required />
                    @error('name')
                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                    @enderror
                </div>

                <x-form-actions :cancelHref="route('master_data.sub_units.index')" submitText="Simpan Perubahan" />
            </form>
        </div>
    </div>
</div>
@endsection
