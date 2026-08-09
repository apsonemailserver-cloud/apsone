@extends('layout.admin')

@section('styles')
    <link rel="stylesheet" href="{{ asset('template/assets/css/custom-home.min.css') }}?v={{ filemtime(public_path('template/assets/css/custom-home.min.css')) }}" />
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
                            <a href="{{ route('assignments.index') }}" class="btn btn-label-warning shadow-sm fw-semibold d-inline-flex align-items-center py-2 px-3">
                                <i class="bx bx-loader-alt bx-spin me-1.5 fs-5 text-warning"></i>
                                <span>{{ $pendingWorkResultsCount }} Pekerjaan Masih Proses</span>
                            </a>
                        @endif

                        @if(auth()->user()->hasRole(\App\Models\Assignment::LEADER_ROLES) || auth()->user()->canAccess('assignment', 'create'))
                            <a href="{{ route('assignments.create') }}" class="btn btn-primary-custom text-white shadow-sm">
                                <i class="bx bx-plus-circle me-1"></i> Tambah Assignment
                            </a>
                        @endif

                        @if (isset($onLeaveToday) && $onLeaveToday)
                            <button class="btn btn-outline-info shadow-sm" disabled>
                                <i class="bx bx-calendar-event me-1"></i> Sedang Cuti
                            </button>
                        @elseif ($todayAttendance)
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

        {{-- MONITORING STATION WIDGET (ADMIN & HOAS) --}}
        @if (Auth::user()->hasRole(['Admin', 'Head Of Airport Service']))
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
                            <div class="station-card station-modal-trigger {{ $borderColor }} {{ $stationStatusClass }} h-100 d-flex flex-column justify-content-between"
                                style="cursor: pointer;"
                                data-station-code="{{ $st->code }}"
                                data-station-name="{{ addslashes($st->name) }}"
                                onclick="window.openFlightScheduleModal('{{ $st->code }}', '{{ addslashes($st->name) }}')"
                                title="Klik untuk lihat Live Flight Schedule {{ $st->code }}">
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
                                        class="btn btn-sm station-detail-btn w-100 fw-semibold"
                                        onclick="event.stopPropagation();"
                                        title="Lihat Detail Staff Aktif">
                                        <i class="fas fa-users me-1"></i> Lihat Detail
                                    </a>
                                @else
                                    <button class="btn btn-sm station-empty-btn w-100 fw-semibold"
                                        onclick="event.stopPropagation();" disabled>
                                        Kosong
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if (Auth::user()->hasRole('Admin'))
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="{{ route('stations.create') }}"
                            class="station-card station-create-card h-100 d-flex flex-column align-items-center justify-content-center text-decoration-none">
                            <div class="station-create-icon rounded-circle d-flex align-items-center justify-content-center mb-2">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="fw-bold" style="font-size: 0.8rem;">Buka Station Baru</div>
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        @endif
        @if (! $showManagementDashboard)
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card stat-card stat-card-primary shadow-sm">
                    <div class="card-body">
                        <div class="stat-title">Assignments (Last 30 Days)</div>
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
                                Assignments
                                <span class="text-muted fw-normal">(1 Bulan Terakhir)</span>
                            </h2>
                        </div>
                        <a href="{{ route('assignments.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
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
                                    <th width="4%">#</th>
                                    <th>TANGGAL</th>
                                    <th>STATION</th>
                                    <th>CATEGORY</th>
                                    <th>REGISTRASI</th>
                                    <th>NO. WO</th>
                                    <th>EX FLIGHT</th>
                                    <th>TO FLIGHT</th>
                                    <th>STAND</th>
                                    <th>WAKTU KERJA</th>
                                    <th>EVIDENCE PHOTO</th>
                                    <th>STATUS</th>
                                    <th>LEADER</th>
                                    <th>STAFF ON DUTY</th>
                                    <th class="text-center" width="10%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($personalWorkResultsLastMonth) && $personalWorkResultsLastMonth->isNotEmpty())
                                    @foreach ($personalWorkResultsLastMonth as $index => $wo)
                                        <tr>
                                            <td class="fw-semibold text-secondary">{{ $loop->iteration }}</td>
                                            <td><strong>{{ \Carbon\Carbon::parse($wo->date)->format('d M Y') }}</strong></td>
                                            <td><span class="badge bg-label-secondary font-monospace">{{ $wo->station }}</span></td>
                                            <td>
                                                @if($wo->type === 'DCI')
                                                    <span class="badge bg-label-primary px-3 py-1.5 font-monospace fw-bold">DCI (INTERIOR)</span>
                                                @elseif($wo->type === 'DCE')
                                                    <span class="badge bg-label-success px-3 py-1.5 font-monospace fw-bold">DCE (EXTERIOR)</span>
                                                @elseif($wo->type === 'PDI')
                                                    <span class="badge bg-label-info px-3 py-1.5 font-monospace fw-bold">PDI</span>
                                                @elseif($wo->type === 'Transit')
                                                    <span class="badge bg-label-warning px-3 py-1.5 font-monospace fw-bold">TRANSIT</span>
                                                @elseif($wo->type === 'RON')
                                                    <span class="badge bg-label-dark px-3 py-1.5 font-monospace fw-bold">RON</span>
                                                @else
                                                    <span class="badge bg-label-secondary px-3 py-1.5 font-monospace fw-bold">{{ strtoupper($wo->type ?? '-') }}</span>
                                                @endif
                                            </td>
                                            <td><strong class="text-dark fs-6">{{ $wo->aircraft_reg }}</strong></td>
                                            <td><code class="font-monospace text-muted">WO: {{ $wo->wo_number }}</code></td>
                                            <td><span class="small fw-semibold text-dark">{{ $wo->ex_flight ?: '-' }}</span></td>
                                            <td><span class="small text-muted">{{ $wo->to_flight ?: '-' }}</span></td>
                                            <td><div class="fw-medium text-dark"><i class="bx bx-parking me-1 text-primary"></i>Stand {{ $wo->parking_stand }}</div></td>
                                            <td><span class="small text-muted"><i class="bx bx-time me-1"></i>{{ substr($wo->start_time, 0, 5) }} - {{ substr($wo->end_time, 0, 5) }} ({{ $wo->duration_minutes }} min)</span></td>
                                            <td>
                                                @if($wo->photo_path)
                                                    <button type="button" class="btn btn-xs btn-label-primary py-1 px-2.5 rounded-pill btn-preview-photo" data-photo-url="{{ asset('storage/' . $wo->photo_path) }}" data-wo="{{ $wo->wo_number }}" title="Lihat Foto Bukti">
                                                        <i class="bx bx-image-alt me-1"></i> Lihat Foto
                                                    </button>
                                                @else
                                                    @if(auth()->user()->hasRole(\App\Models\Assignment::LEADER_ROLES))
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
                                            <td><div class="fw-semibold text-dark">{{ $wo->submittedBy ? $wo->submittedBy->fullname : '-' }}</div></td>
                                            <td>
                                                @if($wo->users && $wo->users->count() > 0)
                                                    @foreach($wo->users as $st)
                                                        <span class="badge bg-label-primary me-1 mb-1 font-monospace" style="font-size: 0.75rem;">{{ $st->fullname }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-1">
                                                    <x-action-button action="view" :href="route('assignments.show', $wo->id)" title="Detail Pekerjaan" />
                                                    @if(auth()->user()->hasRole(\App\Models\Assignment::LEADER_ROLES))
                                                        @if($wo->photo_path)
                                                            <x-action-button action="edit" icon="ti ti-printer" :href="route('assignments.export_single_pdf', $wo->id)" title="Cetak Hardcopy WO PDF" target="_blank" />
                                                        @else
                                                            <x-action-button type="button" action="edit" icon="ti ti-printer" class="opacity-50 btn-no-photo-pdf" :data-wo="$wo->wo_number" title="Belum Ada Foto (Tidak Bisa Dicetak)" />
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="15" class="text-center py-5 text-muted">
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
                        <div class="stat-title">Assignment Hari Ini</div>
                        <div class="stat-value">{{ $totalWoToday ?? 0 }}</div>
                        <i class="fas fa-clipboard-check stat-icon"></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card stat-card stat-card-purple shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="stat-title">Assignment Bulan Ini</div>
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


        {{-- TABEL PENERBANGAN (DITAROH PALING BAWAH - KHUSUS MANAGEMENT) --}}
        @if ($showManagementDashboard)
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
                                Data Penerbangan Hari Ini
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
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($flights as $flight)
                                    <tr class="clickable-row" data-target="#viewFlightModal{{ $flight->id }}">
                                        <td class="fw-bold text-primary">{{ $flight->airline ?? ($flight->station ?? '-') }}</td>
                                        <td><span class="badge bg-label-dark">{{ $flight->flight_number ?? ($flight->ex_flight . ($flight->to_flight ? ' / ' . $flight->to_flight : '')) }}</span></td>
                                        <td>{{ $flight->registasi ?? ($flight->aircraft_reg ?? '-') }}</td>
                                        <td>{{ $flight->type ?? '-' }}</td>
                                        <td><i class="bx bx-time-five text-muted me-1"></i>{{ $flight->arrival ?? ($flight->start_time ?? '-') }}</td>
                                        @php
                                            $targetTime = $flight->time_count
                                                ?: ($flight->date && ($flight->end_time || $flight->start_time)
                                                    ? $flight->date . ' ' . ($flight->end_time ?: $flight->start_time)
                                                    : ($flight->arrival ? ($flight->date ? $flight->date . ' ' . $flight->arrival : $flight->arrival) : null));
                                            $isFinished = !empty($flight->status) || !empty($flight->photo_path);
                                        @endphp
                                        <td>
                                            @if($isFinished)
                                                <span class="badge bg-label-success px-2 py-1"><i class="bx bx-check me-1"></i>Selesai</span>
                                            @elseif($targetTime)
                                                <span class="countdown shadow-sm no-click" data-time="{{ $targetTime }}"></span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $flight->created_at ? $flight->created_at->format('d M Y, H:i') : '-' }}</td>
                                    </tr>
                                    @include('modal.view_flight', ['flight' => $flight])
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bx bx-folder-open fs-1 mb-2 opacity-50"></i>
                                            <p class="mb-0">
                                                Tidak ada data penerbangan untuk hari ini.
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
        @endif
    </div>

    @include('modal.add_flight')
    @include('modal.flight')

    <!-- Flight Schedule Modal (FIDS Airport Board) -->
    <style>
        /* FIDS Modal — Light / Dark mode */
        #flightScheduleModal .fids-header {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            border-bottom: 2px solid rgba(59,130,246,.6);
        }
        #flightScheduleModal .fids-controls {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            transition: background .2s;
        }
        #flightScheduleModal .fids-table thead th {
            background: #0F172A;
            color: #94A3B8;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .6px;
            white-space: nowrap;
        }
        #flightScheduleModal .fids-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background .15s;
        }
        #flightScheduleModal .fids-table tbody tr:hover {
            background: #f0f7ff;
        }
        #flightScheduleModal .fids-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        /* Dark mode (.aps-dark, html.aps-dark, .dark-style, [data-bs-theme=dark]) */
        .aps-dark #flightScheduleModal .fids-controls,
        .aps-dark #flightScheduleModal .fids-footer,
        .dark-style #flightScheduleModal .fids-controls,
        .dark-style #flightScheduleModal .fids-footer,
        html[data-bs-theme="dark"] #flightScheduleModal .fids-controls,
        html[data-bs-theme="dark"] #flightScheduleModal .fids-footer {
            background: #1e293b !important;
            border-color: #334155 !important;
        }
        .aps-dark #flightScheduleModal .fids-table tbody tr,
        .dark-style #flightScheduleModal .fids-table tbody tr,
        html[data-bs-theme="dark"] #flightScheduleModal .fids-table tbody tr {
            border-color: #334155 !important;
        }
        .aps-dark #flightScheduleModal .fids-table tbody tr:hover,
        .dark-style #flightScheduleModal .fids-table tbody tr:hover,
        html[data-bs-theme="dark"] #flightScheduleModal .fids-table tbody tr:hover {
            background: #1e3a5f !important;
        }
        .aps-dark #flightScheduleModal .modal-content,
        .dark-style #flightScheduleModal .modal-content,
        html[data-bs-theme="dark"] #flightScheduleModal .modal-content {
            background: #0f172a !important;
        }
        .aps-dark #flightScheduleModal .form-control,
        .dark-style #flightScheduleModal .form-control,
        html[data-bs-theme="dark"] #flightScheduleModal .form-control {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #e2e8f0 !important;
        }
        .aps-dark #flightScheduleModal .input-group-text,
        .dark-style #flightScheduleModal .input-group-text,
        html[data-bs-theme="dark"] #flightScheduleModal .input-group-text {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #94a3b8 !important;
        }
        .aps-dark #flightScheduleModal .text-dark,
        .dark-style #flightScheduleModal .text-dark,
        html[data-bs-theme="dark"] #flightScheduleModal .text-dark {
            color: #f1f5f9 !important;
        }
        .aps-dark #flightScheduleModal .text-muted,
        .dark-style #flightScheduleModal .text-muted,
        html[data-bs-theme="dark"] #flightScheduleModal .text-muted {
            color: #94a3b8 !important;
        }
        .aps-dark #flightScheduleModal code,
        .dark-style #flightScheduleModal code,
        html[data-bs-theme="dark"] #flightScheduleModal code {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #7dd3fc !important;
        }
        /* Responsive & Scroll Tweaks for FIDS Modal */
        #flightScheduleModal .fids-table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 1px 2px rgba(0,0,0,0.12);
        }
        #flightScheduleModal .modal-dialog {
            max-height: calc(100vh - 1.5rem);
            max-height: calc(100dvh - 1.5rem);
        }
        #flightScheduleModal .modal-content {
            max-height: calc(100vh - 1.5rem);
            max-height: calc(100dvh - 1.5rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #flightScheduleModal .modal-body {
            flex: 1 1 auto;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 0 !important;
        }
        #fidsTableContainer {
            flex: 1 1 auto;
            overflow-y: auto !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }
        #fidsTableContainer::-webkit-scrollbar {
            height: 4px;
            width: 4px;
        }
        #fidsTableContainer::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.4);
            border-radius: 4px;
        }
        #flightScheduleModal .btn-fids-close {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: #ffffff !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            cursor: pointer;
            transition: background 0.15s ease, transform 0.15s ease;
            flex-shrink: 0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
        }
        #flightScheduleModal .btn-fids-close:hover {
            background: rgba(255, 255, 255, 0.4);
            color: #ffffff !important;
            transform: scale(1.06);
        }
        @media (max-width: 767.98px) {
            #flightScheduleModal .modal-dialog {
                margin: 0.25rem !important;
                max-width: calc(100% - 0.5rem) !important;
                max-height: calc(100vh - 0.5rem) !important;
                max-height: calc(100dvh - 0.5rem) !important;
                height: calc(100dvh - 0.5rem) !important;
            }
            #flightScheduleModal .modal-content {
                height: 100% !important;
                max-height: 100% !important;
                border-radius: 0.75rem !important;
            }
            #flightScheduleModal .fids-header { padding: 0.6rem 0.75rem !important; }
            #flightScheduleModal .modal-title { font-size: 0.88rem !important; }
            #flightScheduleModal .btn-fids-close { width: 28px; height: 28px; font-size: 0.75rem; }
            #flightScheduleModal .fids-controls { padding: 0.45rem 0.75rem !important; }
            #flightScheduleModal .fids-table td, #flightScheduleModal .fids-table th { padding: 0.45rem 0.35rem !important; }
            .btn-fr24, .btn-assignment-pill { padding: 4px 8px !important; font-size: 0.7rem !important; border-radius: 6px !important; }
        }
        .btn-fr24 {
            background: #ff7a00;
            border: none;
            color: #ffffff !important;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 8px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(255, 122, 0, 0.3);
            transition: all 0.2s ease-in-out;
        }
        .btn-fr24:hover {
            background: #e56d00;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(255, 122, 0, 0.4);
        }
        .btn-assignment-pill {
            background: #3b82f6;
            border: none;
            color: #ffffff !important;
            font-size: 0.76rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 8px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);
            transition: all 0.2s ease-in-out;
        }
        .btn-assignment-pill:hover {
            background: #2563eb;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4);
        }
    </style>

    @if (Auth::user()->hasRole(['Admin', 'Head Of Airport Service']))
    <div class="modal fade" id="flightScheduleModal" tabindex="-1" aria-labelledby="flightScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 overflow-hidden" style="border-radius: .875rem;">
                <!-- Header FIDS Board -->
                <div class="fids-header modal-header text-white py-3 px-3 px-md-4">
                    <div class="d-flex align-items-center gap-2 gap-md-3 flex-grow-1 min-w-0 me-2">
                        <div class="rounded-circle bg-primary bg-opacity-25 d-flex align-items-center justify-content-center text-info flex-shrink-0" style="width:34px;height:34px;">
                            <i class="fas fa-plane-arrival" style="font-size:.9rem;"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <h6 class="modal-title fw-bold text-white mb-0 d-flex align-items-center gap-1.5 flex-wrap" id="flightScheduleModalLabel">
                                <span class="text-truncate">Flight Schedule Board</span>
                                <span class="badge bg-warning text-dark px-2 py-0.5" style="font-size:.75rem;" id="fidsStationCode">CGK</span>
                            </h6>
                            <small class="text-light opacity-75 d-block text-truncate" id="fidsStationName" style="font-size:.72rem;">Jakarta (Soekarno-Hatta)</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto">
                        <span class="badge border border-success text-success d-none d-sm-inline-flex align-items-center gap-1" style="font-size:.68rem;background:rgba(34,197,94,.12);">
                            <i class="fas fa-satellite-dish fa-spin"></i> Live
                        </span>
                        <a href="https://www.flightradar24.com" target="_blank" rel="noopener" class="btn btn-sm d-none d-md-inline-flex align-items-center gap-1 text-white" style="background:#FF8000;border:none;font-size:.72rem;padding:4px 10px;border-radius:6px;" title="Buka Flightradar24">
                            <i class="fas fa-external-link-alt"></i> FR24
                        </a>
                        <button type="button" class="btn-fids-close" data-bs-dismiss="modal" aria-label="Close" title="Tutup Modal">
                            <i class="fas fa-times" style="font-size:14px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Controls Bar -->
                <div class="fids-controls px-3 px-md-4 py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width:400px;">
                        <div class="input-group input-group-sm w-100">
                            <span class="input-group-text border-end-0"><i class="fas fa-search" style="font-size:.75rem;"></i></span>
                            <input type="text" id="fidsSearchInput" class="form-control border-start-0 ps-0" style="font-size:.8rem;" placeholder="Cari flight, maskapai, registrasi..." onkeyup="filterFidsTable()">
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between justify-content-sm-end w-100 w-sm-auto gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-1">
                            <label for="fidsSortSelect" class="form-label mb-0 text-muted d-none d-md-inline" style="font-size:.72rem; white-space:nowrap;"><i class="fas fa-sort me-1"></i>Urutkan:</label>
                            <select id="fidsSortSelect" class="form-select form-select-sm border-secondary-subtle" style="font-size:.75rem; width:auto; min-width:145px;" onchange="filterFidsTable()">
                                <option value="time_asc" selected>Jam: Terawal → Terbaru</option>
                                <option value="time_desc">Jam: Terbaru → Terawal</option>
                                <option value="flight_asc">Flight (A - Z)</option>
                                <option value="airline_asc">Maskapai (A - Z)</option>
                            </select>
                        </div>
                        <small class="text-muted fw-semibold" id="fidsSummaryCount" style="font-size:.75rem;">0 Penerbangan</small>
                        <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" style="font-size:.75rem;" onclick="reloadCurrentFidsModal()">
                            <i class="fas fa-sync-alt" id="fidsRefreshIcon" style="font-size:.7rem;"></i>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-0">
                    <!-- Loading State -->
                    <div id="fidsLoadingState" class="text-center py-5">
                        <div class="spinner-border text-primary mb-3" style="width:2rem;height:2rem;" role="status"><span class="visually-hidden">Loading...</span></div>
                        <h6 class="fw-semibold mb-1">Mengambil Data Penerbangan Live...</h6>
                        <small class="text-muted">Menghubungkan ke Flightradar24...</small>
                    </div>
                    <!-- Error State -->
                    <div id="fidsErrorState" class="text-center py-5 d-none">
                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex p-3 mb-2">
                            <i class="fas fa-exclamation-triangle fs-4"></i>
                        </div>
                        <h6 class="fw-semibold text-danger mb-1" id="fidsErrorMessage">Gagal memuat data penerbangan.</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="reloadCurrentFidsModal()">Coba Lagi</button>
                    </div>
                    <!-- Empty State -->
                    <div id="fidsEmptyState" class="text-center py-5 d-none">
                        <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-inline-flex p-3 mb-2">
                            <i class="fas fa-plane-slash fs-4"></i>
                        </div>
                        <h6 class="fw-semibold text-muted mb-1">Tidak ada data penerbangan aktif.</h6>
                        <small class="text-muted">Coba refresh beberapa saat lagi.</small>
                    </div>
                    <!-- FIDS Table -->
                    <div id="fidsTableContainer" class="table-responsive h-100 d-none">
                        <table class="table table-hover align-middle mb-0 fids-table" id="fidsTable">
                            <thead>
                                <tr>
                                    <th class="ps-2 ps-md-4 py-3" style="width:28px;">#</th>
                                    <th class="py-3">FLIGHT</th>
                                    <th class="py-3 d-none d-md-table-cell">MASKAPAI</th>
                                    <th class="py-3 d-none d-md-table-cell">RUTE</th>
                                    <th class="py-3 d-none d-md-table-cell">REGISTRASI</th>
                                    <th class="py-3 d-none d-sm-table-cell">TO FLIGHT</th>
                                    <th class="py-3">STATUS FIDS</th>
                                    <th class="pe-2 pe-md-4 py-3 text-end" style="min-width:150px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody id="fidsTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div class="fids-footer modal-footer py-2 px-3 px-md-4 d-flex justify-content-between align-items-center">
                    <small class="text-muted d-none d-sm-block" style="font-size:.72rem;">
                        <i class="fas fa-satellite-dish me-1 text-warning"></i> Data live dari Flightradar24 Arrivals &amp; Departures
                    </small>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

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

@section('scripts')
    @if ($showManagementDashboard)
        <script src="{{ asset('vendor/chartjs/chart.umd.js') }}" defer></script>
    @endif
    <script>
        if (window.__dashboardCleanup) {
            window.__dashboardCleanup();
        }

        function initDashboardPage() {
            if (window.__dashboardCleanup) {
                window.__dashboardCleanup();
            }

            window.__dashboardCleanup = function() {
                if (window.__onDashboardThemeChanged) {
                    window.removeEventListener('aps:theme-changed', window.__onDashboardThemeChanged);
                }
                if (window.__dashboardCharts) {
                    window.__dashboardCharts.forEach(function(chart) {
                        if (chart && typeof chart.destroy === 'function') {
                            chart.destroy();
                        }
                    });
                }
                window.__dashboardCharts = [];
                if (window.__dashboardIntervals) {
                    window.__dashboardIntervals.forEach(clearInterval);
                }
                window.__dashboardIntervals = [];
            };

            window.__dashboardCharts = [];
            window.__dashboardIntervals = [];

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

            const dashboardCharts = window.__dashboardCharts;
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

            // Clean up old theme change listener to avoid accumulation
            if (window.__onDashboardThemeChanged) {
                window.removeEventListener('aps:theme-changed', window.__onDashboardThemeChanged);
            }
            window.__onDashboardThemeChanged = function() {
                const charts = window.__dashboardCharts || [];
                charts.forEach(applyDashboardChartTheme);
            };
            window.addEventListener('aps:theme-changed', window.__onDashboardThemeChanged);
            @endif

            document.querySelectorAll('.countdown').forEach(function(el) {
                let timeData = el.getAttribute('data-time');
                if (!timeData) {
                    el.innerHTML = "<span class='text-muted small'>-</span>";
                    return;
                }
                timeData = timeData.trim();

                let targetDate;
                
                try {
                    if (timeData.length <= 8) {
                        const parts = timeData.split(':');
                        targetDate = new Date();
                        targetDate.setHours(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2] || 0), 0);
                    } else {
                        const dateTimeParts = timeData.split(/[ T]/);
                        const dateParts = dateTimeParts[0].split(/[-/]/);
                        const timeParts = dateTimeParts[1].split(':');
                        
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
                    el.innerHTML = "<span class='text-muted small'>-</span>";
                    return;
                }

                const countDownDate = targetDate.getTime();
                if (isNaN(countDownDate)) {
                    el.innerHTML = "<span class='text-muted small'>-</span>";
                    return;
                }

                const updateTimer = function() {
                    const now = new Date().getTime();
                    const distance = countDownDate - now;
                    
                    if (distance >= 0) {
                        const hours = Math.floor(distance / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        const h = String(hours).padStart(2, '0');
                        const m = String(minutes).padStart(2, '0');
                        const s = String(seconds).padStart(2, '0');
                        
                        el.innerHTML = `<span class="badge bg-label-primary font-monospace px-2 py-1"><i class="bx bx-timer me-1"></i>${h}:${m}:${s}</span>`;
                        return true;
                    } else {
                        el.innerHTML = "<span class='badge bg-label-secondary font-monospace px-2 py-1'><i class='bx bx-time me-1'></i>Lewat Waktu</span>";
                        return false;
                    }
                };

                if (updateTimer()) {
                    const interval = setInterval(function() {
                        if (!updateTimer()) clearInterval(interval);
                    }, 1000);
                    window.__dashboardIntervals.push(interval);
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
            // End of counter & photo/print initialization

            // Initialize FIDS modal
            const modalEl = document.getElementById('flightScheduleModal');
            if (modalEl) {
                fidsModalInstance = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });

                // Load data only after modal is fully shown (prevents DOM race conditions)
                modalEl.addEventListener('shown.bs.modal', function () {
                    if (currentFidsStation) {
                        loadFidsData(currentFidsStation);
                    }
                });

                // Reset state when modal is hidden
                modalEl.addEventListener('hidden.bs.modal', function () {
                    rawFidsFlights = [];
                    const tbody = document.getElementById('fidsTableBody');
                    if (tbody) tbody.innerHTML = '';
                    const countEl = document.getElementById('fidsSummaryCount');
                    if (countEl) countEl.innerText = 'Total: 0 Penerbangan';
                    const loadingState = document.getElementById('fidsLoadingState');
                    if (loadingState) loadingState.classList.remove('d-none');
                    const tableContainer = document.getElementById('fidsTableContainer');
                    if (tableContainer) tableContainer.classList.add('d-none');
                    const errorState = document.getElementById('fidsErrorState');
                    if (errorState) errorState.classList.add('d-none');
                    const emptyState = document.getElementById('fidsEmptyState');
                    if (emptyState) emptyState.classList.add('d-none');
                    const searchEl = document.getElementById('fidsSearchInput');
                    if (searchEl) searchEl.value = '';
                });
            }
        } // end initDashboardPage

        let currentFidsStation = '';
        let currentFidsStationName = '';
        let rawFidsFlights = [];
        let fidsModalInstance = null;

        window.openFlightScheduleModal = function (stationCode, stationName) {
            currentFidsStation = stationCode;
            currentFidsStationName = stationName;

            const codeEl = document.getElementById('fidsStationCode');
            const nameEl = document.getElementById('fidsStationName');
            if (codeEl) codeEl.innerText = stationCode;
            if (nameEl) nameEl.innerText = stationName;

            // Ensure loading state is shown before opening
            const loadingState = document.getElementById('fidsLoadingState');
            const tableContainer = document.getElementById('fidsTableContainer');
            const errorState = document.getElementById('fidsErrorState');
            const emptyState = document.getElementById('fidsEmptyState');
            if (loadingState) loadingState.classList.remove('d-none');
            if (tableContainer) tableContainer.classList.add('d-none');
            if (errorState) errorState.classList.add('d-none');
            if (emptyState) emptyState.classList.add('d-none');

            const modalEl = document.getElementById('flightScheduleModal');
            if (modalEl) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    if (!fidsModalInstance) {
                        fidsModalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
                    }
                    fidsModalInstance.show();
                } else if (typeof jQuery !== 'undefined' && typeof $('#flightScheduleModal').modal === 'function') {
                    $('#flightScheduleModal').modal('show');
                }
            }

            // Immediately load data for station
            loadFidsData(stationCode);
        };

        function reloadCurrentFidsModal() {
            if (currentFidsStation) {
                loadFidsData(currentFidsStation);
            }
        }

        function loadFidsData(stationCode) {
            const loadingState = document.getElementById('fidsLoadingState');
            const errorState = document.getElementById('fidsErrorState');
            const emptyState = document.getElementById('fidsEmptyState');
            const tableContainer = document.getElementById('fidsTableContainer');
            const refreshIcon = document.getElementById('fidsRefreshIcon');

            if (loadingState) loadingState.classList.remove('d-none');
            if (errorState) errorState.classList.add('d-none');
            if (emptyState) emptyState.classList.add('d-none');
            if (tableContainer) tableContainer.classList.add('d-none');
            if (refreshIcon) refreshIcon.classList.add('fa-spin');

            function loadFidsFromServer(ignoreServerError = false) {
                fetch('{{ route("assignments.fetch_flight_data") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ station: stationCode })
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(resData => {
                    if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                    if (loadingState) loadingState.classList.add('d-none');

                    if (resData.success && Array.isArray(resData.flights) && resData.flights.length > 0) {
                        rawFidsFlights = resData.flights;
                        filterFidsTable();
                        if (tableContainer) tableContainer.classList.remove('d-none');
                    } else {
                        rawFidsFlights = [];
                        if (emptyState) emptyState.classList.remove('d-none');
                        const countEl = document.getElementById('fidsSummaryCount');
                        if (countEl) countEl.innerText = 'Total: 0 Penerbangan';
                    }
                })
                .catch(err => {
                    if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                    if (loadingState) loadingState.classList.add('d-none');
                    if (ignoreServerError) {
                        if (emptyState) emptyState.classList.remove('d-none');
                        const countEl = document.getElementById('fidsSummaryCount');
                        if (countEl) countEl.innerText = 'Total: 0 Penerbangan';
                        rawFidsFlights = [];
                    } else {
                        if (errorState) errorState.classList.remove('d-none');
                        const errEl = document.getElementById('fidsErrorMessage');
                        if (errEl) errEl.innerText = 'Gagal terhubung ke server. ' + (err.message || '');
                    }
                });
            }

            // Step 1: Try browser-side direct fetch to Flightradar24 (bypasses server IP block on production)
            const stLower = (stationCode || 'cgk').toLowerCase();
            const arrUrl = `https://api.flightradar24.com/common/v1/airport.json?code=${stLower}&plugin[]=&plugin-setting[schedule][mode]=arrivals&limit=100`;

            fetch(arrUrl, {
                headers: {
                    'Accept': 'application/json, text/plain, */*',
                    'Accept-Language': 'en-US,en;q=0.9'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('FR24 HTTP ' + res.status);
                return res.json();
            })
            .then(json => {
                const arrivalsData = json?.result?.response?.airport?.pluginData?.schedule?.arrivals?.data || [];
                if (arrivalsData.length === 0) {
                    loadFidsFromServer(true);
                    return;
                }

                const depUrl = `https://api.flightradar24.com/common/v1/airport.json?code=${stLower}&plugin[]=&plugin-setting[schedule][mode]=departures&limit=100`;
                return fetch(depUrl, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : {})
                    .then(depJson => {
                        const depData = depJson?.result?.response?.airport?.pluginData?.schedule?.departures?.data || [];
                        const depMap = {};
                        depData.forEach(d => {
                            const dFl = d.flight || {};
                            const dReg = (dFl.aircraft?.registration || '').toUpperCase().replace(/[-\s]/g, '');
                            const dNum = dFl.identification?.number?.default || dFl.identification?.callsign || '';
                            const dId = dFl.identification?.id || '';
                            if (dReg && dNum) {
                                depMap[dReg] = { flight_no: dNum.toUpperCase(), flight_id: dId };
                            }
                        });

                        const parsedFlights = [];
                        arrivalsData.forEach(item => {
                            const fl = item.flight || {};
                            const reg = fl.aircraft?.registration || '';
                            const flightNo = fl.identification?.number?.default || fl.identification?.callsign || '-';
                            const flightId = fl.identification?.id || '';
                            const ts = fl.time?.real?.arrival || fl.time?.estimated?.arrival || fl.time?.scheduled?.arrival;

                            const cleanReg = reg.toUpperCase().replace(/[-\s]/g, '');
                            const toObj = depMap[cleanReg] || {};
                            const toFlightNo = toObj.flight_no || '-';

                            let startStr = '', endStr = '';
                            if (ts) {
                                const d = new Date(ts * 1000);
                                startStr = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' });
                                const dEnd = new Date((ts + 1800) * 1000);
                                endStr = dEnd.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' });
                            }

                            const originCity = fl.airport?.origin?.position?.region?.city
                                || fl.airport?.origin?.name
                                || fl.airport?.origin?.code?.iata
                                || '';
                            const originCode = (fl.airport?.origin?.code?.iata || '').toUpperCase();
                            const airlineName = (fl.airline?.name || '') !== 'Airlines' ? (fl.airline?.name || '') : '';
                            const statusText = fl.status?.text || 'Scheduled';
                            const statusColor = (fl.status?.icon || fl.status?.generic?.status?.color || 'gray').toLowerCase();

                            parsedFlights.push({
                                flight_id: flightId,
                                aircraft_reg: reg.toUpperCase(),
                                ex_flight: flightNo.toUpperCase(),
                                to_flight: toFlightNo,
                                station: stationCode,
                                timestamp: ts || 0,
                                start_time: startStr,
                                end_time: endStr,
                                origin: originCity !== '-' ? originCity : '',
                                origin_code: originCode,
                                status_text: statusText,
                                status_color: statusColor,
                                airline: airlineName
                            });
                        });

                        if (refreshIcon) refreshIcon.classList.remove('fa-spin');
                        if (loadingState) loadingState.classList.add('d-none');

                        if (parsedFlights.length > 0) {
                            rawFidsFlights = parsedFlights;
                            filterFidsTable();
                            if (tableContainer) tableContainer.classList.remove('d-none');
                        } else {
                            loadFidsFromServer();
                        }
                    });
            })
            .catch(err => {
                // Fallback to backend server fetch
                loadFidsFromServer();
            });
        }

        function renderFidsTable(flights) {
            const tbody = document.getElementById('fidsTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';

            const countEl = document.getElementById('fidsSummaryCount');
            if (countEl) countEl.innerText = `${flights.length} Penerbangan`;

            flights.forEach((item, index) => {
                const createAssignmentUrl = `{{ route('assignments.create') }}?station=${encodeURIComponent(item.station)}&aircraft_reg=${encodeURIComponent(item.aircraft_reg)}&ex_flight=${encodeURIComponent(item.ex_flight)}&to_flight=${encodeURIComponent(item.to_flight)}&start_time=${encodeURIComponent(item.start_time)}&end_time=${encodeURIComponent(item.end_time)}`;

                // Determine status badge first so we know if flight is landed
                let statusBadge = '';
                const sText = item.status_text || 'Scheduled';
                const sColor = (item.status_color || '').toLowerCase();
                const isLanded = sText.toLowerCase().includes('landed') || sText.toLowerCase().includes('arrived');

                // Build direct Flightradar24 URL (smart resolution for live vs landed)
                const flightNoClean = (item.ex_flight || '').replace(/\s+/g, '').toUpperCase();
                const regClean = (item.aircraft_reg || '').replace(/[-\s]/g, '').toLowerCase();
                let fr24Url = 'https://www.flightradar24.com';

                if (isLanded) {
                    // For landed flights, link directly to flight history or aircraft data page on FR24 to prevent "Live flight not found" popup
                    if (flightNoClean && flightNoClean !== '-') {
                        fr24Url = `https://www.flightradar24.com/data/flights/${flightNoClean.toLowerCase()}`;
                    } else if (regClean) {
                        fr24Url = `https://www.flightradar24.com/data/aircraft/${regClean}`;
                    }
                } else if (item.flight_id && flightNoClean && flightNoClean !== '-') {
                    fr24Url = `https://www.flightradar24.com/${flightNoClean}/${item.flight_id}`;
                } else if (flightNoClean && flightNoClean !== '-') {
                    fr24Url = `https://www.flightradar24.com/data/flights/${flightNoClean.toLowerCase()}`;
                } else if (regClean) {
                    fr24Url = `https://www.flightradar24.com/data/aircraft/${regClean}`;
                }

                const toFlightNoClean = (item.to_flight || '').replace(/\s+/g, '').toUpperCase();
                let toFr24Url = '';
                if (toFlightNoClean && toFlightNoClean !== '-') {
                    toFr24Url = `https://www.flightradar24.com/data/flights/${toFlightNoClean.toLowerCase()}`;
                }

                const toFlightDisplay = (toFlightNoClean && toFlightNoClean !== '-')
                    ? `<a href="${toFr24Url}" target="_blank" rel="noopener" class="badge bg-label-info text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:.72rem;" title="Buka ${item.to_flight} di Flightradar24">${item.to_flight} <i class="fas fa-external-link-alt" style="font-size:.55rem;opacity:.75;"></i></a>`
                    : `<span class="text-muted">-</span>`;



                let statusTextFormatted = sText.toUpperCase();
                if (item.start_time && !statusTextFormatted.includes(':')) {
                    statusTextFormatted += ' ' + item.start_time;
                }

                if (sColor === 'green' || sText.toLowerCase().includes('landed') || sText.toLowerCase().includes('arrived')) {
                    statusBadge = `<span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2 py-1" style="font-size:.72rem;"><i class="fas fa-check-circle me-1"></i>${statusTextFormatted}</span>`;
                } else if (sColor === 'yellow' || sColor === 'amber' || sText.toLowerCase().includes('delayed')) {
                    statusBadge = `<span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25 px-2 py-1" style="font-size:.72rem;"><i class="fas fa-exclamation-circle me-1"></i>${statusTextFormatted}</span>`;
                } else if (sColor === 'red' || sText.toLowerCase().includes('cancelled')) {
                    statusBadge = `<span class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 px-2 py-1" style="font-size:.72rem;"><i class="fas fa-times-circle me-1"></i>${statusTextFormatted}</span>`;
                } else if (sText.toLowerCase().includes('en route') || sText.toLowerCase().includes('estimated')) {
                    statusBadge = `<span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25 px-2 py-1" style="font-size:.72rem;"><i class="fas fa-plane-arrival me-1"></i>${statusTextFormatted}</span>`;
                } else {
                    statusBadge = `<span class="badge bg-secondary bg-opacity-15 text-secondary border border-secondary border-opacity-25 px-2 py-1" style="font-size:.72rem;"><i class="far fa-clock me-1"></i>${statusTextFormatted}</span>`;
                }

                // Build Route Display
                const originLabel = item.origin ? item.origin : (item.origin_code || '-');
                const routeDisplay = `
                    <div class="d-flex align-items-center gap-1 flex-wrap" style="font-size:.78rem;">
                        <span class="fw-semibold text-dark">${originLabel}</span>
                        ${item.origin_code ? `<span class="badge bg-label-secondary" style="font-size:.65rem;">${item.origin_code}</span>` : ''}
                        <i class="fas fa-arrow-right text-muted mx-0.5" style="font-size:.6rem;opacity:.7;"></i>
                        <span class="badge bg-label-warning text-dark fw-bold" style="font-size:.68rem;">${item.station}</span>
                    </div>
                `;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="ps-2 ps-md-4 fw-semibold text-muted" style="font-size:.75rem;width:28px;">${index + 1}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <a href="${fr24Url}" target="_blank" rel="noopener" class="fw-bold text-primary text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:.88rem;letter-spacing:.2px;" title="Buka ${item.ex_flight} di Flightradar24">
                                ${item.ex_flight || '-'}
                                <i class="fas fa-external-link-alt text-primary" style="font-size:.58rem;opacity:.75;"></i>
                            </a>
                            <span class="d-md-none text-muted text-truncate" style="font-size:.7rem;max-width:110px;">${item.airline || ''} ${item.origin ? '• ' + item.origin : ''}</span>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="fw-semibold text-dark" style="font-size:.8rem;">${item.airline || '-'}</span>
                    </td>
                    <td class="d-none d-md-table-cell">
                        ${routeDisplay}
                    </td>
                    <td class="d-none d-md-table-cell">
                        <code style="font-size:.78rem;background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);color:#6366f1;border-radius:4px;padding:2px 6px;">${item.aircraft_reg || '-'}</code>
                    </td>
                    <td class="d-none d-sm-table-cell">${toFlightDisplay}</td>
                    <td>${statusBadge}</td>
                    <td class="pe-2 pe-md-4 text-end" style="min-width:150px;">
                        <div class="d-flex align-items-center justify-content-end gap-1.5" style="gap: 6px !important;">
                            <a href="${fr24Url}" target="_blank" rel="noopener"
                               class="btn-fr24"
                               title="Buka live radar ${item.ex_flight} di Flightradar24">
                                <i class="fas fa-plane" style="font-size:.68rem;"></i>
                                <span>FR24</span>
                            </a>
                            <a href="${createAssignmentUrl}"
                               class="btn-assignment-pill"
                               title="Buat Assignment untuk ${item.ex_flight}">
                                <i class="fas fa-plus-circle" style="font-size:.68rem;"></i>
                                <span>Assignment</span>
                            </a>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function parseFlightTime(item) {
            let timeStr = item.start_time || '';
            if (!timeStr && item.status_text) {
                const match = item.status_text.match(/\b(\d{1,2})[:.](\d{2})\b/);
                if (match) {
                    timeStr = match[0];
                }
            }
            if (timeStr) {
                const match = timeStr.match(/(\d{1,2})[:.](\d{2})/);
                if (match) {
                    const h = parseInt(match[1], 10);
                    const m = parseInt(match[2], 10);
                    if (!isNaN(h) && !isNaN(m)) {
                        return h * 3600 + m * 60;
                    }
                }
            }
            if (item.timestamp && item.timestamp > 0) {
                const d = new Date(item.timestamp * 1000);
                return d.getHours() * 3600 + d.getMinutes() * 60;
            }
            return 0;
        }

        function filterFidsTable() {
            const query = document.getElementById('fidsSearchInput') ? document.getElementById('fidsSearchInput').value.toLowerCase().trim() : '';
            const sortVal = document.getElementById('fidsSortSelect') ? document.getElementById('fidsSortSelect').value : 'time_asc';

            let result = Array.from(rawFidsFlights || []);

            if (query) {
                result = result.filter(item => {
                    return (item.ex_flight && item.ex_flight.toLowerCase().includes(query)) ||
                           (item.aircraft_reg && item.aircraft_reg.toLowerCase().includes(query)) ||
                           (item.airline && item.airline.toLowerCase().includes(query)) ||
                           (item.origin && item.origin.toLowerCase().includes(query)) ||
                           (item.to_flight && item.to_flight.toLowerCase().includes(query)) ||
                           (item.status_text && item.status_text.toLowerCase().includes(query)) ||
                           (item.start_time && item.start_time.toLowerCase().includes(query));
                });
            }

            result.sort((a, b) => {
                if (sortVal === 'time_asc') {
                    return parseFlightTime(a) - parseFlightTime(b);
                } else if (sortVal === 'time_desc') {
                    return parseFlightTime(b) - parseFlightTime(a);
                } else if (sortVal === 'flight_asc') {
                    return (a.ex_flight || '').localeCompare(b.ex_flight || '');
                } else if (sortVal === 'airline_asc') {
                    return (a.airline || '').localeCompare(b.airline || '');
                }
                return 0;
            });

            renderFidsTable(result);
        }

        // Run init on load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardPage);
        } else {
            initDashboardPage();
        }

        // Clean up and bind to PJAX content loaded event
        if (window.__onDashboardContentLoaded) {
            window.removeEventListener('aps:content-loaded', window.__onDashboardContentLoaded);
        }
        window.__onDashboardContentLoaded = function() {
            initDashboardPage();
        };
        window.addEventListener('aps:content-loaded', window.__onDashboardContentLoaded);
    </script>
@endsection
