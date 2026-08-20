@extends('layout.admin')

@section('title', 'Edit Shift')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Edit Shift</h5>
            <small class="text-muted float-end">Form Pengeditan Shift Operasional</small>
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

            <form action="{{ route('shift.update', $shift->id) }}" method="POST" id="shiftForm">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="id">Kode Shift <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               name="id" 
                               id="id" 
                               value="{{ $shift->id }}" 
                               readonly>
                        <div class="form-text">Kode Shift bersifat tetap (primary key).</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="name">Nama Shift <span class="text-danger">*</span></label>
                        <select class="form-select @error('name') is-invalid @enderror" name="name" id="name" required>
                            <option value="">Pilih Nama Shift</option>
                            <option value="Pagi" {{ old('name', $shift->name) === 'Pagi' ? 'selected' : '' }}>Pagi</option>
                            <option value="Siang" {{ old('name', $shift->name) === 'Siang' ? 'selected' : '' }}>Siang</option>
                            <option value="Malam" {{ old('name', $shift->name) === 'Malam' ? 'selected' : '' }}>Malam</option>
                            <option value="Libur" {{ old('name', $shift->name) === 'Libur' ? 'selected' : '' }}>Libur</option>
                        </select>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Deskripsi Shift <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('description') is-invalid @enderror" 
                               name="description" 
                               id="description" 
                               placeholder="Contoh: Shift operasional pagi hari" 
                               value="{{ old('description', $shift->description) }}" 
                               required>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="start_time">Jam Mulai <span class="text-danger">*</span></label>
                        <input type="time" 
                               class="form-control @error('start_time') is-invalid @enderror" 
                               name="start_time" 
                               id="start_time" 
                               value="{{ old('start_time', \Carbon\Carbon::parse($shift->start_time)->format('H:i')) }}" 
                               required>
                        @error('start_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="end_time">Jam Berakhir <span class="text-danger">*</span></label>
                        <input type="time" 
                               class="form-control @error('end_time') is-invalid @enderror" 
                               name="end_time" 
                               id="end_time" 
                               value="{{ old('end_time', \Carbon\Carbon::parse($shift->end_time)->format('H:i')) }}" 
                               required>
                        @error('end_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="use_manpower">Jumlah Tenaga Kerja (Manpower) <span class="text-danger">*</span></label>
                        <input type="number" 
                               class="form-control @error('use_manpower') is-invalid @enderror" 
                               name="use_manpower" 
                               id="use_manpower" 
                               placeholder="Contoh: 5" 
                               value="{{ old('use_manpower', $shift->use_manpower) }}" 
                               min="0" 
                               max="50" 
                               required>
                        <div class="form-text">Kapasitas staff yang dibutuhkan untuk shift ini.</div>
                        @error('use_manpower')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="tolerance_minutes">Toleransi Keterlambatan (Menit)</label>
                        <input type="number" 
                               class="form-control @error('tolerance_minutes') is-invalid @enderror" 
                               name="tolerance_minutes" 
                               id="tolerance_minutes" 
                               placeholder="Contoh: 15" 
                               value="{{ old('tolerance_minutes', $shift->tolerance_minutes ?? 15) }}" 
                               min="0" 
                               max="120">
                        <div class="form-text">Batas toleransi check-in sebelum status dianggap terlambat (default: 15 menit).</div>
                        @error('tolerance_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <x-form-actions :cancelHref="route('shift.index')" submitText="Update Data" />
            </form>
        </div>
    </div>
</div>
@endsection
