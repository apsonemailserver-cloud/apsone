@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Edit Jabatan / Job Title</h5>
        </div>
        <div class="card-body pt-4">
            <form action="{{ route('master_data.job_titles.update', $jobTitle->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Jabatan / Job Title <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $jobTitle->name) }}" required />
                    @error('name')
                        <small class="text-danger mt-1 d-block">{{ $message }}</small>
                    @enderror
                </div>

                <x-form-actions :cancelHref="route('master_data.job_titles.index')" submitText="Simpan Perubahan" />
            </form>
        </div>
    </div>
</div>
@endsection
