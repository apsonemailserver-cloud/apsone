@extends('layout.admin')

@section('title', 'Formulir Pengajuan Cuti')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Pengajuan Cuti / Izin</h5>
            <small class="text-muted float-end">Formulir Pengajuan Cuti & Izin Karyawan</small>
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

            <form method="POST" action="{{ route('leaves.store') }}" enctype="multipart/form-data">
                @csrf
                
                <div class="row g-3">
                    <div class="col-12">
                        <label for="leave_type_id" class="form-label">Jenis Pengajuan <span class="text-danger">*</span></label>
                        <select name="leave_type_id" id="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Jenis Cuti --</option>
                            @foreach($leaveTypes as $type)
                                @php
                                    $bal = $balances->get($type->id);
                                    $remaining = $type->is_unlimited ? 999 : ($bal ? $bal->remaining_days : $type->default_quota);
                                @endphp
                                <option value="{{ $type->id }}" 
                                        data-name="{{ $type->name }}"
                                        data-unlimited="{{ $type->is_unlimited ? 1 : 0 }}"
                                        data-remaining="{{ $remaining }}"
                                        {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        <div id="quota_info_text" class="form-text text-primary mt-1 fw-semibold d-none"></div>
                        @error('leave_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    @php
                        $minDate = \Carbon\Carbon::today()->subDays(7)->format('Y-m-d');
                    @endphp
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" min="{{ $minDate }}" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">Tanggal Berakhir <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" min="{{ $minDate }}" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Total Durasi Pengajuan</label>
                        <div id="total_days_display"><strong class="fs-6">0 Hari</strong></div>
                    </div>
                    
                    <div class="col-12">
                        <label for="reason" class="form-label">Alasan / Keterangan <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" rows="3" class="form-control @error('reason') is-invalid @enderror" placeholder="Tuliskan alasan pengajuan cuti..." required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="attachment" id="attachment_label" class="form-label">Lampiran Foto Bukti (Opsional, Max 2MB)</label>
                        <input type="file" name="attachment" id="attachment" accept="image/png, image/jpeg, image/jpg, image/webp" class="form-control @error('attachment') is-invalid @enderror">
                        <div class="form-text">Hanya diperbolehkan mengunggah file foto/gambar (JPG, JPEG, PNG, WEBP).</div>
                        @error('attachment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <x-form-actions :cancelHref="route('leaves.pengajuan')" submitText="Kirim Pengajuan" />
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const totalDaysDisplay = document.getElementById('total_days_display');
    const leaveTypeSelect = document.getElementById('leave_type_id');
    const attachmentInput = document.getElementById('attachment');
    const attachmentLabel = document.getElementById('attachment_label');
    const quotaInfoText = document.getElementById('quota_info_text');

    function calculateDays() {
        if (!startDateInput.value || !endDateInput.value) {
            totalDaysDisplay.innerHTML = '<strong class="fs-6">0 Hari</strong>';
            return;
        }

        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        if (endDate >= startDate) {
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            totalDaysDisplay.innerHTML = `<strong class="fs-6 text-primary">${diffDays} Hari</strong>`;
        } else {
            totalDaysDisplay.innerHTML = '<strong class="text-danger">Tanggal tidak valid</strong>';
        }
    }

    function handleLeaveTypeChange() {
        const selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            attachmentLabel.innerHTML = 'Lampiran Foto Bukti (Opsional, Max 2MB)';
            attachmentInput.removeAttribute('required');
            quotaInfoText.classList.add('d-none');
            return;
        }

        const isUnlimited = selectedOption.getAttribute('data-unlimited') === '1';
        const remaining = selectedOption.getAttribute('data-remaining');

        attachmentLabel.innerHTML = 'Lampiran Foto Bukti (Opsional, Max 2MB)';
        attachmentInput.removeAttribute('required');

        const quotaDisplay = isUnlimited ? 'Tidak Terbatas' : `${remaining} hari`;
        quotaInfoText.innerHTML = `<i class="bx bx-info-circle me-1"></i> Sisa kuota cuti: <strong>${quotaDisplay}</strong>`;
        quotaInfoText.classList.remove('d-none');
    }

    startDateInput.addEventListener('change', calculateDays);
    endDateInput.addEventListener('change', calculateDays);
    leaveTypeSelect.addEventListener('change', handleLeaveTypeChange);

    calculateDays();
    handleLeaveTypeChange();
});
</script>
@endsection
