@extends('layout.admin')

@section('title', 'Tambah Shift Baru')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Tambah Shift Baru</h5>
            <small class="text-muted float-end">Form Input Shift Operasional</small>
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

            <form action="{{ route('shift.store') }}" method="POST" id="shiftForm">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="id">Kode Shift <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('id') is-invalid @enderror" 
                               name="id" 
                               id="id" 
                               value="{{ old('id', $autoShiftId ?? '') }}" 
                               readonly 
                               required>
                        <div class="form-text">Kode Shift di-generate otomatis oleh sistem.</div>
                        @error('id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="name">Nama Shift <span class="text-danger">*</span></label>
                        <select class="form-select @error('name') is-invalid @enderror" name="name" id="name" required>
                            <option value="">Pilih Nama Shift</option>
                            <option value="Pagi" {{ old('name') === 'Pagi' ? 'selected' : '' }}>Pagi</option>
                            <option value="Siang" {{ old('name') === 'Siang' ? 'selected' : '' }}>Siang</option>
                            <option value="Malam" {{ old('name') === 'Malam' ? 'selected' : '' }}>Malam</option>
                        </select>
                        <div class="form-text">Kategori nama shift operasional.</div>
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
                               value="{{ old('description') }}" 
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
                               value="{{ old('start_time') }}" 
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
                               value="{{ old('end_time') }}" 
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
                               value="{{ old('use_manpower') }}" 
                               min="1" 
                               max="50" 
                               required>
                        <div class="form-text">Kapasitas staff yang dibutuhkan untuk shift ini.</div>
                        @error('use_manpower')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <x-form-actions :cancelHref="route('shift.index')" submitText="Simpan Data" />
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('shiftForm');
        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');

        if (!form || !startTime || !endTime) return;

        function validateTime() {
            if (startTime.value && endTime.value && startTime.value >= endTime.value) {
                endTime.setCustomValidity('Jam berakhir harus setelah jam mulai');
                endTime.classList.add('is-invalid');
                return false;
            } else {
                endTime.setCustomValidity('');
                endTime.classList.remove('is-invalid');
                return true;
            }
        }

        startTime.addEventListener('change', validateTime);
        endTime.addEventListener('change', validateTime);

        form.addEventListener('submit', function(e) {
            if (!validateTime()) {
                e.preventDefault();
                e.stopPropagation();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Validasi Jam Kerja',
                        text: 'Jam berakhir harus setelah jam mulai.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });
</script>
@endsection
