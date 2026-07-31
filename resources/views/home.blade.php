@extends('layout.admin')

@section('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </noscript>
    <link rel="stylesheet" href="{{ asset('template/assets/css/custom-home.css') }}?v={{ filemtime(public_path('template/assets/css/custom-home.css')) }}" media="print" onload="this.media='all'" />
    <noscript><link rel="stylesheet" href="{{ asset('template/assets/css/custom-home.css') }}" /></noscript>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- HEADER SECTION --}}
        <div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                @php
                    $hour = date('H');
                    $timeGreeting = $hour < 12 ? 'Pagi' : ($hour < 18 ? 'Siang' : 'Malam');
                @endphp
                <h1 class="h4 fw-bold mb-1 text-dark dashboard-title">
                    Hi {{ Auth::user()->fullname }}, Selamat {{ $timeGreeting }} 👋
                </h1>
                <p class="text-muted mb-0" style="font-size: 0.8rem;">
                    Dashboard Overview &bull;
                    <span class="dashboard-scope">
                        {{ isset($selectedStation) && $selectedStation !== 'All' ? 'Station ' . $selectedStation : 'Global' }}
                    </span>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-3 action-buttons">
                    @if (Auth::user()->role == 'Admin')
                        <form action="{{ url()->current() }}" method="GET" id="stationFilterForm" class="m-0">
                            <div class="input-group station-filter-control">
                                <span class="input-group-text border-0"><i class="fas fa-filter"></i></span>
                                <select name="station" class="form-select border-0 fw-semibold" aria-label="Filter Station"
                                    onchange="document.getElementById('stationFilterForm').submit()">
                                    <option value="All"
                                        {{ isset($selectedStation) && $selectedStation == 'All' ? 'selected' : '' }}>Semua
                                        Station (Global)</option>
                                    @if (isset($listStations))
                                        @foreach ($listStations as $st)
                                            <option value="{{ $st->code }}"
                                                {{ isset($selectedStation) && $selectedStation == $st->code ? 'selected' : '' }}>
                                                {{ $st->code }} - {{ $st->name }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </form>
                    @endif

                    <div class="attendance-action-buttons d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                        @if(isset($pendingWorkResultsCount) && $pendingWorkResultsCount > 0)
                            <a href="{{ route('work_results.index') }}" class="btn btn-label-warning shadow-sm fw-semibold d-inline-flex align-items-center py-2 px-3">
                                <i class="bx bx-loader-alt bx-spin me-1.5 fs-5 text-warning"></i>
                                <span>{{ $pendingWorkResultsCount }} Pekerjaan Masih Proses</span>
                            </a>
                        @endif

                        @if(auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES))
                            <a href="{{ route('work_results.create') }}" class="btn btn-primary-custom text-white shadow-sm">
                                <i class="bx bx-plus-circle me-1"></i> Tambah WO
                            </a>
                        @endif

                        @if ($todayAttendance)
                            @if (!$todayAttendance->check_in_time)
                                <a href="{{ route('attendance.camera', ['type' => 'in']) }}" class="btn btn-primary-custom text-white shadow-sm">
                                    <i class="bx bx-log-in me-1"></i> Absen In
                                </a>
                            @elseif ($todayAttendance->check_in_time && !$todayAttendance->check_out_time)
                                <a href="{{ route('attendance.camera', ['type' => 'out']) }}" class="btn btn-primary-custom text-white shadow-sm">
                                    <i class="bx bx-log-out me-1"></i> Absen Out
                                </a>
                            @else
                                <button class="btn btn-outline-secondary shadow-sm" disabled>
                                    <i class="bx bx-check-circle me-1"></i> Sudah Absen
                                </button>
                            @endif
                        @else
                            <a href="{{ route('attendance.camera', ['type' => 'in']) }}" class="btn btn-primary-custom text-white shadow-sm">
                                <i class="bx bx-log-in me-1"></i> Absen In
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- MONITORING STATION WIDGET (KHUSUS ADMIN) --}}
        @if (Auth::user()->role == 'Admin')
            <div class="monitoring-section">
                <div class="monitoring-heading">
                    <div class="monitoring-icon">
                        <i class="fas fa-satellite-dish fa-sm"></i>
                    </div>
                    <h2 class="h6 mb-0 fw-bold">Monitoring Station (Realtime)</h2>
                </div>

                <div class="row g-3">
                    @foreach ($allStations as $st)
                        @php
                            $count = $stationStats[$st->code] ?? 0;
                            $borderColor = $count > 0 ? 'border-left-active' : 'border-left-empty';
                            $stationStatusClass = $count > 0 ? 'station-card-active' : 'station-card-empty';
                        @endphp
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="station-card {{ $borderColor }} {{ $stationStatusClass }} h-100 d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="station-code">{{ $st->code }}</div>
                                        <div class="station-name text-truncate" title="{{ $st->name }}">{{ $st->name }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="station-count">{{ $count }}</div>
                                        <small class="station-count-label">Staff Aktif</small>
                                    </div>
                                </div>
                                @if ($count > 0)
                                    <a href="{{ route('staff.index', ['station' => $st->code]) }}"
                                        class="btn btn-sm station-detail-btn w-100 fw-semibold">
                                        <i class="fas fa-users me-1"></i> Lihat Detail
                                    </a>
                                @else
                                    <button class="btn btn-sm station-empty-btn w-100 fw-semibold" disabled>
                                        Kosong
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="{{ route('stations.create') }}"
                            class="station-card station-create-card h-100 d-flex flex-column align-items-center justify-content-center text-decoration-none">
                            <div class="station-create-icon rounded-circle d-flex align-items-center justify-content-center mb-2">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="fw-bold" style="font-size: 0.8rem;">Buka Station Baru</div>
                        </a>
                    </div>
                </div>
            </div>
        @endif
        @if (! $showManagementDashboard)
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card stat-card stat-card-primary shadow-sm">
                    <div class="card-body">
                        <div class="stat-title">Work Orders (Last 30 Days)</div>
                        <div class="stat-value">{{ $personalAssignmentsLastMonth }}</div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card stat-card-success shadow-sm">
                    <div class="card-body">
                        <div class="stat-title">Persentase Kehadiran Anda (1 Bulan Terakhir)</div>
                        <div class="stat-value" data-animate-counter="false">{{ number_format($personalAttendancePercentage, 2) }}%</div>
                        <i class="fas fa-user-check stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card stat-card-info shadow-sm">
                    <div class="card-body">
                        <div class="stat-title">Penerbangan Selesai (1 Bulan Terakhir)</div>
                        <div class="stat-value">{{ $personalCompletedFlightsLastMonth }}</div>
                        <i class="fas fa-plane-departure stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        {{-- RIWAYAT PRESENSI PERSONAL --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="modern-card">
                    <div
                        class="card-header chart-header d-flex justify-content-between align-items-center pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2 me-2 text-primary d-flex align-items-center justify-content-center"
                                style="width: 34px; height: 34px; background-color: var(--primary-soft);">
                                <i class="ti ti-calendar-user fs-4"></i>
                            </div>
                            <h2 class="h6 mb-0 fw-bold text-dark">
                                Riwayat Presensi Anda
                                <span class="text-muted fw-normal">(7 Hari Terakhir)</span>
                            </h2>
                        </div>
                        <a href="{{ route('attendance.history') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Office</th>
                                    <th>Shift</th>
                                    <th>In</th>
                                    <th>Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($personalAttendanceHistory as $attendance)
                                    @php
                                        $attendanceDate = $attendance->check_in_time
                                            ? \Carbon\Carbon::parse($attendance->check_in_time)
                                            : \Carbon\Carbon::parse($attendance->created_at);
                                        $schedule = $personalSchedules[$attendanceDate->toDateString()] ?? null;
                                    @endphp
                                    <tr>
                                        <td>{{ $attendanceDate->translatedFormat('d M Y') }}</td>
                                        <td>{{ $attendance->station?->code ?? Auth::user()->station ?? '-' }}</td>
                                        <td>
                                            @if ($schedule?->shift)
                                                {{ \Carbon\Carbon::parse($schedule->shift->start_time)->format('H:i') }}
                                                -
                                                {{ \Carbon\Carbon::parse($schedule->shift->end_time)->format('H:i') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '-' }}</td>
                                        <td>{{ $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '-' }}</td>
                                        <td>
                                            @if (! $attendance->check_in_time)
                                                <span class="badge bg-label-danger">Tidak Lengkap</span>
                                            @elseif (! $attendance->check_out_time)
                                                <span class="badge bg-label-warning">Belum Check-out</span>
                                            @else
                                                <span class="badge bg-label-success">Hadir</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="bx bx-folder-open fs-1 mb-2 opacity-50"></i>
                                            <p class="mb-0">Tidak ada riwayat presensi dalam 7 hari terakhir.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {{-- PENGERJAAN PERSONAL HARI INI / 1 BULAN TERAKHIR --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="modern-card">
                    <div
                        class="card-header chart-header d-flex justify-content-between align-items-center pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2 me-2 text-primary d-flex align-items-center justify-content-center"
                                style="width: 34px; height: 34px; background-color: var(--primary-soft);">
                                <i class="ti ti-checklist fs-4"></i>
                            </div>
                            <h2 class="h6 mb-0 fw-bold text-dark">
                                Work Orders
                                <span class="text-muted fw-normal">(1 Bulan Terakhir)</span>
                            </h2>
                        </div>
                        <a href="{{ route('work_results.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    @if(isset($assignedFlights) && $assignedFlights->isNotEmpty())
                        <div class="d-none" aria-hidden="true">
                            @foreach($assignedFlights as $af)
                                <span>{{ $af->flight_number }}</span>
                            @endforeach
                        </div>
                    @endif
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal & Station</th>
                                    <th>Kategori</th>
                                    <th>Registrasi & WO</th>
                                    <th>Ex / To Flight</th>
                                    <th>Stand & Waktu</th>
                                    <th>Foto Bukti</th>
                                    <th>Status</th>
                                    <th>Staff Terlibat</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($personalWorkResultsLastMonth) && $personalWorkResultsLastMonth->isNotEmpty())
                                    @foreach ($personalWorkResultsLastMonth as $wo)
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($wo->date)->format('d M Y') }}</div>
                                                <span class="badge bg-label-secondary mt-1">{{ $wo->station }}</span>
                                            </td>
                                            <td>
                                                @if($wo->type === 'DCI')
                                                    <span class="badge bg-label-primary px-3 py-1.5 fw-bold">DCI (INTERIOR)</span>
                                                @else
                                                    <span class="badge bg-label-success px-3 py-1.5 fw-bold">DCE (EXTERIOR)</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong class="text-dark fs-6">{{ $wo->aircraft_reg }}</strong>
                                                <div class="small text-muted">WO: {{ $wo->wo_number }}</div>
                                            </td>
                                            <td>
                                                <div class="small fw-semibold text-dark">Ex: {{ $wo->ex_flight ?: '-' }}</div>
                                                <div class="small text-muted">To: {{ $wo->to_flight ?: '-' }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-medium text-dark"><i class="bx bx-parking me-1 text-primary"></i>Stand {{ $wo->parking_stand }}</div>
                                                <div class="small text-muted"><i class="bx bx-time me-1"></i>{{ substr($wo->start_time, 0, 5) }} - {{ substr($wo->end_time, 0, 5) }} ({{ $wo->duration_minutes }} min)</div>
                                            </td>
                                            <td>
                                                @if($wo->photo_path)
                                                    <button type="button" class="btn btn-xs btn-label-primary py-1 px-2.5 rounded-pill btn-preview-photo" data-photo-url="{{ asset('storage/' . $wo->photo_path) }}" data-wo="{{ $wo->wo_number }}" title="Lihat Foto Bukti">
                                                        <i class="bx bx-image-alt me-1"></i> Lihat Foto
                                                    </button>
                                                @else
                                                    @if(auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES))
                                                        <button type="button" class="btn btn-xs btn-label-warning py-1 px-2.5 rounded-pill btn-upload-photo" data-id="{{ $wo->id }}" data-wo="{{ $wo->wo_number }}" title="Upload Foto Bukti Pekerjaan">
                                                            <i class="bx bx-upload me-1"></i> Upload Foto
                                                        </button>
                                                    @else
                                                        <span class="badge bg-label-secondary"><i class="bx bx-image me-1"></i>Belum Ada Foto</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if($wo->photo_path)
                                                    <span class="badge bg-label-success px-3 py-1.5 rounded-pill fw-semibold"><i class="bx bx-check me-1"></i>Selesai</span>
                                                @else
                                                    <span class="badge bg-label-warning px-3 py-1.5 rounded-pill fw-semibold"><i class="bx bx-loader-alt bx-spin me-1"></i>Proses</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($wo->users && $wo->users->count() > 0)
                                                    @foreach($wo->users->take(2) as $st)
                                                        <span class="badge bg-label-primary me-1 mb-1" style="font-size: 0.75rem;">{{ $st->fullname }}</span>
                                                    @endforeach
                                                    @if($wo->users->count() > 2)
                                                        <span class="badge bg-label-secondary me-1 mb-1" style="font-size: 0.75rem;">+{{ $wo->users->count() - 2 }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <a href="{{ route('work_results.show', $wo->id) }}" class="action-btn" title="Detail Pekerjaan">
                                                        <i class="bx bx-show"></i>
                                                    </a>
                                                    @if(auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES))
                                                        @if($wo->photo_path)
                                                            <a href="{{ route('work_results.export_single_pdf', $wo->id) }}" class="action-btn action-edit" title="Cetak Hardcopy WO PDF" target="_blank">
                                                                <i class="bx bx-printer"></i>
                                                            </a>
                                                        @else
                                                            <button type="button" class="action-btn action-edit opacity-50 btn-no-photo-pdf" data-wo="{{ $wo->wo_number }}" title="Belum Ada Foto (Tidak Bisa Dicetak)">
                                                                <i class="bx bx-printer"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bx bx-folder-open fs-1 mb-2 opacity-50"></i>
                                            <p class="mb-0">Tidak ada pengerjaan yang ditugaskan kepada Anda dalam 1 bulan terakhir.</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if ($showManagementDashboard)
        {{-- PANEL STATISTIK UTAMA --}}
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 mb-4">
            <div class="col">
                <div class="card stat-card stat-card-primary shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="stat-title">Total Staff GLOBAL</div>
                        <div class="stat-value">{{ $userCount ?? 0 }}</div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card stat-card-success shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="stat-title">Staff Bertugas</div>
                        <div class="stat-value">{{ $workingManpowers ?? 0 }}</div>
                        <i class="fas fa-user-check stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card stat-card-info shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="stat-title">Penerbangan Selesai</div>
                        <div class="stat-value">{{ $totalFlightPerDay ?? 0 }}</div>
                        <i class="fas fa-plane-departure stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card stat-card-warning shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="stat-title">WO Hari Ini</div>
                        <div class="stat-value">{{ $totalWoToday ?? 0 }}</div>
                        <i class="fas fa-clipboard-check stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card stat-card-purple shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="stat-title">WO Bulan Ini</div>
                        <div class="stat-value">{{ $totalWoThisMonth ?? 0 }}</div>
                        <i class="fas fa-calendar-alt stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- CHARTS ATAS --}}
        <div class="top-charts-container">
            <div class="modern-card chart-card-aircraft">
                <div class="card-header chart-header line-chart-header">
                    <div class="chart-heading-main line-chart-title-block">
                        <div class="chart-heading-title">
                            <strong>Performa Pengerjaan Pesawat</strong>
                            <span class="chart-period">(7 Hari Terakhir)</span>
                        </div>
                        <div class="line-chart-legend" aria-label="Legend Performa Pengerjaan Pesawat">
                            <span class="line-series-item"><span class="line-series-dot primary"></span>Selesai</span>
                            <span class="line-series-item"><span class="line-series-dot compare"></span>Rata-rata</span>
                        </div>
                    </div>
                    <span class="chart-period-select">
                        7 Hari
                    </span>
                </div>
                <div class="card-body">
                    <div class="chart-canvas-wrapper">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="modern-card chart-card-doughnut">
                <div class="card-header chart-header">
                    <div class="chart-heading-main">
                        <div class="chart-heading-title">
                            <strong>Distribusi Staff by Role</strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-canvas-wrapper">
                        <canvas id="doughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- CHARTS BAWAH & INFO --}}
        <div class="bottom-charts-container">
            <div class="modern-card chart-card-attendance">
                <div class="card-header chart-header attendance-chart-header">
                    <div class="chart-heading-main">
                        <div class="chart-heading-title">
                            <strong>Data Absensi Staff</strong>
                            <span class="chart-period">(7 Hari Terakhir)</span>
                        </div>
                    </div>
                    <div class="chart-legend-inline" aria-label="Legend Data Absensi Staff">
                        <span class="chart-legend-item"><span class="chart-legend-dot sick"></span>Sakit</span>
                        <span class="chart-legend-item"><span class="chart-legend-dot leave"></span>Cuti</span>
                    </div>
                </div>
                <div class="chart-insight-strip">
                    <div class="chart-insight-item">
                        <span class="chart-insight-label">
                            <span class="chart-insight-indicator sick"></span>
                            Total Sakit
                        </span>
                        <span class="chart-insight-value">{{ array_sum($sickData ?? []) }}</span>
                    </div>
                    <div class="chart-insight-item">
                        <span class="chart-insight-label">
                            <span class="chart-insight-indicator leave"></span>
                            Total Cuti
                        </span>
                        <span class="chart-insight-value">{{ array_sum($leaveData ?? []) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-canvas-wrapper">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="modern-card attendance-stat-card">
                <div class="card-header chart-header mb-1">
                    <div class="chart-heading-main">
                        <div class="chart-heading-title">
                            <strong>Statistik Kehadiran Hari Ini</strong>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    @php
                        $attendancePercentValue = min(100, max(0, (float) ($attendancePercentage ?? 0)));
                    @endphp
                    <div class="attendance-score">
                        <div class="attendance-score-top">
                            <div>
                                <div class="attendance-score-label">Persentase Kehadiran</div>
                                <div class="attendance-score-value">{{ $attendancePercentage ?? 0 }}%</div>
                            </div>
                            <div class="attendance-score-badge">
                                <i class="ti ti-chart-donut-3"></i>
                            </div>
                        </div>
                        <div class="attendance-progress" role="progressbar" aria-valuenow="{{ $attendancePercentage ?? 0 }}" aria-valuemin="0" aria-valuemax="100" aria-label="Persentase Kehadiran">
                            <span class="attendance-progress-bar" style="width: {{ $attendancePercentValue }}%;"></span>
                        </div>
                    </div>
                    <ul class="info-list">
                        <li class="info-item">
                            <div class="info-icon-wrapper icon-blue"><i class="ti ti-file-pencil"></i></div>
                            <span class="info-label">Total Staff Kontrak</span>
                            <span class="info-value">{{ $totalContractStaff ?? 0 }}</span>
                        </li>
                        <li class="info-item">
                            <div class="info-icon-wrapper icon-purple"><i class="ti ti-id-badge-2"></i></div>
                            <span class="info-label">Total Staff PAS Aktif</span>
                            <span class="info-value">{{ $totalPasStaff ?? 0 }}</span>
                        </li>
                        <li class="info-item">
                            <div class="info-icon-wrapper icon-green"><i class="ti ti-user-check"></i></div>
                            <span class="info-label">Kehadiran Hari Ini</span>
                            <span class="info-value">{{ $presentToday ?? 0 }}</span>
                        </li>
                        <li class="info-item">
                            <div class="info-icon-wrapper icon-red"><i class="ti ti-user-x"></i></div>
                            <span class="info-label">Tidak Hadir</span>
                            <span class="info-value">{{ $totalAbsent ?? 0 }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endif


        {{-- TABEL PENERBANGAN (DITAROH PALING BAWAH) --}}
        <div class="row mt-4">
            <div class="col-12">
                <div class="modern-card">
                    <div
                        class="card-header chart-header d-flex justify-content-between align-items-center pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="rounded-3 p-2 me-2 text-primary d-flex align-items-center justify-content-center"
                                style="width: 34px; height: 34px; background-color: var(--primary-soft);">
                                <i class="ti ti-plane-arrival fs-4"></i>
                            </div>
                            <h2 class="h6 mb-0 fw-bold text-dark">
                                @if ($showManagementDashboard)
                                    Data Penerbangan Hari Ini
                                @else
                                    Data Penerbangan
                                    <span class="text-muted fw-normal">(7 Hari Terakhir)</span>
                                @endif
                            </h2>
                        </div>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>AIRLINE</th>
                                    <th>FLIGHT NO.</th>
                                    <th>REGISTRASI</th>
                                    <th>TIPE</th>
                                    <th>KEDATANGAN</th>
                                    <th>HITUNG MUNDUR</th>
                                    <th>DIBUAT PADA</th>
                                    <th class="text-center">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($flights as $flight)
                                    <tr class="clickable-row" data-target="#viewFlightModal{{ $flight->id }}">
                                        <td class="fw-bold text-primary">{{ $flight->airline }}</td>
                                        <td><span class="badge bg-label-dark">{{ $flight->flight_number }}</span></td>
                                        <td>{{ $flight->registasi }}</td>
                                        <td>{{ $flight->type }}</td>
                                        <td><i class="bx bx-time-five text-muted me-1"></i>{{ $flight->arrival }}</td>
                                        <td><span class="countdown shadow-sm no-click"
                                                data-time="{{ $flight->time_count }}"></span></td>
                                        <td class="text-muted">{{ $flight->created_at->format('d M Y, H:i') }}</td>
                                        <td class="no-click text-center">
                                            @if ($flight->status)
                                                <span class="badge bg-label-success px-3 py-1.5 rounded-pill fw-semibold">
                                                    <i class="bx bx-check me-1"></i>Selesai
                                                </span>
                                            @else
                                                @if (in_array(Auth::user()->role, ['Ass Leader', 'Leader']))
                                                    <form action="{{ route('flights.update', $flight->id) }}"
                                                        method="POST" style="display:inline;">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                            class="btn btn-warning btn-xs rounded-pill px-3 shadow-sm" title="Klik untuk selesaikan penerbangan">
                                                            <i class="bx bx-loader-alt bx-spin me-1"></i> Selesaikan
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="badge bg-label-warning px-3 py-1.5 rounded-pill fw-semibold">
                                                        <i class="bx bx-loader-alt bx-spin me-1"></i>Proses
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    @include('modal.view_flight', ['flight' => $flight])
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="bx bx-folder-open fs-1 mb-2 opacity-50"></i>
                                            <p class="mb-0">
                                                @if ($showManagementDashboard)
                                                    Tidak ada data penerbangan untuk hari ini.
                                                @else
                                                    Tidak ada data penerbangan dalam 7 hari terakhir.
                                                @endif
                                            </p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('modal.add_flight')
    @include('modal.flight')
@endsection

@section('scripts')
    @if ($showManagementDashboard)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if ($showManagementDashboard)
            // DATA YANG DIKIRIM DARI CONTROLLER AKAN OTOMATIS BERUBAH SESUAI FILTER
            const lineChartLabels = @json($lineChartLabels ?? []);
            const lineChartData = @json($lineChartData ?? []);
            const doughnutChartLabels = @json($doughnutChartLabels ?? []);
            const doughnutChartData = @json($doughnutChartData ?? []);
            const barChartLabels = @json($barChartLabels ?? []);
            const sickData = @json($sickData ?? []);
            const leaveData = @json($leaveData ?? []);
            const lineAverage = lineChartData.length
                ? lineChartData.reduce((sum, value) => sum + Number(value || 0), 0) / lineChartData.length
                : 0;
            const lineComparisonData = lineChartData.map(() => Number(lineAverage.toFixed(1)));

            // Global Chart Defaults
            Chart.defaults.font.family = "'Public Sans', sans-serif";
            Chart.defaults.color = '#64748B';

            const dashboardPalette = {
                primary: '#2F80ED',
                primaryDark: '#2368C8',
                sky: '#38BDF8',
                teal: '#10B981',
                amber: '#F59E0B',
                rose: '#EF4444',
                purple: '#8B5CF6',
                slate: '#64748B',
                text: '#1F2937',
                muted: '#64748B',
                grid: 'rgba(148, 163, 184, 0.16)'
            };

            const dashboardCharts = [];
            const getDashboardChartTheme = () => {
                const dark = document.documentElement.classList.contains('aps-dark');

                return {
                    text: dark ? '#e6edf7' : '#1F2937',
                    muted: dark ? '#9fb0c8' : '#64748B',
                    tick: dark ? '#8fa1b8' : '#94A3B8',
                    grid: dark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(148, 163, 184, 0.06)',
                    card: dark ? '#111c31' : '#ffffff'
                };
            };
            const syncDashboardPalette = () => {
                const theme = getDashboardChartTheme();
                dashboardPalette.text = theme.text;
                dashboardPalette.muted = theme.muted;
                dashboardPalette.grid = theme.grid;
                Chart.defaults.color = theme.muted;
                return theme;
            };
            const applyDashboardChartTheme = (chart) => {
                if (!chart) return;

                const theme = syncDashboardPalette();
                const scales = chart.options.scales || {};

                Object.values(scales).forEach((scale) => {
                    if (scale.ticks) {
                        scale.ticks.color = theme.tick;
                    }
                    if (scale.grid && scale.grid.display !== false) {
                        scale.grid.color = theme.grid;
                    }
                });

                if (chart.options.plugins?.legend?.labels) {
                    chart.options.plugins.legend.labels.color = theme.muted;
                }

                if (chart.config.type === 'doughnut') {
                    chart.data.datasets.forEach((dataset) => {
                        dataset.borderColor = theme.card;
                    });
                }

                chart.update('none');
            };
            const registerDashboardChart = (chart) => {
                dashboardCharts.push(chart);
                applyDashboardChartTheme(chart);
                return chart;
            };
            syncDashboardPalette();

            const tooltipBase = {
                backgroundColor: 'rgba(15, 23, 42, 0.92)',
                padding: 12,
                cornerRadius: 10,
                titleFont: {
                    size: 12,
                    weight: '600'
                },
                bodyFont: {
                    size: 12
                },
                borderColor: 'rgba(255, 255, 255, 0.08)',
                borderWidth: 1
            };

            const createVerticalGradient = (canvas, stops) => {
                const gradient = canvas.getContext('2d').createLinearGradient(0, 0, 0, 320);
                stops.forEach(([offset, color]) => gradient.addColorStop(offset, color));
                return gradient;
            };

            const barGradient = (baseColor, lightColor) => (context) => {
                const {
                    chart
                } = context;
                const {
                    ctx,
                    chartArea
                } = chart;
                if (!chartArea) {
                    return baseColor;
                }

                const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                gradient.addColorStop(0, baseColor);
                gradient.addColorStop(1, lightColor);
                return gradient;
            };

            const stackedBarRadius = (datasetIndex) => (context) => {
                const radius = 6;
                const index = context.dataIndex;
                const sick = Number(sickData[index] || 0);
                const leave = Number(leaveData[index] || 0);

                if (datasetIndex === 0) {
                    return {
                        topLeft: (sick > 0 && leave === 0) ? radius : 0,
                        topRight: (sick > 0 && leave === 0) ? radius : 0,
                        bottomLeft: sick > 0 ? radius : 0,
                        bottomRight: sick > 0 ? radius : 0
                    };
                } else {
                    return {
                        topLeft: leave > 0 ? radius : 0,
                        topRight: leave > 0 ? radius : 0,
                        bottomLeft: (leave > 0 && sick === 0) ? radius : 0,
                        bottomRight: (leave > 0 && sick === 0) ? radius : 0
                    };
                }
            };

            const centerDoughnutText = {
                id: 'centerDoughnutText',
                beforeDraw(chart) {
                    const {
                        ctx,
                        chartArea
                    } = chart;
                    if (!chartArea) return;

                    const total = chart.data.datasets[0].data.reduce((sum, value) => sum + Number(value || 0), 0);
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = dashboardPalette.text;
                    ctx.font = "700 24px 'Public Sans', sans-serif";
                    ctx.fillText(total, centerX, centerY - 6);
                    ctx.fillStyle = dashboardPalette.muted;
                    ctx.font = "500 11px 'Public Sans', sans-serif";
                    ctx.fillText('Total Staff', centerX, centerY + 16);
                    ctx.restore();
                }
            };

            const lineGlow = {
                id: 'lineGlow',
                beforeDatasetDraw(chart, args) {
                    if (chart.config.type !== 'line') return;
                    const {
                        ctx
                    } = chart;
                    if (args.index === 0) {
                        ctx.save();
                        ctx.shadowColor = 'rgba(47, 128, 237, 0.3)';
                        ctx.shadowBlur = 18;
                        ctx.shadowOffsetY = 10;
                    }
                },
                afterDatasetDraw(chart, args) {
                    if (chart.config.type !== 'line') return;
                    if (args.index === 0) {
                        chart.ctx.restore();
                    }
                }
            };

            const verticalHoverLine = {
                id: 'verticalHoverLine',
                afterDraw(chart) {
                    if (chart.config.type !== 'line') return;
                    const active = chart.tooltip && chart.tooltip.getActiveElements();
                    if (!active || !active.length) return;

                    const {
                        ctx,
                        chartArea
                    } = chart;
                    const x = active[0].element.x;

                    ctx.save();
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(47, 128, 237, 0)');
                    gradient.addColorStop(0.35, 'rgba(47, 128, 237, 0.16)');
                    gradient.addColorStop(1, 'rgba(47, 128, 237, 0)');
                    ctx.strokeStyle = gradient;
                    ctx.lineWidth = 26;
                    ctx.beginPath();
                    ctx.moveTo(x, chartArea.top + 8);
                    ctx.lineTo(x, chartArea.bottom);
                    ctx.stroke();

                    ctx.strokeStyle = 'rgba(47, 128, 237, 0.28)';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(x, chartArea.top + 8);
                    ctx.lineTo(x, chartArea.bottom);
                    ctx.stroke();
                    ctx.restore();
                }
            };

            const ctxLine = document.getElementById('lineChart');
            if (ctxLine) {
                const lineGradient = createVerticalGradient(ctxLine, [
                    [0, 'rgba(47, 128, 237, 0.28)'],
                    [0.55, 'rgba(47, 128, 237, 0.10)'],
                    [1, 'rgba(47, 128, 237, 0)']
                ]);

                registerDashboardChart(new Chart(ctxLine, {
                    type: 'line',
                    data: {
                        labels: lineChartLabels,
                        datasets: [{
                            label: 'Jumlah Penerbangan',
                            data: lineChartData,
                            borderColor: (context) => {
                                const {
                                    chart
                                } = context;
                                const {
                                    ctx,
                                    chartArea
                                } = chart;
                                if (!chartArea) return dashboardPalette.primary;
                                const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                                gradient.addColorStop(0, '#38BDF8');
                                gradient.addColorStop(0.45, dashboardPalette.primary);
                                gradient.addColorStop(1, dashboardPalette.primaryDark);
                                return gradient;
                            },
                            backgroundColor: lineGradient,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            borderCapStyle: 'round',
                            borderJoinStyle: 'round',
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: dashboardPalette.primary,
                            pointBorderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: dashboardPalette.primary,
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3
                        }, {
                            label: 'Rata-rata',
                            data: lineComparisonData,
                            borderColor: 'rgba(148, 163, 184, 0.6)',
                            backgroundColor: 'transparent',
                            fill: false,
                            tension: 0.4,
                            borderWidth: 1.5,
                            borderDash: [6, 6],
                            pointRadius: 0,
                            pointHoverRadius: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: Math.max(...lineChartData, 0) + 2,
                                ticks: {
                                    precision: 0,
                                    padding: 10,
                                    color: '#94A3B8',
                                    font: {
                                        size: 11,
                                        weight: '500'
                                    }
                                },
                                border: {
                                    display: false
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.06)'
                                }
                            },
                            x: {
                                border: {
                                    display: false
                                },
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    padding: 8,
                                    color: '#94A3B8',
                                    font: {
                                        size: 11,
                                        weight: '500'
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                ...tooltipBase,
                                displayColors: true,
                                usePointStyle: true,
                                callbacks: {
                                    title: function(items) {
                                        return items[0]?.label || '';
                                    },
                                    label: function(context) {
                                        const suffix = context.dataset.label === 'Rata-rata' ? 'rata-rata' : 'selesai';
                                        return `${context.dataset.label}: ${context.parsed.y} ${suffix}`;
                                    }
                                },
                                filter: function(context) {
                                    return context.dataset.label !== 'Rata-rata' || Number(context.parsed.y || 0) > 0;
                                }
                            }
                        }
                    },
                    plugins: [verticalHoverLine, lineGlow]
                }));
            }

            const ctxDoughnut = document.getElementById('doughnutChart');
            if (ctxDoughnut) {
                registerDashboardChart(new Chart(ctxDoughnut, {
                    type: 'doughnut',
                    data: {
                        labels: doughnutChartLabels,
                        datasets: [{
                            data: doughnutChartData,
                            backgroundColor: [
                                dashboardPalette.primary,
                                dashboardPalette.sky,
                                dashboardPalette.teal,
                                dashboardPalette.amber,
                                dashboardPalette.purple,
                                dashboardPalette.rose,
                                '#14B8A6',
                                '#6366F1',
                                dashboardPalette.slate
                            ],
                            borderColor: '#ffffff',
                            borderWidth: 4,
                            borderRadius: 8,
                            spacing: 2,
                            hoverOffset: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 14,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    color: dashboardPalette.muted,
                                    font: {
                                        size: 10,
                                        weight: '500'
                                    }
                                }
                            },
                            tooltip: {
                                ...tooltipBase
                            }
                        },
                        layout: {
                            padding: 10
                        },
                        cutout: '72%'
                    },
                    plugins: [centerDoughnutText]
                }));
            }

            const ctxBar = document.getElementById('barChart');
            if (ctxBar) {
                registerDashboardChart(new Chart(ctxBar, {
                    type: 'bar',
                    data: {
                        labels: barChartLabels,
                        datasets: [{
                            label: 'Sakit',
                            data: sickData,
                            backgroundColor: 'rgba(245, 158, 11, 0.85)',
                            hoverBackgroundColor: 'rgba(245, 158, 11, 1)',
                            stack: 'Absen',
                            borderRadius: stackedBarRadius(0),
                            borderSkipped: false,
                            barThickness: 26,
                            maxBarThickness: 30,
                            categoryPercentage: 0.6,
                            borderWidth: 0
                        }, {
                            label: 'Cuti',
                            data: leaveData,
                            backgroundColor: 'rgba(239, 68, 68, 0.85)',
                            hoverBackgroundColor: 'rgba(239, 68, 68, 1)',
                            stack: 'Absen',
                            borderRadius: stackedBarRadius(1),
                            borderSkipped: false,
                            barThickness: 26,
                            maxBarThickness: 30,
                            categoryPercentage: 0.6,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            x: {
                                stacked: true,
                                border: {
                                    display: false
                                },
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    padding: 8,
                                    color: '#94A3B8',
                                    font: {
                                        size: 11,
                                        weight: '500'
                                    }
                                }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    padding: 10,
                                    color: '#94A3B8',
                                    font: {
                                        size: 11,
                                        weight: '500'
                                    }
                                },
                                border: {
                                    display: false
                                },
                                grid: {
                                    color: 'rgba(148, 163, 184, 0.06)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                ...tooltipBase,
                                callbacks: {
                                    footer: function(items) {
                                        const total = items.reduce((sum, item) => sum + Number(item.parsed.y || 0), 0);
                                        return `Total: ${total}`;
                                    }
                                }
                            }
                        }
                    }
                }));
            }

            window.addEventListener('aps:theme-changed', function() {
                dashboardCharts.forEach(applyDashboardChartTheme);
            });
            @endif

            document.querySelectorAll('.countdown').forEach(function(el) {
                let timeData = el.getAttribute('data-time');
                if (!timeData) return;
                timeData = timeData.trim();

                let targetDate;
                
                try {
                    if (timeData.length <= 8) {
                        // Format: HH:mm:ss
                        const parts = timeData.split(':');
                        targetDate = new Date();
                        targetDate.setHours(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2] || 0), 0);
                    } else {
                        // Format: YYYY-MM-DD HH:mm:ss atau YYYY-MM-DDTHH:mm:ss
                        const dateTimeParts = timeData.split(/[ T]/);
                        const dateParts = dateTimeParts[0].split(/[-/]/);
                        const timeParts = dateTimeParts[1].split(':');
                        
                        // monthIndex di JS dimulai dari 0
                        targetDate = new Date(
                            parseInt(dateParts[0]),
                            parseInt(dateParts[1]) - 1,
                            parseInt(dateParts[2]),
                            parseInt(timeParts[0]),
                            parseInt(timeParts[1]),
                            parseInt(timeParts[2] || 0)
                        );
                    }
                } catch (e) {
                    console.error('Failed to parse date:', timeData);
                    return;
                }

                const countDownDate = targetDate.getTime();
                if (isNaN(countDownDate)) return;

                const updateTimer = function() {
                    const now = new Date().getTime();
                    const distance = countDownDate - now;
                    
                    if (distance >= 0) {
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        let timeStr = '';
                        if (days > 0) timeStr += `${days}h `;
                        
                        const h = String(hours).padStart(2, '0');
                        const m = String(minutes).padStart(2, '0');
                        const s = String(seconds).padStart(2, '0');
                        
                        timeStr += `${h}j ${m}m ${s}d`;
                        el.innerHTML = timeStr;
                        return true;
                    } else {
                        el.innerHTML =
                            "<span class='text-danger fw-bold'><i class='bx bx-error-circle me-1'></i>WAKTU HABIS</span>";
                        el.style.background = 'transparent';
                        el.style.padding = '0';
                        return false;
                    }
                };

                if (updateTimer()) {
                    const interval = setInterval(function() {
                        if (!updateTimer()) clearInterval(interval);
                    }, 1000);
                }
            });

            document.querySelectorAll('.clickable-row').forEach(row => {
                row.addEventListener('click', (e) => {
                    if (!e.target.closest('.no-click') && !e.target.closest('button') && !e.target
                        .closest('a') && !e.target.closest('input')) {
                        const modalId = row.getAttribute('data-target');
                        if (modalId) {
                            const modalElement = document.querySelector(modalId);
                            if (modalElement) {
                                const modal = new bootstrap.Modal(modalElement);
                                modal.show();
                            }
                        }
                    }
                });
            });

            // Animasi Counter 0.6 detik
            function animateValue(obj, start, end, duration) {
                let startTimestamp = null;
                const step = (timestamp) => {
                    if (!startTimestamp) startTimestamp = timestamp;
                    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                    obj.innerHTML = Math.floor(progress * (end - start) + start);
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    } else {
                        obj.innerHTML = end; // Ensure exact final value
                    }
                };
                window.requestAnimationFrame(step);
            }

            document.querySelectorAll('.station-count, .stat-value:not([data-animate-counter="false"])').forEach(el => {
                const targetText = el.innerText.trim();
                const targetValue = parseInt(targetText.replace(/\D/g, ''), 10);
                
                if (!isNaN(targetValue) && targetValue > 0) {
                    el.innerText = '0';
                    animateValue(el, 0, targetValue, 600); // 600ms = 0.6 seconds
                }
            });

            // Photo Preview Popup
            $(document).on('click', '.btn-preview-photo', function(e) {
                e.preventDefault();
                const photoUrl = $(this).data('photo-url');
                const woNumber = $(this).data('wo');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Foto Bukti Pekerjaan',
                        html: '<div class="text-muted mb-2 font-monospace">WO: <strong>' + woNumber + '</strong></div><img src="' + photoUrl + '" class="img-fluid rounded border shadow-sm" style="max-height: 380px; object-fit: contain;">',
                        showCloseButton: true,
                        confirmButtonText: 'Tutup',
                        confirmButtonColor: '#2f80ed'
                    });
                } else {
                    window.open(photoUrl, '_blank');
                }
            });

            // Trigger Upload Photo Modal
            $(document).on('click', '.btn-upload-photo', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const wo = $(this).data('wo');
                $('#uploadPhotoModalTitle').text('Upload Foto Bukti WO: ' + wo);
                $('#uploadPhotoForm').attr('action', '/work-results/' + id + '/upload-photo');
                const modal = new bootstrap.Modal(document.getElementById('uploadPhotoModal'));
                modal.show();
            });

            // Block Print PDF when no photo
            $(document).on('click', '.btn-no-photo-pdf', function(e) {
                e.preventDefault();
                const wo = $(this).data('wo');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        iconColor: '#f59e0b',
                        title: 'Foto Bukti Belum Ada',
                        html: 'WO <strong>' + wo + '</strong> belum memiliki foto bukti pekerjaan.<br>Silakan unggah foto bukti terlebih dahulu agar dapat mencetak Laporan PDF.',
                        confirmButtonText: 'Mengerti',
                        confirmButtonColor: '#2f80ed'
                    });
                }
            });
        });
    </script>

    <!-- Modal Upload Foto Bukti Pekerjaan -->
    <div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="uploadPhotoModalTitle">Upload Foto Bukti Pekerjaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="uploadPhotoForm" action="" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-primary d-flex align-items-center py-2 px-3 mb-3">
                            <i class="bx bx-info-circle me-2 fs-5"></i>
                            <div class="small">Laporan PDF baru dapat dicetak setelah foto bukti pekerjaan diunggah.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih File Foto Bukti <span class="text-danger">*</span></label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/jpg" required>
                            <small class="text-muted d-block mt-1">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-upload me-1"></i> Simpan & Unggah Foto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
