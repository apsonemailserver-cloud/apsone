@extends('layout.admin')

@section('title', 'Koreksi Absensi')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Form Koreksi Absensi ({{ \Carbon\Carbon::parse($attendanceDate)->translatedFormat('d F Y') }})</h5>
        </div>
        <div class="card-body pt-4">
            @if ($errors->has('attendance_date'))
                <div class="alert alert-danger mb-4">{{ $errors->first('attendance_date') }}</div>
            @endif

            <form action="{{ route('attendance.corrections.store', $attendanceDate) }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal</label>
                    <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}" readonly>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="check_in_time" class="form-label fw-semibold">Jam In <span class="text-danger">*</span></label>
                        <input
                            type="time"
                            id="check_in_time"
                            name="check_in_time"
                            class="form-control @error('check_in_time') is-invalid @enderror"
                            value="{{ old('check_in_time', $attendance?->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '') }}"
                            required>
                        @error('check_in_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="check_out_time" class="form-label fw-semibold">Jam Out <span class="text-danger">*</span></label>
                        <input
                            type="time"
                            id="check_out_time"
                            name="check_out_time"
                            class="form-control @error('check_out_time') is-invalid @enderror"
                            value="{{ old('check_out_time', $attendance?->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '') }}"
                            required>
                        @error('check_out_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="station_id" class="form-label fw-semibold">Office <span class="text-danger">*</span></label>
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
                    <label for="reason" class="form-label fw-semibold">Alasan Koreksi <span class="text-danger">*</span></label>"
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

                <x-form-actions :cancelHref="route('attendance.history')" submitText="Kirim Pengajuan" />
            </form>
        </div>
    </div>
</div>
@endsection
