@extends('layout.admin')

@section('title', 'Koreksi Absensi')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mx-auto py-4" style="max-width: 760px;">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Koreksi Absensi</h4>
                <p class="text-muted mb-0">Ajukan perubahan data absensi tanggal {{ \Carbon\Carbon::parse($attendanceDate)->translatedFormat('d F Y') }}.</p>
            </div>
            <a href="{{ route('attendance.history') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>

        <div class="card">
            <div class="card-body p-4">
                @if ($errors->has('attendance_date'))
                    <div class="alert alert-danger">{{ $errors->first('attendance_date') }}</div>
                @endif

                <form action="{{ route('attendance.corrections.store', $attendanceDate) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}" readonly>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="check_in_time" class="form-label">Date In</label>
                            <input
                                type="datetime-local"
                                id="check_in_time"
                                name="check_in_time"
                                class="form-control @error('check_in_time') is-invalid @enderror"
                                value="{{ old('check_in_time', $attendance?->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('Y-m-d\TH:i') : '') }}"
                                required>
                            @error('check_in_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="check_out_time" class="form-label">Date Out</label>
                            <input
                                type="datetime-local"
                                id="check_out_time"
                                name="check_out_time"
                                class="form-control @error('check_out_time') is-invalid @enderror"
                                value="{{ old('check_out_time', $attendance?->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('Y-m-d\TH:i') : '') }}"
                                required>
                            @error('check_out_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="station_id" class="form-label">Office</label>
                        <select id="station_id" name="station_id" class="form-select @error('station_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Office --</option>
                            @foreach ($stations as $station)
                                <option value="{{ $station->id }}" @selected((string) old('station_id', $attendance?->station_id) === (string) $station->id)>
                                    {{ $station->code }} — {{ $station->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('station_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="reason" class="form-label">Alasan Koreksi</label>
                        <textarea
                            id="reason"
                            name="reason"
                            rows="4"
                            maxlength="2000"
                            class="form-control @error('reason') is-invalid @enderror"
                            placeholder="Jelaskan alasan koreksi absensi..."
                            required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i>Kirim Pengajuan
                        </button>
                        <a href="{{ route('attendance.history') }}" class="btn btn-label-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
