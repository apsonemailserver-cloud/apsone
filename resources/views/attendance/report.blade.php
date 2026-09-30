@extends('layout.admin')

@section('title', 'Laporan Rekap Absensi Karyawan')

@section('styles')
<style>
    .employee-card {
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    .employee-card:hover {
        border-color: #cbd5e1;
    }
    .employee-card-header {
        background-color: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        cursor: pointer;
        user-select: none;
    }
    .employee-card-header:hover {
        background-color: #f1f5f9;
    }
    .employee-avatar {
        width: 44px;
        height: 44px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        font-weight: 700;
        font-size: 1.1rem;
    }
    .collapse-chevron {
        transition: transform 0.2s ease;
    }
    [aria-expanded="false"] .collapse-chevron {
        transform: rotate(-90deg);
    }
    html.aps-dark .employee-card {
        background-color: #111c31 !important;
        border-color: #24324a !important;
    }
    html.aps-dark .employee-card-header {
        background-color: #162238 !important;
        border-color: #24324a !important;
    }
    html.aps-dark .employee-card-header:hover {
        background-color: #1c2b46 !important;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Riwayat Rekap Absensi Karyawan</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Rekap presensi bulanan karyawan (presensi 1 bulan penuh per karyawan).</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">Attendance</a></li>
                    <li class="breadcrumb-item active">Rekap Bulanan</li>
                </ol>
            </nav>
        </div>

        {{-- Card Utama: Filter Toolbar --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="dt-toolbar" style="align-items: flex-end;">
                    <form action="{{ route('attendance.reports') }}" method="GET" class="d-flex flex-wrap gap-3 align-items-end flex-grow-1" style="margin:0;">
                        <div>
                            <label class="form-label fw-semibold">Periode</label>
                            <input type="month" name="month" class="form-control" value="{{ request('month') ?: date('Y-m') }}" style="min-width: 160px;">
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Station</label>
                            <select name="station_id" class="form-select" style="min-width: 180px;" {{ !$isFullAccess ? 'disabled' : '' }}>
                                <option value="">Semua Station</option>
                                @foreach ($stations as $station)
                                <option value="{{ $station->code }}" {{ request('station_id', $userStation) == $station->code ? 'selected' : '' }}>
                                    {{ $station->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Role Karyawan</label>
                            <select name="role" class="form-select" style="min-width: 180px;">
                                <option value="">Semua Role</option>
                                @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption }}" {{ request('role') == $roleOption ? 'selected' : '' }}>
                                    {{ $roleOption }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="dt-search">
                            <label class="form-label fw-semibold">Cari Karyawan</label>
                            <div class="position-relative">
                                <i class="bx bx-search search-icon" style="top: 50%; transform: translateY(-50%);"></i>
                                <input type="text" name="user_name" class="form-control" placeholder="NIP atau Nama..." value="{{ request('user_name') }}" style="padding-left: 2.25rem;">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="height: 38px;">
                            <i class="bx bx-filter-alt me-1"></i>Filter
                        </button>
                        @if (request()->hasAny(['station_id', 'role', 'user_name', 'month']))
                            <a href="{{ route('attendance.reports') }}" class="btn btn-outline-secondary" style="height: 38px;" title="Reset Filter">
                                <i class="bx bx-reset"></i>
                            </a>
                        @endif
                    </form>

                    {{-- Tombol Export Excel --}}
                    @php
                        $exportAllParams = request()->only(['station_id', 'role']);
                        $exportAllParams['month'] = request('month', date('Y-m'));
                        $exportUserParams = array_merge($exportAllParams, ['user_name' => request('user_name')]);
                    @endphp
                    @if (request()->filled('user_name'))
                        <div class="btn-group">
                            <a href="{{ route('attendance.export', $exportUserParams) }}" class="btn btn-success" style="height: 38px; display: inline-flex; align-items: center; white-space: nowrap;">
                                <i class="bx bx-download me-1"></i>Export (User Ini)
                            </a>
                            <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" style="height: 38px;">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('attendance.export', $exportAllParams) }}">
                                        <i class="bx bx-group me-2"></i>Export Semua Karyawan
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('attendance.export', $exportAllParams) }}" class="btn btn-success" style="height: 38px; display: inline-flex; align-items: center; white-space: nowrap;" title="Export Excel Semua Karyawan (Rekap Matrix Bulanan ke Kanan & Detail Presensi)">
                            <i class="bx bx-download me-1"></i>Export Excel
                        </a>
                    @endif
                </div>

                {{-- Alert Pesan --}}
                @if (!empty($message))
                <div class="alert alert-warning alert-dismissible fade show mt-3 mb-0" role="alert" style="border-radius: 0.5rem; border: 1px solid #fef3c7; background: #fffbeb; color: #92400e;">
                    <i class="bx bx-info-circle me-1"></i>{{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif
            </div>
        </div>

        {{-- Bar Kontrol & Info Rekap --}}
        @if (!empty($employeeReports) && $employeeReports->isNotEmpty())
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 px-1">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-label-primary px-3 py-2" style="font-size:0.85rem;">
                    <i class="bx bx-calendar me-1"></i> Periode: <strong>{{ \Carbon\Carbon::parse($selectedMonth ?? date('Y-m'))->translatedFormat('F Y') }}</strong>
                </span>
                <span class="badge bg-label-secondary px-3 py-2" style="font-size:0.85rem;">
                    <i class="bx bx-user me-1"></i> Total Karyawan: <strong>{{ $paginatedUsers ? $paginatedUsers->total() : $employeeReports->count() }}</strong>
                </span>
            </div>
            <div>
                <button type="button" id="toggleAllBtn" class="btn btn-sm btn-outline-secondary">
                    <i class="bx bx-chevron-up me-1"></i>Tutup Semua
                </button>
            </div>
        </div>
        @endif

        {{-- List Rekap Presensi Karyawan (1 Orang = 1 Bulan Penuh) --}}
        @forelse ($employeeReports as $empReport)
            @php
                $emp = $empReport->user;
                $summary = $empReport->summary;
                $empAttendances = $empReport->attendances;
                $collapseId = 'collapseEmp_' . preg_replace('/[^A-Za-z0-9]/', '_', $emp->id);
                $initials = strtoupper(substr($emp->fullname ?? $emp->id, 0, 2));
            @endphp

            <div class="card mb-4 employee-card shadow-sm">
                {{-- Card Header: Info Karyawan & Ringkasan Bulan Ini --}}
                <div class="employee-card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 p-3"
                     data-bs-toggle="collapse"
                     data-bs-target="#{{ $collapseId }}"
                     aria-expanded="true">
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="employee-avatar">
                            {{ $initials }}
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">{{ $emp->fullname }}</h5>
                            <div class="text-muted d-flex flex-wrap align-items-center gap-2 mt-1" style="font-size: 0.8125rem;">
                                <span><i class="bx bx-id-card me-1"></i>NIP: <strong>{{ $emp->id }}</strong></span>
                                <span>&bull;</span>
                                <span><i class="bx bx-buildings me-1"></i>Station: <strong>{{ $emp->station ?? '-' }}</strong></span>
                                <span>&bull;</span>
                                <span><i class="bx bx-briefcase me-1"></i>Role: <strong>{{ $emp->role ?? '-' }}</strong></span>
                            </div>
                        </div>
                    </div>

                    {{-- Badges Ringkasan 1 Bulan --}}
                    <div class="d-flex align-items-center flex-wrap gap-1 mt-2 mt-md-0">
                        <span class="badge bg-label-success" title="Total Hari Hadir">
                            <i class="bx bx-check me-1"></i>Hadir: {{ $summary['hadir'] }}
                        </span>
                        <span class="badge bg-label-primary" title="Tepat Waktu">
                            Tepat: {{ $summary['tepat_waktu'] }}
                        </span>
                        <span class="badge bg-label-danger" title="Terlambat">
                            <i class="bx bx-time-five me-1"></i>Telat: {{ $summary['telat'] }}
                        </span>
                        <span class="badge bg-label-info" title="Cuti Disetujui">
                            Cuti: {{ $summary['cuti'] }}
                        </span>
                        @if ($summary['koreksi'] > 0)
                        <span class="badge bg-label-warning" title="Koreksi Absen Disetujui">
                            Koreksi: {{ $summary['koreksi'] }}
                        </span>
                        @endif
                        <span class="badge bg-label-secondary" title="Hari Libur / Off">
                            Libur: {{ $summary['libur'] }}
                        </span>
                        @if ($summary['tidak_hadir'] > 0)
                        <span class="badge bg-label-dark" title="Tidak Hadir Tanpa Keterangan">
                            Alpa: {{ $summary['tidak_hadir'] }}
                        </span>
                        @endif
                        <i class="bx bx-chevron-down fs-4 text-muted ms-2 collapse-chevron"></i>
                    </div>
                </div>

                {{-- Card Body: Tabel 1 Bulan Penuh untuk Karyawan Ini --}}
                <div id="{{ $collapseId }}" class="collapse show employee-collapse">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr style="background-color: #f1f5f9;">
                                    <th style="min-width: 120px;">Tanggal</th>
                                    <th>Shift / Jadwal</th>
                                    <th>Check-In</th>
                                    <th>Check-Out</th>
                                    <th>Lokasi</th>
                                    <th>Durasi Kerja</th>
                                    <th>Status Telat</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($empAttendances as $row)
                                @php
                                    $attendance = $row->attendance ?? null;
                                    $schedule   = $row->schedule   ?? null;
                                    $leave      = $row->leave      ?? null;
                                    $correction = $row->correction ?? null;
                                    $date       = \Carbon\Carbon::parse($row->date);
                                    $today      = \Carbon\Carbon::today();

                                    $isOnLeave = $leave !== null;

                                    $checkIn = $attendance?->check_in_time
                                        ? \Carbon\Carbon::parse($attendance->check_in_time)
                                        : null;
                                    $checkOut = $attendance?->check_out_time
                                        ? \Carbon\Carbon::parse($attendance->check_out_time)
                                        : null;

                                    if ($correction && $correction->status === 'approved') {
                                        $checkIn = \Carbon\Carbon::parse($correction->proposed_check_in_time);
                                        $checkOut = \Carbon\Carbon::parse($correction->proposed_check_out_time);
                                    }

                                    $checkInClass  = '';
                                    $checkOutClass = '';

                                    if (!$isOnLeave) {
                                        if ($date->lt($today)) {
                                            if (!$checkIn || !$checkOut) {
                                                $checkInClass  = 'bg-danger text-white';
                                                $checkOutClass = 'bg-danger text-white';
                                            } elseif ($schedule && !empty($schedule->start_time)) {
                                                $schedStart = \Carbon\Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
                                                $schedEnd   = \Carbon\Carbon::parse($date->toDateString() . ' ' . $schedule->end_time);

                                                if ($checkIn->gt($schedStart)) {
                                                    $checkInClass = 'bg-danger text-white';
                                                } elseif ($checkIn->lt($schedStart)) {
                                                    $checkInClass = 'bg-success text-white';
                                                }

                                                if ($checkOut->lt($schedEnd)) {
                                                    $checkOutClass = 'bg-danger text-white';
                                                } elseif ($checkOut->gt($schedEnd)) {
                                                    $checkOutClass = 'bg-success text-white';
                                                }
                                            }
                                        }
                                    }

                                    $workDuration = $checkIn && $checkOut ? $checkIn->diff($checkOut)->format('%H:%I:%S') : '-';

                                    // Status Telat
                                    $latenessStatus = '-';
                                    $latenessBadgeClass = 'bg-label-secondary';

                                    if ($checkIn && $schedule && !empty($schedule->start_time)) {
                                        $schedStart = \Carbon\Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
                                        if ($checkIn->gt($schedStart)) {
                                            $latenessStatus = 'Telat';
                                            $latenessBadgeClass = 'bg-label-danger';
                                        } else {
                                            $latenessStatus = 'Tepat Waktu';
                                            $latenessBadgeClass = 'bg-label-success';
                                        }
                                    } elseif ($checkIn) {
                                        if ($attendance && $attendance->status === 'Terlambat') {
                                            $latenessStatus = 'Telat';
                                            $latenessBadgeClass = 'bg-label-danger';
                                        } else {
                                            $latenessStatus = 'Tepat Waktu';
                                            $latenessBadgeClass = 'bg-label-success';
                                        }
                                    } elseif (!$isOnLeave && $date->lt($today)) {
                                        if ($schedule && strtolower($schedule->shift_id ?? '') !== 'off') {
                                            $latenessStatus = 'Tidak Absen';
                                            $latenessBadgeClass = 'bg-label-danger';
                                        }
                                    }

                                    // Keterangan / Status Koreksi / Cuti
                                    $keteranganText = '-';
                                    $keteranganBadgeClass = 'bg-label-secondary';

                                    if ($isOnLeave) {
                                        $keteranganText = 'Cuti (' . ($leave->leave_type ?? 'Cuti') . ')';
                                        $keteranganBadgeClass = 'bg-label-info';
                                    } elseif ($correction) {
                                        $corrStatus = ucfirst($correction->status);
                                        $keteranganText = 'Koreksi Absen (' . $corrStatus . ')';
                                        if ($correction->status === 'approved') {
                                            $keteranganBadgeClass = 'bg-label-success';
                                        } elseif ($correction->status === 'rejected') {
                                            $keteranganBadgeClass = 'bg-label-danger';
                                        } else {
                                            $keteranganBadgeClass = 'bg-label-warning';
                                        }
                                    } elseif ($attendance) {
                                        $keteranganText = 'Hadir';
                                        $keteranganBadgeClass = 'bg-label-success';
                                    } elseif ($date->lt($today)) {
                                        if ($schedule && strtolower($schedule->shift_id ?? '') !== 'off') {
                                            $keteranganText = 'Tidak Hadir';
                                            $keteranganBadgeClass = 'bg-label-danger';
                                        } else {
                                            $keteranganText = 'Libur';
                                            $keteranganBadgeClass = 'bg-label-secondary';
                                        }
                                    }

                                    $locationText = '-';
                                    if ($checkIn) {
                                        if ($attendance) {
                                            $locationText = $attendance->station?->code ?? $emp->station ?? ($attendance->check_in_notes ?? '-');
                                        } elseif ($correction && $correction->status === 'approved') {
                                            $locationText = $correction->station?->code ?? $emp->station ?? '-';
                                        }
                                    }

                                    $shiftText = '-';
                                    if ($schedule) {
                                        $shiftDesc = $schedule->shift_description ?? ($schedule->shift?->description ?? 'Shift');
                                        $start = $schedule->start_time ? substr($schedule->start_time, 0, 5) : '';
                                        $end = $schedule->end_time ? substr($schedule->end_time, 0, 5) : '';
                                        $shiftText = $start ? "{$shiftDesc} ({$start}-{$end})" : $shiftDesc;
                                    }
                                @endphp

                                <tr class="{{ $isOnLeave ? 'table-info' : '' }}">
                                    <td class="fw-semibold">{{ $date->translatedFormat('d M Y') }}</td>
                                    <td>
                                        @if ($schedule)
                                            <span class="badge bg-label-primary">{{ $shiftText }}</span>
                                        @else
                                            <span class="badge bg-label-secondary">Libur / Off</span>
                                        @endif
                                    </td>
                                    <td class="{{ $checkInClass }}" style="border-radius: 0.25rem;">
                                        {{ $checkIn ? $checkIn->format('H:i:s') : '-' }}
                                    </td>
                                    <td class="{{ $checkOutClass }}" style="border-radius: 0.25rem;">
                                        {{ $checkOut ? $checkOut->format('H:i:s') : '-' }}
                                    </td>
                                    <td>{{ $locationText }}</td>
                                    <td>{{ $isOnLeave ? '-' : $workDuration }}</td>
                                    <td>
                                        <span class="badge {{ $latenessBadgeClass }}">
                                            {{ $latenessStatus }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $keteranganBadgeClass }}">
                                            {{ $keteranganText }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Tidak ada data presensi pada periode ini.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="empty-state">
                        <i class="bx bx-calendar-x d-block fs-1 text-muted mb-2"></i>
                        <h6 class="fw-bold mb-1">Tidak Ada Data Rekap</h6>
                        <p class="text-muted mb-0">Silakan sesuaikan filter station, periode, atau nama karyawan di atas.</p>
                    </div>
                </div>
            </div>
        @endforelse

        {{-- Pagination --}}
        @if ($paginatedUsers && $paginatedUsers->hasPages())
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4 pt-2">
            <div class="text-muted small">
                Menampilkan <strong>{{ $paginatedUsers->firstItem() }}</strong> - <strong>{{ $paginatedUsers->lastItem() }}</strong> dari <strong>{{ $paginatedUsers->total() }}</strong> karyawan
            </div>
            <div>
                {{ $paginatedUsers->links() }}
            </div>
        </div>
        @endif

        {{-- Legenda Warna Status --}}
        <div class="card mt-4 border shadow-none" style="background:#fcfcfd;">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap gap-4 align-items-center" style="font-size:0.8125rem;">
                    <div class="fw-bold text-muted">Legenda Warna:</div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="display:inline-block;width:14px;height:14px;background-color:#198754;border-radius:3px;"></span>
                        <span>Hijau: Tepat waktu / Lebih awal</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="display:inline-block;width:14px;height:14px;background-color:#dc3545;border-radius:3px;"></span>
                        <span>Merah: Terlambat / Pulang cepat / Tidak absen</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="display:inline-block;width:14px;height:14px;background-color:#0dcaf0;border-radius:3px;"></span>
                        <span>Biru: Cuti (Disetujui)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span style="display:inline-block;width:14px;height:14px;background-color:#6c757d;border-radius:3px;"></span>
                        <span>Abu-abu: Libur / Off</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleAllBtn');
    if (toggleBtn) {
        let allOpen = true;
        toggleBtn.addEventListener('click', function() {
            allOpen = !allOpen;
            const collapses = document.querySelectorAll('.employee-collapse');
            collapses.forEach(function(el) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                if (allOpen) {
                    bsCollapse.show();
                } else {
                    bsCollapse.hide();
                }
            });
            toggleBtn.innerHTML = allOpen 
                ? '<i class="bx bx-chevron-up me-1"></i>Tutup Semua' 
                : '<i class="bx bx-chevron-down me-1"></i>Buka Semua';
        });
    }
});
</script>
@endsection
