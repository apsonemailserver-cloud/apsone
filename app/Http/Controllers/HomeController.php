<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Document;
use App\Models\Flights;
use App\Models\Leave;
use App\Models\Schedule;
use App\Models\Station;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RealRashid\SweetAlert\Facades\Alert;

class HomeController extends Controller
{
    private const MANAGEMENT_ROLES = [
        'Admin',
        'Head Of Airport Service',
        'SPV Bge',
        'SPV Apron',
        'Leader Bge',
        'Leader Apron',
        'Ass Leader Bge',
        'Ass Leader Apron',
        'Leader Aircraft Interior Exterior Cleaning',
        'Leader Porter Apron',
        'Finance',
        'HSE',
        'Controller',
        'Quality Control',
    ];

    /**
     * Menampilkan data dashboard utama dengan Filter Station.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $showManagementDashboard = $user->hasRole(self::MANAGEMENT_ROLES);
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->first();

        // =================================================================
        // BAGIAN 0: LOGIKA FILTER STATION (BARU)
        // =================================================================

        // Default: Station milik user yang login
        $selectedStation = $user->station;

        // Jika Admin, boleh ambil dari Dropdown (Request). Jika kosong, default 'All'
        if ($user->hasRole('Admin')) {
            $selectedStation = $request->input('station', 'All');
        }

        // Siapkan list station untuk isi Dropdown (Khusus Admin)
        $listStations = [];
        if ($user->hasRole('Admin')) {
            $listStations = Station::where('is_active', 1)->get();
        }

        // =================================================================
        // BAGIAN 1: MENGAMBIL DATA UTAMA (DENGAN FILTER)
        // =================================================================

        // 1. Data Penerbangan / Work Orders sesuai periode dashboard
        $today = Carbon::today();
        if ($showManagementDashboard) {
            $flightsQuery = WorkOrder::with(['users', 'submittedBy'])->whereDate('date', $today);
            if ($selectedStation !== 'All') {
                $flightsQuery->where('station', $selectedStation);
            }
            $flights = $flightsQuery->get();
        } else {
            $flightsQuery = Flights::with(['details.schedule.user'])
                ->whereBetween('created_at', [
                    $today->copy()->subDays(6)->startOfDay(),
                    $today->copy()->endOfDay(),
                ]);
            if ($selectedStation !== 'All') {
                $flightsQuery->where('station', $selectedStation);
            }
            $flights = $flightsQuery->get();
        }

        if (! $showManagementDashboard) {
            return view('home', [
                'showManagementDashboard' => false,
                'selectedStation' => $selectedStation,
                'listStations' => $listStations,
                'todayAttendance' => $todayAttendance,
                'flights' => $flights,
                ...$this->staffDashboardData($user),
            ]);
        }

        // 2. Total Penerbangan Selesai Hari Ini
        $totalFlightQuery = WorkOrder::whereDate('date', Carbon::today());
        if ($selectedStation !== 'All') {
            $totalFlightQuery->where('station', $selectedStation);
        }
        $totalFlightPerDay = $totalFlightQuery->count();

        // 3. Total Staff (ALWAYS GLOBAL as requested)
        $userCount = User::count();

        // 3b. Staff for attendance calculation (Filtered by Station)
        $userKehadiranQuery = User::query();
        if ($selectedStation !== 'All') {
            $userKehadiranQuery->where('station', $selectedStation);
        }
        $userKehadiranCount = $userKehadiranQuery->count();

        // 4. Staff Sedang Bekerja (Working Manpower via Assignments)
        $workingQuery = DB::table('assignment_user')
            ->join('assignments', 'assignment_user.assignment_id', '=', 'assignments.id')
            ->whereDate('assignments.date', Carbon::today());

        if ($selectedStation !== 'All') {
            $workingQuery->where('assignments.station', $selectedStation);
        }
        $workingManpowers = $workingQuery->distinct('assignment_user.user_id')->count('assignment_user.user_id');

        // =================================================================
        // BAGIAN 2: MENYIAPKAN DATA UNTUK INFO CARD
        // =================================================================
        $twoMonthsFromNow = Carbon::today()->addMonths(2);

        // 1. Kontrak Expired Soon
        $contractQuery = User::whereDate('contract_end', '<=', $twoMonthsFromNow)
            ->whereDate('contract_end', '>=', Carbon::today());
        if ($selectedStation !== 'All') {
            $contractQuery->where('station', $selectedStation);
        }
        $totalContractStaff = $contractQuery->count();

        // 2. PAS Expired Soon
        $pasQuery = User::whereDate('pas_expired', '<=', $twoMonthsFromNow)
            ->whereDate('pas_expired', '>=', Carbon::today());
        if ($selectedStation !== 'All') {
            $pasQuery->where('station', $selectedStation);
        }
        $totalPasStaff = $pasQuery->count();

        // 3. Data Absensi / Cuti Hari Ini
        $absentQuery = DB::table('leaves')
            ->join('users', 'leaves.user_id', '=', 'users.id')
            ->whereDate('leaves.start_date', '<=', Carbon::today())
            ->whereDate('leaves.end_date', '>=', Carbon::today())
            ->where('leaves.status', 'approved');

        if ($selectedStation !== 'All') {
            $absentQuery->where('users.station', $selectedStation);
        }

        $absentUsers = $absentQuery->select('users.id', 'users.fullname', 'leaves.leave_type', 'leaves.status')->get();
        $totalAbsent = $absentUsers->count();

        // Hitung Hadir & Persentase
        $presentToday = $userKehadiranCount - $totalAbsent;
        $attendancePercentage = $userKehadiranCount > 0
            ? round(($presentToday / $userKehadiranCount) * 100, 2)
            : 0;

        // =================================================================
        // BAGIAN 3: CHART (DENGAN FILTER STATION - EFFICIENT BATCH QUERY)
        // =================================================================
        $lineChartLabels = [];
        $lineChartData = [];
        $barChartLabels = [];
        $sickData = [];
        $leaveData = [];

        $chartStartDate = Carbon::now()->subDays(6)->startOfDay();
        $chartEndDate = Carbon::now()->endOfDay();

        // 1. Batch query daily flights / assignments (1 query instead of 7)
        $dailyFlightQ = WorkOrder::select(DB::raw('date as date_key'), DB::raw('count(*) as total'))
            ->whereBetween('date', [$chartStartDate->toDateString(), $chartEndDate->toDateString()]);
        if ($selectedStation !== 'All') {
            $dailyFlightQ->where('station', $selectedStation);
        }
        $dailyFlightCounts = $dailyFlightQ->groupBy('date')
            ->pluck('total', 'date_key');

        // 2. Batch query daily leaves for Sakit & Tahunan (1 query instead of 14)
        $dailyLeaveQ = Leave::join('users', 'leaves.user_id', '=', 'users.id')
            ->select(DB::raw('DATE(leaves.start_date) as date_key'), 'leaves.leave_type', DB::raw('count(*) as total'))
            ->whereBetween('leaves.start_date', [$chartStartDate->toDateString(), $chartEndDate->toDateString()])
            ->whereIn('leaves.leave_type', ['Cuti Sakit', 'Cuti Tahunan']);
        if ($selectedStation !== 'All') {
            $dailyLeaveQ->where('users.station', $selectedStation);
        }
        $dailyLeaveCounts = $dailyLeaveQ->groupBy(DB::raw('DATE(leaves.start_date)'), 'leaves.leave_type')
            ->get();

        $sickByDate = [];
        $leaveByDate = [];
        foreach ($dailyLeaveCounts as $row) {
            $dKey = (string) $row->date_key;
            if ($row->leave_type === 'Cuti Sakit') {
                $sickByDate[$dKey] = (int) $row->total;
            } elseif ($row->leave_type === 'Cuti Tahunan') {
                $leaveByDate[$dKey] = (int) $row->total;
            }
        }

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->format('Y-m-d');
            $dayName = $date->locale('id')->dayName;

            $lineChartLabels[] = $dayName;
            $barChartLabels[] = $dayName;

            $lineChartData[] = (int) ($dailyFlightCounts[$dateString] ?? 0);
            $sickData[] = $sickByDate[$dateString] ?? 0;
            $leaveData[] = $leaveByDate[$dateString] ?? 0;
        }

        // Doughnut Chart: Distribusi Role (Filtered)
        $doughnutQuery = User::select('role', DB::raw('count(*) as total'));
        if ($selectedStation !== 'All') {
            $doughnutQuery->where('station', $selectedStation);
        }
        $doughnutData = $doughnutQuery->groupBy('role')->get();

        $doughnutChartLabels = $doughnutData->pluck('role');
        $doughnutChartData = $doughnutData->pluck('total');

        // =================================================================
        // BAGIAN 4: SWEETALERT & MONITORING
        // =================================================================
        if ($user && ! session()->has('pas_warning_shown')) {
            if (empty($user->pas_expired)) {
                Alert::warning('Peringatan', '⚠️ Belum ada data masa berlaku PAS Anda. Harap isi segera.');
                session()->put('pas_warning_shown', true);
            } else {
                $expiredDate = Carbon::parse($user->pas_expired);
                $today = Carbon::today();
                $diffMonths = ceil($today->diffInDays($expiredDate) / 30);

                if ($diffMonths <= 2 && $expiredDate->greaterThanOrEqualTo($today)) {
                    Alert::warning('Peringatan', '⚠️ Masa berlaku PAS Anda akan habis dalam '.$diffMonths.' bulan lagi. Harap segera perpanjang.');
                    session()->put('pas_warning_shown', true);
                }
            }
        }

        // Data Monitoring Station (Untuk Widget Kartu-Kartu Station)
        $allStations = !empty($listStations) ? $listStations : Station::where('is_active', 1)->get();
        $stationStats = User::select('station', DB::raw('count(*) as total'))
            ->groupBy('station')
            ->pluck('total', 'station');

        // =================================================================
        // BAGIAN 4B: WORK RESULTS STATS (BARU)
        // =================================================================
        $recentWorkResults = collect();
        $totalWoToday = 0;
        $totalWoThisMonth = 0;

        if ($showManagementDashboard) {
            $woQuery = WorkOrder::query();
            
            if ($user->hasRole('Admin')) {
                if ($selectedStation !== 'All') {
                    $woQuery->where('station', $selectedStation);
                }
            } else {
                $woQuery->where('submitted_by', $user->id);
            }

            $totalWoToday = (clone $woQuery)->whereDate('date', Carbon::today())->count();
            $totalWoThisMonth = (clone $woQuery)->whereMonth('date', Carbon::today()->month)
                                               ->whereYear('date', Carbon::today()->year)->count();
            
            $recentWorkResults = $woQuery->with(['users', 'submittedBy'])
                ->orderByRaw("CASE WHEN photo_path IS NULL OR photo_path = '' THEN 0 ELSE 1 END ASC")
                ->orderBy('date', 'desc')
                ->orderBy('start_time', 'desc')
                ->take(5)
                ->get();
        }

        $pendingWorkResultsCount = WorkOrder::query()
            ->whereMonth('date', Carbon::today()->month)
            ->whereYear('date', Carbon::today()->year)
            ->when(!$user->hasRole('Admin'), function($q) use ($user) {
                if ($user->hasRole(WorkOrder::LEADER_ROLES)) {
                    $q->where('submitted_by', $user->id);
                } else {
                    $q->whereHas('users', fn($sq) => $sq->where('users.id', $user->id));
                }
            })
            ->when($user->hasRole('Admin') && isset($selectedStation) && $selectedStation !== 'All', fn($q) => $q->where('station', $selectedStation))
            ->where(fn($q) => $q->whereNull('photo_path')->orWhere('photo_path', ''))
            ->count();

        // =================================================================
        // BAGIAN 5: RETURN VIEW
        // =================================================================
        return view('home', compact(
            // Data Filter
            'showManagementDashboard',
            'selectedStation',
            'listStations',

            // KPI Utama
            'userCount',
            'workingManpowers',
            'flights',
            'totalFlightPerDay',

            // Info Card
            'todayAttendance',
            'totalContractStaff',
            'totalPasStaff',
            'totalAbsent',
            'attendancePercentage',
            'presentToday',

            // Charts
            'lineChartLabels',
            'lineChartData',
            'doughnutChartLabels',
            'doughnutChartData',
            'barChartLabels',
            'sickData',
            'leaveData',

            // Monitoring Station Widget
            'allStations',
            'stationStats',

            // Work Results (Pekerjaan) Stats
            'recentWorkResults',
            'totalWoToday',
            'totalWoThisMonth',
            'pendingWorkResultsCount'
        ));
    }

    /**
     * Internal Helper: Generate Data Dashboard Staff
     */
    private function staffDashboardData(User $user)
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();

        $monthStartOfDay = $monthStart->copy()->startOfDay();
        $todayEndOfDay = $today->copy()->endOfDay();

        $historyDates = collect();
        for ($i = 0; $i < 7; $i++) {
            $historyDates->push($today->copy()->subDays($i)->toDateString());
        }

        $assignedFlights = Flights::whereHas(
            'details.schedule',
            fn ($query) => $query->where('user_id', $user->id)
        )
            ->whereBetween('created_at', [$monthStartOfDay, $todayEndOfDay])
            ->orderBy('arrival')
            ->get();

        $personalWorkResultsLastMonth = WorkOrder::with(['users', 'submittedBy'])
            ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->whereBetween('date', [$monthStart->toDateString(), $today->toDateString()])
            ->orderByRaw("CASE WHEN photo_path IS NULL OR photo_path = '' THEN 0 ELSE 1 END ASC")
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        $scheduledDates = Schedule::where('user_id', $user->id)
            ->whereBetween('date', [$monthStart->toDateString(), $today->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $attendedDates = Attendance::where('user_id', $user->id)
            ->whereNotNull('check_in_time')
            ->whereBetween('check_in_time', [
                $monthStartOfDay,
                $todayEndOfDay,
            ])
            ->pluck('check_in_time')
            ->map(fn ($time) => Carbon::parse($time)->toDateString())
            ->unique()
            ->values();

        $totalScheduled = $scheduledDates->count();
        $totalAttended = $attendedDates->intersect($scheduledDates)->count();

        $personalAttendancePercentage = $totalScheduled > 0
            ? round(($totalAttended / $totalScheduled) * 100, 2)
            : 0;

        $personalAttendanceHistory = Attendance::with('station')
            ->where('user_id', $user->id)
            ->whereIn(\Illuminate\Support\Facades\DB::raw('DATE(check_in_time)'), $historyDates)
            ->orderBy('check_in_time', 'desc')
            ->get();

        $personalSchedules = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->whereIn('date', $historyDates)
            ->get()
            ->keyBy(fn (Schedule $schedule) => Carbon::parse($schedule->date)->toDateString());

        $personalWorkResults = WorkOrder::with(['users', 'submittedBy'])
            ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
            ->orderByRaw("CASE WHEN photo_path IS NULL OR photo_path = '' THEN 0 ELSE 1 END ASC")
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->take(5)
            ->get();

        $pendingWorkResultsCount = $personalWorkResultsLastMonth
            ->filter(fn($wo) => empty($wo->photo_path))
            ->count();

        $totalAssignments = $personalWorkResultsLastMonth->count() + $assignedFlights->count();
        $totalCompleted = $personalWorkResultsLastMonth->count() + $assignedFlights->where('status', true)->count();

        return [
            'assignedFlights' => $assignedFlights,
            'personalWorkResultsLastMonth' => $personalWorkResultsLastMonth,
            'personalAttendanceHistory' => $personalAttendanceHistory,
            'personalSchedules' => $personalSchedules,
            'personalAttendancePercentage' => $personalAttendancePercentage,
            'personalAssignmentsLastMonth' => $totalAssignments,
            'personalCompletedFlightsLastMonth' => $totalCompleted,
            'personalWorkResults' => $personalWorkResults,
            'pendingWorkResultsCount' => $pendingWorkResultsCount,
        ];
    }

    /**
     * Method Generate PDF (TIDAK DIUBAH)
     */
    public function generatePDF()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login')->withErrors('Silakan login terlebih dahulu.');
        }

        $karyawan = DB::table('users')
            ->select('id', 'fullname', 'role', 'alamat')
            ->where('id', $user->id)
            ->first();

        if (! $karyawan) {
            return back()->withErrors('Data karyawan tidak ditemukan.');
        }

        $tanggal_surat = now()->translatedFormat('d F Y');
        $logoPath = public_path('storage/photo/JAS Airport Services.png');

        // Cek jika file ada untuk menghindari error
        $base64Logo = '';
        if (file_exists($logoPath)) {
            $base64Logo = 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('template', [
            'nama_karyawan' => $karyawan->fullname,
            'nik_karyawan' => $karyawan->id,
            'posisi_karyawan' => $karyawan->role,
            'alamat_karyawan' => $karyawan->alamat,
            'tanggal_surat' => $tanggal_surat,
            'base64' => $base64Logo,
        ]);

        return $pdf->download("Surat-Pengganti-ID-Card-{$karyawan->fullname}.pdf");
    }

    public function document(): View
    {
        $role = Auth::user()->role;
        $visibleDocuments = Document::query()
            ->orderBy('nama_dokumen')
            ->get()
            ->filter(fn (Document $document) => $document->isVisibleForRole($role))
            ->values();

        $totalDocuments = $visibleDocuments->count();
        $allRoleDocuments = $visibleDocuments->filter(fn (Document $document) => $document->isAllRoleAccess())->count();
        $adminDocuments = $visibleDocuments->filter(fn (Document $document) => $document->hasRoleAccess('Admin'))->count();
        $managerDocuments = $visibleDocuments->filter(fn (Document $document) => $document->hasAnyRoleAccess(Document::managerRoles()))->count();

        return view('document', compact(
            'visibleDocuments',
            'totalDocuments',
            'allRoleDocuments',
            'adminDocuments',
            'managerDocuments'
        ));
    }

    public function downloadDocument(Document $document)
    {
        if (! $document->isVisibleForRole(Auth::user()->role)) {
            abort(403);
        }

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            Alert::error('File tidak ditemukan', 'Silakan unggah file dokumen terlebih dahulu.');

            return redirect()->route('document');
        }

        return Storage::disk('public')->download($document->file_path, $document->nama_file);
    }
}
