@extends('layout.admin')

@section('title', 'Edit Pengumuman')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <span class="text-muted fw-light">Pengumuman /</span> Edit Pengumuman
            </h4>
            <p class="text-muted mb-0">Perbarui pengumuman.</p>
        </div>
        <a href="{{ route('announcements.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Formulir Edit Pengumuman</h5>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-4 border-0" role="alert">
                    <h6 class="alert-heading fw-bold mb-1">Terjadi Kesalahan Validation:</h6>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @php
                $targetStations = $announcement->target_stations ?? ['ALL'];
                $isAllStation = in_array('ALL', $targetStations, true);
            @endphp

            <form action="{{ route('announcements.update', $announcement->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark">Judul Pengumuman <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control form-control-lg" value="{{ old('title', $announcement->title) }}" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-dark">Target Station <span class="text-danger">*</span></label>
                    <p class="text-muted small mb-2">Pilih station mana saja yang dapat melihat pengumuman ini (Multi-Select):</p>
                    
                    <div class="dropdown position-relative" id="stationMultiSelectDropdownEdit">
                        <button class="btn btn-outline-secondary w-100 text-start d-flex align-items-center justify-content-between py-2 px-3 bg-white" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="border-radius: 0.375rem; min-height: 44px;">
                            <div class="d-flex flex-wrap gap-1 align-items-center" id="editSelectedStationsBadge">
                                <span class="badge bg-primary">Semua Station (ALL)</span>
                            </div>
                            <i class="ti ti-chevron-down text-muted ms-2"></i>
                        </button>

                        <div class="dropdown-menu w-100 p-2 shadow-lg border-0" style="max-height: 260px; overflow-y: auto; border-radius: 0.375rem;">
                            <div class="form-check p-2 rounded hover-bg-light cursor-pointer">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="target_stations[]" value="ALL" id="editStationAll" {{ in_array('ALL', old('target_stations', $targetStations), true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark cursor-pointer" for="editStationAll">
                                    Semua Station (ALL)
                                </label>
                            </div>
                            <hr class="my-1 opacity-25">
                            @foreach($stations as $st)
                                <div class="form-check p-2 rounded hover-bg-light cursor-pointer">
                                    <input class="form-check-input station-specific-checkbox-edit ms-0 me-2" type="checkbox" name="target_stations[]" value="{{ $st->code }}" id="editStation_{{ $st->code }}" {{ in_array($st->code, old('target_stations', $targetStations), true) ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark cursor-pointer" for="editStation_{{ $st->code }}">
                                        {{ $st->code }} <span class="text-muted">({{ $st->name }})</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-dark">Isi Pengumuman <span class="text-danger">*</span></label>
                    <textarea name="content" class="form-control" rows="8" required>{{ old('content', $announcement->content) }}</textarea>
                </div>

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="{{ route('announcements.index') }}" class="btn btn-label-secondary">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const allCheckbox = document.getElementById('editStationAll');
        const specificCheckboxes = document.querySelectorAll('.station-specific-checkbox-edit');
        const badgeContainer = document.getElementById('editSelectedStationsBadge');

        function updateBadges() {
            if (!badgeContainer) return;
            badgeContainer.innerHTML = '';

            if (allCheckbox && allCheckbox.checked) {
                badgeContainer.innerHTML = '<span class="badge bg-primary">Semua Station (ALL)</span>';
                return;
            }

            let selected = [];
            specificCheckboxes.forEach(function (cb) {
                if (cb.checked) {
                    selected.push(cb.value);
                }
            });

            if (selected.length === 0) {
                if (allCheckbox) allCheckbox.checked = true;
                badgeContainer.innerHTML = '<span class="badge bg-primary">Semua Station (ALL)</span>';
            } else {
                selected.forEach(function (code) {
                    const span = document.createElement('span');
                    span.className = 'badge bg-label-info';
                    span.innerText = code;
                    badgeContainer.appendChild(span);
                });
            }
        }

        if (allCheckbox) {
            allCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    specificCheckboxes.forEach(function (cb) {
                        cb.checked = false;
                    });
                }
                updateBadges();
            });
        }

        specificCheckboxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (this.checked && allCheckbox) {
                    allCheckbox.checked = false;
                }
                updateBadges();
            });
        });

        updateBadges();
    });
</script>
@endsection
