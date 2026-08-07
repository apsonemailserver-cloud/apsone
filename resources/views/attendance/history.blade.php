@extends('layout.admin')

@section('title', 'Riwayat Absensi')

@section('styles')
<style>
    .attendance-row.has-note {
        transition: background-color 0.15s ease;
    }
    .attendance-row.has-note:hover {
        background-color: rgba(37, 99, 235, 0.06) !important;
    }
    .note-truncate {
        max-width: 160px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
        vertical-align: middle;
    }

    .action-btn-edit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 0.75rem;
        background-color: #fff7ed !important;
        border: 1.5px solid #fed7aa !important;
        color: #d97706 !important;
        text-decoration: none !important;
        transition: all 0.2s ease-in-out;
        box-shadow: none !important;
    }

    .action-btn-edit:hover {
        background-color: #ffedd5 !important;
        border-color: #f97316 !important;
        color: #c2410c !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(249, 115, 22, 0.15) !important;
    }

    .action-btn-edit i {
        font-size: 1.15rem;
        line-height: 1;
    }

    /* Dark Mode Styling for Modal & Edit Button */
    html.aps-dark .action-btn-edit {
        background-color: rgba(249, 115, 22, 0.15) !important;
        border-color: rgba(249, 115, 22, 0.35) !important;
        color: #fbbf24 !important;
    }

    html.aps-dark .action-btn-edit:hover {
        background-color: rgba(249, 115, 22, 0.28) !important;
        border-color: rgba(249, 115, 22, 0.6) !important;
        color: #ffffff !important;
    }

    html.aps-dark #noteDetailModal .modal-content {
        background-color: #1e293b;
        color: #f8fafc;
    }
    html.aps-dark #noteDetailModal .bg-light {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }
    html.aps-dark #noteDetailModal .bg-body {
        background-color: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }
    html.aps-dark #noteDetailModal .text-dark {
        color: #f8fafc !important;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Presence Report ({{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }})</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Detail riwayat absensi harian karyawan.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Attendance</a></li>
                    <li class="breadcrumb-item active">History</li>
                </ol>
            </nav>
        </div>

        {{-- Action Bar: Back & Month Filter --}}
        @php
            $currentMonthCarbon = \Carbon\Carbon::parse($month);
            $prevMonth = $currentMonthCarbon->copy()->subMonth()->format('Y-m');
            $nextMonth = $currentMonthCarbon->copy()->addMonth()->format('Y-m');
            $nowMonth = \Carbon\Carbon::now()->format('Y-m');
        @endphp
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center" style="height: 36px; padding-left: 0.85rem; padding-right: 0.85rem;">
                <i class="bx bx-arrow-back me-1"></i> Back
            </a>

            <form method="GET" action="{{ route('attendance.history') }}" class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                <div class="d-inline-flex align-items-center gap-1">
                    <a href="{{ route('attendance.history', ['month' => $prevMonth]) }}" 
                       class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center p-0" 
                       style="width: 36px; height: 36px; flex-shrink: 0;" 
                       title="Bulan Sebelumnya">
                        <i class="bx bx-chevron-left fs-5"></i>
                    </a>

                    <input type="month" name="month" 
                        class="form-control form-control-sm text-center fw-semibold" 
                        value="{{ $month }}" 
                        onchange="this.form.submit()" 
                        style="width: 140px !important; height: 36px; flex-shrink: 0; font-size: 0.85rem;">

                    <a href="{{ route('attendance.history', ['month' => $nextMonth]) }}" 
                       class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center p-0" 
                       style="width: 36px; height: 36px; flex-shrink: 0;" 
                       title="Bulan Selanjutnya">
                        <i class="bx bx-chevron-right fs-5"></i>
                    </a>
                </div>

                @if($month !== $nowMonth)
                <a href="{{ route('attendance.history') }}" 
                   class="btn btn-sm btn-primary rounded-3 px-3 fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center" 
                   style="height: 36px; white-space: nowrap; flex-shrink: 0;" 
                   title="Kembali ke Bulan Ini">
                    <i class="bx bx-calendar me-1 fs-6"></i> Bulan Ini
                </a>
                @endif
            </form>
        </div>

        {{-- Card --}}
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0" style="color: #374151; font-weight: 600;">Attendance History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table text-center align-middle">
                        <thead style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>Date</th>
                                <th>Office</th>
                                <th>Shift</th>
                                <th>In</th>
                                <th>Out</th>
                                <th style="max-width: 180px; width: 180px;">Note Koreksi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daysInMonth as $day => $data)
                            @php
                            $attendance = $data['attendance'];
                            $schedule = $data['schedule'];
                            $correction = $data['correction'];

                            $currentDate = \Carbon\Carbon::parse($month)->day($day);

                            $startTime = $schedule ? \Carbon\Carbon::parse($schedule->start_time) : null;
                            $endTime = $schedule ? \Carbon\Carbon::parse($schedule->end_time) : null;
                            $shiftStart = $schedule ? \Carbon\Carbon::parse($currentDate->toDateString() . ' ' . $schedule->start_time) : null;
                            $shiftEnd = $schedule ? \Carbon\Carbon::parse($currentDate->toDateString() . ' ' . $schedule->end_time) : null;

                            if ($shiftStart && $shiftEnd && $shiftEnd->lte($shiftStart)) {
                                $shiftEnd->addDay();
                            }

                            $checkIn = $attendance && $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time) : null;
                            $checkOut = $attendance && $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time) : null;

                            if ($correction && $correction->status === \App\Models\AttendanceCorrection::STATUS_APPROVED) {
                                $checkIn = \Carbon\Carbon::parse($correction->proposed_check_in_time);
                                $checkOut = \Carbon\Carbon::parse($correction->proposed_check_out_time);
                            }

                            $stationCode = '-';
                            if ($attendance && $attendance->check_in_time) {
                                $stationCode = $attendance->station?->code ?? $user->station ?? '-';
                            }
                            if ($correction && $correction->status === \App\Models\AttendanceCorrection::STATUS_APPROVED) {
                                $stationCode = $correction->station?->code ?? $user->station ?? '-';
                            }

                            $isShiftKosong = $startTime && $startTime->format('H:i') === '00:00' && $endTime && $endTime->format('H:i') === '00:00';

                            $today = now()->startOfDay();
                            $isFuture = $currentDate->gt($today);

                            $hasNote = !empty($correction?->reason);
                            $statusClass = '';
                            if ($correction) {
                                $statusClass = match ($correction->status) {
                                    \App\Models\AttendanceCorrection::STATUS_APPROVED => 'bg-label-success',
                                    \App\Models\AttendanceCorrection::STATUS_REJECTED => 'bg-label-danger',
                                    default => 'bg-label-warning',
                                };
                            }
                            @endphp
                            <tr class="attendance-row {{ $hasNote ? 'has-note' : '' }}"
                                @if($hasNote)
                                    style="cursor: pointer;"
                                    title="Klik row untuk lihat detail note koreksi"
                                    data-date="{{ $currentDate->translatedFormat('d F Y') }}"
                                    data-station="{{ $stationCode }}"
                                    data-shift="{{ $schedule ? $startTime->format('H:i') . ' - ' . $endTime->format('H:i') : '-' }}"
                                    data-in="{{ $checkIn ? $checkIn->format('H:i') : '-' }}"
                                    data-out="{{ $checkOut ? $checkOut->format('H:i') : '-' }}"
                                    data-status="{{ $correction ? ucfirst($correction->status) : '' }}"
                                    data-status-class="{{ $statusClass }}"
                                    data-note="{{ $correction->reason }}"
                                @endif
                            >
                                <td>{{ $day }}</td>
                                <td>{{ $stationCode }}</td>
                                <td>
                                    @if($schedule)
                                    {{ $startTime->format('H:i') }} - {{ $endTime->format('H:i') }}
                                    @else
                                    -
                                    @endif
                                </td>

                                {{-- Kolom In --}}
                                <td class="
                                    @if(!$isFuture && !$isShiftKosong)
                                        @if(!$checkIn)
                                            bg-danger text-white
                                        @elseif($shiftStart && $checkIn->gt($shiftStart))
                                            bg-danger text-white
                                        @elseif($shiftStart && $checkIn->lte($shiftStart))
                                            bg-success text-white
                                        @endif
                                    @endif
                                " style="border-radius: 0.25rem;">
                                    {{ $checkIn ? $checkIn->format('H:i') : '-' }}
                                </td>

                                {{-- Kolom Out --}}
                                <td class="
                                    @if(!$isFuture && !$isShiftKosong)
                                        @if(!$checkOut)
                                            bg-danger text-white
                                        @elseif($shiftEnd && $checkOut->gte($shiftEnd))
                                            bg-success text-white
                                        @elseif($shiftEnd && $checkOut->lt($shiftEnd))
                                            bg-danger text-white
                                        @endif
                                    @endif
                                " style="border-radius: 0.25rem;">
                                    {{ $checkOut ? $checkOut->format('H:i') : '-' }}
                                </td>
                                
                                {{-- Kolom Note Koreksi --}}
                                <td class="correction-note" style="max-width: 180px;">
                                    @if($hasNote)
                                        <div class="note-truncate mx-auto" title="{{ $correction->reason }}">
                                            <i class="ti ti-notes text-primary me-1"></i>
                                            <span>{{ $correction->reason }}</span>
                                        </div>
                                        @if ($correction && $correction->status === 'rejected' && !empty($correction->rejection_reason))
                                            <div class="small text-danger mt-1 text-truncate" title="Alasan Penolakan: {{ $correction->rejection_reason }}">
                                                <i class="ti ti-alert-circle me-1"></i>{{ $correction->rejection_reason }}
                                            </div>
                                        @endif
                                    @else
                                        @if ($correction && $correction->status === 'rejected' && !empty($correction->rejection_reason))
                                            <div class="small text-danger text-truncate" title="Alasan Penolakan: {{ $correction->rejection_reason }}">
                                                <i class="ti ti-alert-circle me-1"></i>{{ $correction->rejection_reason }}
                                            </div>
                                        @else
                                            -
                                        @endif
                                    @endif
                                </td>

                                <td>
                                    @if ($correction)
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($correction->status) }}</span>
                                    @elseif (!$isFuture && ($canEditMonth ?? false))
                                        <a href="{{ route('attendance.corrections.create', $currentDate->toDateString()) }}" class="action-btn-edit" title="Edit Absensi">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Keterangan --}}
        <div class="d-flex flex-wrap gap-4 mt-4">
            <div class="d-flex align-items-center gap-2">
                <span style="display:inline-block;width:12px;height:12px;background-color:#f0fdf4;border:1px solid #bbf7d0;border-radius:3px;"></span>
                <small class="text-muted">On Time</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span style="display:inline-block;width:12px;height:12px;background-color:#fef2f2;border:1px solid #fecaca;border-radius:3px;"></span>
                <small class="text-muted">Terlambat / Pulang Cepat / Tidak Absen</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span style="display:inline-block;width:12px;height:12px;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:3px;"></span>
                <small class="text-muted">Hari yang akan datang</small>
            </div>
        </div>
    </div>
</div>

{{-- Modal Detail Note Koreksi --}}
<div class="modal fade" id="noteDetailModal" tabindex="-1" aria-labelledby="noteDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="noteDetailModalLabel">
                    <i class="ti ti-notes text-primary fs-4"></i> Detail Note Koreksi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-3 p-3 rounded bg-light border">
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Tanggal</small>
                        <strong id="modalDateVal" class="text-dark">-</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Status Koreksi</small>
                        <span id="modalStatusBadge" class="badge bg-label-secondary">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Office / Station</small>
                        <span id="modalOfficeVal" class="fw-semibold text-dark">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Shift</small>
                        <span id="modalShiftVal" class="fw-semibold text-dark">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Jam Masuk (In)</small>
                        <span id="modalInVal" class="fw-semibold text-dark">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block mb-1">Jam Keluar (Out)</small>
                        <span id="modalOutVal" class="fw-semibold text-dark">-</span>
                    </div>
                </div>

                <div>
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="ti ti-file-description me-1 text-primary"></i> Catatan Koreksi:
                    </label>
                    <div id="modalNoteContent" class="p-3 rounded border bg-body" style="word-break: break-word; white-space: pre-wrap; font-size: 0.925rem; line-height: 1.6;"></div>
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-primary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('noteDetailModal');
        if (!modalEl) return;

        const noteModal = new bootstrap.Modal(modalEl);
        const modalDateVal = document.getElementById('modalDateVal');
        const modalStatusBadge = document.getElementById('modalStatusBadge');
        const modalOfficeVal = document.getElementById('modalOfficeVal');
        const modalShiftVal = document.getElementById('modalShiftVal');
        const modalInVal = document.getElementById('modalInVal');
        const modalOutVal = document.getElementById('modalOutVal');
        const modalNoteContent = document.getElementById('modalNoteContent');

        document.querySelectorAll('.attendance-row.has-note').forEach(row => {
            row.addEventListener('click', function (e) {
                if (e.target.closest('a') || e.target.closest('button')) return;

                const note = this.dataset.note;
                if (!note) return;

                modalDateVal.textContent = this.dataset.date || '-';
                modalOfficeVal.textContent = this.dataset.station || '-';
                modalShiftVal.textContent = this.dataset.shift || '-';
                modalInVal.textContent = this.dataset.in || '-';
                modalOutVal.textContent = this.dataset.out || '-';

                const status = this.dataset.status;
                const statusClass = this.dataset.statusClass;
                if (status) {
                    modalStatusBadge.textContent = status;
                    modalStatusBadge.className = 'badge ' + (statusClass || 'bg-label-primary');
                    modalStatusBadge.style.display = 'inline-block';
                } else {
                    modalStatusBadge.style.display = 'none';
                }

                modalNoteContent.textContent = note;
                noteModal.show();
            });
        });
    });
</script>
@endsection
