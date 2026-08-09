@extends('layout.admin')
@section('title', 'Input Lembur')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="mb-0 fw-bold">Form Pengajuan Lembur</h5>
        </div>
        <div class="card-body pt-4">
            <form action="{{ route('overtime.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tanggal Lembur <span class="text-danger">*</span></label>
                    <input type="date" name="date" id="overtime_date" class="form-control" required value="{{ date('Y-m-d') }}" onchange="fetchCalculatedDuration()">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Judul Kegiatan <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Contoh: Loading Cargo Qantas" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Durasi (Jam) <span class="text-danger">*</span></label>
                    <input type="number" step="0.5" name="duration" id="overtime_duration" class="form-control" placeholder="1" min="0.5" required>
                    <div class="form-text" id="duration_hint">Durasi dikalkulasi otomatis dari data check-out absensi Anda.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi Detail <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <x-form-actions :cancelHref="route('overtime.index')" submitText="Kirim Pengajuan" />
            </form>
        </div>
    </div>
</div>

<script>
    function fetchCalculatedDuration() {
        const dateVal = document.getElementById('overtime_date').value;
        const hintEl = document.getElementById('duration_hint');
        const durationInput = document.getElementById('overtime_duration');
        if (!dateVal) return;

        fetch(`{{ route('overtime.calculate_duration') }}?date=${dateVal}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.duration) {
                    durationInput.value = data.duration;
                    hintEl.className = "form-text text-success font-weight-bold";
                    hintEl.innerHTML = `<i class="bx bx-check-circle me-1"></i> ${data.message}`;
                } else if (data.message) {
                    hintEl.className = "form-text text-muted";
                    hintEl.innerHTML = `<i class="bx bx-info-circle me-1"></i> ${data.message}`;
                }
            })
            .catch(() => {});
    }
    document.addEventListener('DOMContentLoaded', fetchCalculatedDuration);
</script>
@endsection