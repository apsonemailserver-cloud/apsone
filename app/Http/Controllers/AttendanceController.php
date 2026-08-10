<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Station;
use App\Models\User;
use App\Models\AttendanceCorrection;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->first();

        $todaySchedule = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->where('date', $today->toDateString())
            ->first();

        $onLeaveToday = false;
        if (\Illuminate\Support\Facades\Schema::hasTable('leaves')) {
            $onLeaveToday = \App\Models\Leave::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'pending Apron', 'pending Bge', 'approved'])
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', '>=', $today->toDateString())
                ->exists();
        }

        $hasFaceSamples = \App\Http\Controllers\FaceSampleController::isComplete($user->id);
        $strictMode = config('attendance.face_recognition_strict', true);

        return view('attendance.index', compact('todayAttendance', 'todaySchedule', 'user', 'onLeaveToday', 'hasFaceSamples', 'strictMode'));
    }

    public function camera(Request $request)
    {
        $type = $request->query('type', 'in'); // in / out
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->first();

        if ($type === 'out') {
            if (!$todayAttendance) {
                return redirect()->route('attendance.index')
                    ->with('error', 'Anda belum check-in hari ini. Silakan check-in terlebih dahulu.');
            }
            if ($todayAttendance->check_out_time) {
                return redirect()->route('attendance.index')
                    ->with('error', 'Anda sudah check-out hari ini.');
            }
        } else {
            if ($todayAttendance) {
                return redirect()->route('attendance.index')
                    ->with('error', 'Anda sudah check-in hari ini.');
            }
        }

        $hasFaceSamples = \App\Http\Controllers\FaceSampleController::isComplete($user->id);
        $strictMode = config('attendance.face_recognition_strict', true);

        return view('attendance.camera', compact('type', 'hasFaceSamples', 'strictMode'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
            'type' => 'required|in:in,out',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = Auth::user();
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        if (\Illuminate\Support\Facades\Schema::hasTable('leaves')) {
            $hasLeave = \App\Models\Leave::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'pending Apron', 'pending Bge', 'approved'])
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($hasLeave) {
                return back()->with('error', 'Absensi gagal! Anda tidak dapat melakukan absensi karena sedang dalam masa cuti.');
            }
        }

        $strictMode = config('attendance.face_recognition_strict', true);
        $hasFaceSamples = \App\Http\Controllers\FaceSampleController::isComplete($user->id);

        if ($strictMode) {
            if (!$hasFaceSamples) {
                return back()->with('error', 'Absensi diblokir! Konfigurasi FACE_RECOGNITION_STRICT aktif dan Anda belum menyelesaikan registrasi 3 foto referensi wajah NIP.');
            }

            // Server-side strict face verification on submit
            $faceSampleController = new \App\Http\Controllers\FaceSampleController();
            $verifyReq = \Illuminate\Http\Request::create('/attendance/face-verify', 'POST', ['live_b64' => $request->photo]);
            $verifyRes = $faceSampleController->verifyFace($verifyReq)->getData(true);

            if (empty($verifyRes['matched']) || $verifyRes['matched'] !== true) {
                $matchPct = $verifyRes['match_pct'] ?? 0;
                $errMessage = $verifyRes['error'] ?? 'Wajah pada foto tidak cocok dengan foto referensi terdaftar NIP Anda.';
                return back()->with('error', 'Absensi Ditolak! Verifikasi Wajah Gagal: ' . $errMessage . ' (Tingkat kemiripan: ' . $matchPct . '%)');
            }
        }

        /*
    |--------------------------------------------------------------------------
    | 1️⃣ VALIDASI GEOFENCING
    |--------------------------------------------------------------------------
    */

        $station = Station::where('code', $user->station)->first();

        if (!$station) {
            return back()->with('error', 'Station tidak ditemukan.');
        }

        if (!$station->is_active) {
            return back()->with('error', 'Station Anda sedang dinonaktifkan.');
        }

        if (!$station->isRoleAllowed($user)) {
            return back()->with('error', "Role {$user->role} belum terdaftar untuk melakukan presensi di Station {$station->name} ({$station->code}).");
        }

        $targetLat = $station->latitude;
        $targetLong = $station->longitude;

        $allowedRadius = $station->radius ?? config('locations.radius', 40);

        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $targetLat,
            $targetLong
        );

        if ($distance > $allowedRadius) {
            return back()->with(
                'error',
                "Anda berada di luar radius {$station->name}. Jarak: "
                    . round($distance) . " meter"
            );
        }

        /*
    |--------------------------------------------------------------------------
    | 2️⃣ SIMPAN FOTO
    |--------------------------------------------------------------------------
    */

        $photoInput = $request->photo;
        $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoInput);
        $photoData = str_replace(' ', '+', $photoData);
        $decodedData = base64_decode($photoData);

        $fileName = 'attendance_' . $user->id . '_' . time() . '.jpg';

        if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
            $image = @imagecreatefromstring($decodedData);
            if ($image !== false) {
                ob_start();
                imagejpeg($image, null, 70);
                $compressedBinary = ob_get_clean();
                if (!empty($compressedBinary)) {
                    $decodedData = $compressedBinary;
                }
                imagedestroy($image);
            }
        }

        Storage::disk('public')->put(
            'attendance/' . $fileName,
            $decodedData
        );

        /*
    |--------------------------------------------------------------------------
    | 3️⃣ CHECK IN / CHECK OUT
    |--------------------------------------------------------------------------
    */

        if ($request->type === 'in') {

            $existing = Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', $today)
                ->first();

            if ($existing) {
                return back()->with('error', 'Anda sudah check-in hari ini.');
            }

            $schedule = Schedule::with('shift')
                ->where('user_id', $user->id)
                ->where('date', $today)
                ->first();

            if ($schedule && $schedule->shift) {
                $shiftStartTime = Carbon::parse($today . ' ' . $schedule->shift->start_time);
                $toleranceMinutes = $schedule->shift->tolerance_minutes ?? 15;
                $deadline = (clone $shiftStartTime)->addMinutes($toleranceMinutes);

                if ($now->isAfter($deadline)) {
                    return redirect()->route('attendance.corrections.create', ['date' => $today])
                        ->with('error', "Anda terlambat melakukan check-in (Batas toleransi: {$toleranceMinutes} menit). Absensi biasa diblokir, silakan ajukan Koreksi Absen.");
                }
            }

            $status = ($schedule && $schedule->shift && $now->isAfter(Carbon::parse($today . ' ' . $schedule->shift->start_time))) ? 'Terlambat' : 'Tepat Waktu';

            Attendance::create([
                'user_id' => $user->id,
                'station_id' => $station->id,
                'check_in_time' => $now,
                'check_in_photo' => $fileName,
                'check_in_latitude' => $request->latitude,
                'check_in_longitude' => $request->longitude,
                'status' => $status,
            ]);
        } else {

            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('check_in_time', $today)
                ->first();

            if (!$attendance) {
                return back()->with('error', 'Belum check-in hari ini.');
            }

            if ($attendance->check_out_time) {
                return back()->with('error', 'Anda sudah check-out hari ini.');
            }

            $attendance->update([
                'check_out_time' => $now,
                'check_out_photo' => $fileName,
                'check_out_latitude' => $request->latitude,
                'check_out_longitude' => $request->longitude,
            ]);
        }

        return redirect()->route('attendance.index')
            ->with('success', 'Absensi berhasil!');
    }

    // =========================================================================
    // BAGIAN INI YANG SAYA PERBAIKI AGAR GPS AKURAT & SESUAI STATION USER
    // =========================================================================
    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $user = Auth::user();
        $now = Carbon::now();
        $today = $now->format('Y-m-d');

        if (\Illuminate\Support\Facades\Schema::hasTable('leaves')) {
            $hasLeave = \App\Models\Leave::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'pending Apron', 'pending Bge', 'approved'])
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($hasLeave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Absensi gagal! Anda tidak dapat melakukan absensi karena sedang dalam masa cuti.'
                ]);
            }
        }

        // 1. Ambil Station User dari Database
        $station = Station::where('code', $user->station)->first();

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => "Lokasi untuk station '{$user->station}' tidak ditemukan di database."
            ]);
        }

        if (!$station->is_active) {
            return response()->json([
                'success' => false,
                'message' => "Station '{$station->name}' sedang dinonaktifkan."
            ]);
        }

        if (!$station->isRoleAllowed($user)) {
            return response()->json([
                'success' => false,
                'message' => "Role {$user->role} belum terdaftar untuk melakukan presensi di Station {$station->name} ({$station->code})."
            ]);
        }

        // 2. Cek apakah sudah absen hari ini
        $existingCheckIn = Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $today)
            ->first();

        if ($existingCheckIn) {
            return response()->json(['success' => false, 'message' => 'Anda sudah melakukan Check-in hari ini.']);
        }

        // Ambil Koordinat Target & Radius
        $targetLat = $station->latitude;
        $targetLon = $station->longitude;
        $locationName = $station->name;
        $allowedRadius = $station->radius ?? config('locations.radius', 40);

        // 4. Hitung Jarak (GPS HP User vs GPS Kantor)
        $userLat = $request->latitude;
        $userLon = $request->longitude;

        $distance = $this->calculateDistance($userLat, $userLon, $targetLat, $targetLon);

        // 5. Validasi Radius (Geofencing)
        if ($distance > $allowedRadius) {
            return response()->json([
                'success' => false,
                'message' => "Absensi Gagal! Anda berada di luar radius {$locationName}. Jarak: " . round($distance) . " meter (Maks: {$allowedRadius}m).",
            ]);
        }

        // 6. Cek Jadwal Shift
        $schedule = Schedule::with('shift')
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$schedule || !$schedule->shift) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki jadwal shift untuk hari ini.']);
        }

        $shiftStartTime = Carbon::parse($schedule->date . ' ' . $schedule->shift->start_time);
        $toleranceMinutes = $schedule->shift->tolerance_minutes ?? 15;
        $deadline = (clone $shiftStartTime)->addMinutes($toleranceMinutes);

        if ($now->isAfter($deadline)) {
            return response()->json([
                'success' => false,
                'is_late_blocked' => true,
                'redirect_url' => route('attendance.corrections.create', ['date' => $today]),
                'message' => "Anda terlambat melakukan check-in (Batas toleransi: {$toleranceMinutes} menit). Check-in diblokir, silakan ajukan Koreksi Absen."
            ]);
        }

        $status = ($now->isAfter($shiftStartTime)) ? 'Terlambat' : 'Tepat Waktu';

        // 7. Simpan Data Absensi dengan Koordinat
        try {
            Attendance::create([
                'user_id' => $user->id,
                'station_id' => $station->id,
                'check_in_time' => $now,
                'check_in_ip' => $request->ip(),
                'check_in_latitude' => $userLat,
                'check_in_longitude' => $userLon,
                'status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Check-in berhasil di {$locationName}! Status: {$status}.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data ke database.']);
        }
    }

    /**
     * @return float
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Radius Bumi (Meter)

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    // =========================================================================
    // FUNGSI DI BAWAH INI TIDAK SAYA UBAH (SAMA SEPERTI ASLINYA)
    // =========================================================================

    public function history(Request $request)
    {
        $user = Auth::user();

        // Ambil bulan dari request, default bulan ini (format YYYY-MM)
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        // Cek apakah bulan yang dipilih masih boleh diedit (Maksimal 1 bulan mundur dari bulan berjalan)
        $currentMonth = Carbon::now()->startOfMonth();
        $selectedMonthDate = Carbon::parse($month)->startOfMonth();
        $monthsDiff = $selectedMonthDate->diffInMonths($currentMonth, false);

        // Boleh edit jika $selectedMonthDate <= $currentMonth dan beda bulan <= 1 (yaitu 0 bulan ini, atau 1 bulan sebelumnya)
        $canEditMonth = ($selectedMonthDate <= $currentMonth && $monthsDiff <= 1);

        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        // Ambil semua schedule user beserta data shift untuk sebulan
        $scheduleData = Schedule::where('user_id', $user->id)
            ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
            ->selectRaw('schedules.*, shifts.description as shift_description, shifts.start_time, shifts.end_time')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->date)->toDateString()); // keyBy tanggal YYYY-MM-DD

        // Ambil semua absensi user untuk sebulan
        $attendanceData = Attendance::with('station')
            ->where('user_id', $user->id)
            ->whereBetween('check_in_time', [$startDate->toDateString() . ' 00:00:00', $endDate->toDateString() . ' 23:59:59'])
            ->get()
            ->keyBy(fn($item) => Carbon::parse($item->check_in_time)->toDateString()); // keyBy tanggal YYYY-MM-DD

        $correctionData = AttendanceCorrection::where('user_id', $user->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn ($item) => $item->attendance_date->toDateString());

        // Siapkan data per hari untuk Blade
        $daysInMonth = [];
        for ($day = 1; $day <= $startDate->daysInMonth; $day++) {
            $dateString = $startDate->copy()->day($day)->toDateString();

            $schedule = $scheduleData[$dateString] ?? null;   // Aman, tidak ada undefined key
            $attendance = $attendanceData[$dateString] ?? null;

            $daysInMonth[$day] = [
                'schedule' => $schedule,
                'attendance' => $attendance,
                'correction' => $correctionData[$dateString] ?? null,
            ];
        }

        return view('attendance.history', compact('daysInMonth', 'month', 'user', 'canEditMonth'));
    }

    public function reportsIndex(Request $request)
    {
        $authUser = Auth::user();
        $isFullAccess = $authUser->hasRole(['Admin', 'Head Of Airport Service']) || ($authUser->station === 'Ho');

        $attendances = collect();
        $message = null;

        if ($isFullAccess) {
            $stations = Station::where('is_active', 1)->get();
        } else {
            $stations = Station::where('is_active', 1)->where('code', $authUser->station)->get();
        }

        $selectedMonth = $request->filled('month') ? $request->input('month') : Carbon::now()->format('Y-m');

        if ($selectedMonth) {
            try {
                $period = \Carbon\Carbon::parse($selectedMonth . '-01');
                $startDate = $period->copy()->startOfMonth();
                $endDate = $period->copy()->endOfMonth();

                // ===== QUERY USER (GABUNG SEMUA FILTER) =====
                $queryUser = \App\Models\User::query();

                if ($request->filled('user_name')) {
                    $queryUser->where(function ($q) use ($request) {
                        $q->where('id', $request->user_name)
                            ->orWhere('fullname', 'LIKE', "%{$request->user_name}%");
                    });
                }

                if ($request->filled('station_id')) {
                    $queryUser->where('station', $request->station_id);
                } elseif (!$isFullAccess && $authUser->station) {
                    $queryUser->where('station', $authUser->station);
                }

                if ($request->filled('role')) {
                    $queryUser->whereHas('roleRelation', function ($rq) use ($request) {
                        $rq->where('name', $request->role);
                    });
                }

                if (! $request->filled('user_name') && ! $request->filled('station_id') && ! $request->filled('role')) {
                    $user = Auth::user();
                } else {
                    $user = $queryUser->first();
                }

                // ===== VALIDASI USER =====
                if (! $user) {
                    $message = 'Data karyawan tidak ditemukan sesuai filter.';
                } else {

                    // ===== ATTENDANCE =====
                    $attData = \App\Models\Attendance::with('station')
                        ->where('user_id', $user->id)
                        ->whereBetween('check_in_time', [
                            $startDate->copy()->startOfDay(),
                            $endDate->copy()->endOfDay()
                        ])
                        ->get()
                        ->groupBy(fn($att) => \Carbon\Carbon::parse($att->check_in_time)->toDateString());

                    // ===== SCHEDULE =====
                    $scheduleData = \App\Models\Schedule::where('user_id', $user->id)
                        ->selectRaw('schedules.*, shifts.description as shift_description, shifts.start_time, shifts.end_time')
                        ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
                        ->whereBetween('date', [
                            $startDate->toDateString(),
                            $endDate->toDateString()
                        ])
                        ->get()
                        ->groupBy(fn($item) => \Carbon\Carbon::parse($item->date)->toDateString());

                    // ===== LEAVE (CUTI) DATA =====
                    $leaveData = Leave::where('user_id', $user->id)
                        ->where('status', 'approved')
                        ->where(function ($q) use ($startDate, $endDate) {
                            $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                              ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                              ->orWhere(function ($q2) use ($startDate, $endDate) {
                                  $q2->where('start_date', '<=', $startDate->toDateString())
                                     ->where('end_date', '>=', $endDate->toDateString());
                              });
                        })
                        ->get();

                    // ===== CORRECTION DATA =====
                    $correctionData = \App\Models\AttendanceCorrection::where('user_id', $user->id)
                        ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->get()
                        ->keyBy(fn ($item) => $item->attendance_date->toDateString());

                    // ===== GENERATE DATA =====
                    $cursor = $startDate->copy();

                    while ($cursor->lte($endDate)) {
                        $dateStr = $cursor->toDateString();

                        $attendancesForDate = $attData->get($dateStr) ?? collect();
                        $schedulesForDate = $scheduleData->get($dateStr) ?? collect();
                        $leaveForDate = $leaveData->first(fn($leave) => 
                            \Carbon\Carbon::parse($dateStr)->between(
                                \Carbon\Carbon::parse($leave->start_date),
                                \Carbon\Carbon::parse($leave->end_date)
                            )
                        );
                        $correctionForDate = $correctionData->get($dateStr);

                        if ($schedulesForDate->isEmpty()) {
                            $attendances->push((object) [
                                'date'       => $dateStr,
                                'attendance' => $attendancesForDate->first(),
                                'schedule'   => null,
                                'user'       => $user,
                                'leave'      => $leaveForDate,
                                'correction' => $correctionForDate,
                            ]);
                        } else {
                            foreach ($schedulesForDate as $schedule) {
                                $attendances->push((object) [
                                    'date'       => $dateStr,
                                    'attendance' => $attendancesForDate->first(),
                                    'schedule'   => $schedule,
                                    'user'       => $user,
                                    'leave'      => $leaveForDate,
                                    'correction' => $correctionForDate,
                                ]);
                            }
                        }

                        $cursor->addDay();
                    }
                }
            } catch (\Exception $e) {
                $message = "Format periode tidak valid.";
            }
        }

        $roles = [
            'Admin', 'Finance', 'Leader Bge', 'SPV Bge', 'SPV Apron', 'Leader Apron',
            'Porter Bge', 'HSE', 'Head Of Airport Service', 'Porter Apron', 'Ass Leader Apron',
            'Dispatcher', 'Ass Leader Bge', 'Driver', 'Aircraft Interior Exterior Cleaning',
            'Leader Aircraft Interior Exterior Cleaning', 'Leader Porter Apron', 'Controller', 'Quality Control'
        ];

        $userStation = !$isFullAccess ? $authUser->station : null;

        return view('attendance.report', compact('attendances', 'message', 'stations', 'roles', 'isFullAccess', 'userStation'));
    }

    public function export(Request $request)
    {
        $authUser = Auth::user();
        $isFullAccess = $authUser->hasRole(['Admin', 'Head Of Airport Service']) || ($authUser->station === 'Ho');

        $month = $request->input('month', date('Y-m'));
        if (empty($month)) {
            $month = date('Y-m');
        }

        try {
            // ===== PARSE PERIODE =====
            $period = \Carbon\Carbon::parse($month . '-01');
            $startDate = $period->copy()->startOfMonth();
            $endDate = $period->copy()->endOfMonth();

            // ===== QUERY USER (GABUNG SEMUA FILTER) =====
            $queryUser = \App\Models\User::query();

            if ($request->filled('user_name')) {
                $queryUser->where(function ($q) use ($request) {
                    $q->where('id', $request->user_name)
                        ->orWhere('fullname', 'LIKE', "%{$request->user_name}%");
                });
            }

            if ($request->filled('station_id')) {
                $queryUser->where('station', $request->station_id);
            } elseif (!$isFullAccess && $authUser->station) {
                $queryUser->where('station', $authUser->station);
            }

            if ($request->filled('role')) {
                $queryUser->whereHas('roleRelation', function ($rq) use ($request) {
                    $rq->where('name', $request->role);
                });
            }

            if (! $request->filled('user_name') && ! $request->filled('station_id') && ! $request->filled('role')) {
                $users = collect([$authUser]);
            } else {
                $users = $queryUser->orderBy('fullname')->get();
            }

            // ===== AMBIL INFO STATION UNTUK FILENAME =====
            $stationName = 'Semua_Station';
            if ($request->filled('station_id')) {
                $st = \App\Models\Station::where('code', $request->station_id)->first();
                if ($st) $stationName = str_replace(' ', '_', $st->name);
            } elseif (!$isFullAccess && $authUser->station) {
                $st = \App\Models\Station::where('code', $authUser->station)->first();
                if ($st) $stationName = str_replace(' ', '_', $st->name);
            }

            $attendances = collect();

            // ===== LOOP SEMUA USER =====
            foreach ($users as $user) {

                // ATTENDANCE
                $attData = \App\Models\Attendance::with('station')
                    ->where('user_id', $user->id)
                    ->whereBetween('check_in_time', [
                        $startDate->copy()->startOfDay(),
                        $endDate->copy()->endOfDay()
                    ])
                    ->get()
                    ->groupBy(fn($att) => \Carbon\Carbon::parse($att->check_in_time)->toDateString());

                // SCHEDULE
                $scheduleData = \App\Models\Schedule::where('user_id', $user->id)
                    ->selectRaw('schedules.*, shifts.description as shift_description, shifts.start_time, shifts.end_time')
                    ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
                    ->whereBetween('date', [
                        $startDate->toDateString(),
                        $endDate->toDateString()
                    ])
                    ->get()
                    ->groupBy(fn($item) => \Carbon\Carbon::parse($item->date)->toDateString());

                // LEAVE DATA
                $leaveData = Leave::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                          ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                          ->orWhere(function ($q2) use ($startDate, $endDate) {
                              $q2->where('start_date', '<=', $startDate->toDateString())
                                 ->where('end_date', '>=', $endDate->toDateString());
                          });
                    })
                    ->get();

                $leaveDates = collect();
                foreach ($leaveData as $leave) {
                    $leaveStart = Carbon::parse($leave->start_date);
                    $leaveEnd   = Carbon::parse($leave->end_date);
                    $leaveCursor = $leaveStart->copy();
                    while ($leaveCursor->lte($leaveEnd)) {
                        $leaveDates->put($leaveCursor->toDateString(), $leave);
                        $leaveCursor->addDay();
                    }
                }

                // CORRECTION DATA
                $correctionData = \App\Models\AttendanceCorrection::where('user_id', $user->id)
                    ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get()
                    ->keyBy(fn ($item) => $item->attendance_date->toDateString());

                // LOOP TANGGAL
                $cursor = $startDate->copy();

                while ($cursor->lte($endDate)) {
                    $dateStr = $cursor->toDateString();

                    $attendancesForDate = $attData->get($dateStr) ?? collect();
                    $schedulesForDate = $scheduleData->get($dateStr) ?? collect();
                    $leaveForDate = $leaveDates->get($dateStr);
                    $correctionForDate = $correctionData->get($dateStr);

                    if ($schedulesForDate->isEmpty()) {
                        $attendances->push((object) [
                            'date'       => $dateStr,
                            'attendance' => $attendancesForDate->first(),
                            'schedule'   => null,
                            'user'       => $user,
                            'leave'      => $leaveForDate,
                            'correction' => $correctionForDate,
                        ]);
                    } else {
                        foreach ($schedulesForDate as $schedule) {
                            $attendances->push((object) [
                                'date'       => $dateStr,
                                'attendance' => $attendancesForDate->first(),
                                'schedule'   => $schedule,
                                'user'       => $user,
                                'leave'      => $leaveForDate,
                                'correction' => $correctionForDate,
                            ]);
                        }
                    }

                    $cursor->addDay();
                }
            }

            // ===== SORTING =====
            $attendances = $attendances->sortBy([
                ['user.fullname', 'asc'],
                ['date', 'asc'],
            ])->values();

            // ===== FILE NAME =====
            $exportName = $users->count() === 1 ? str_replace(' ', '_', $users->first()->fullname) : $stationName;
            $fileName = 'Laporan_Absensi_' . $exportName . '_' . $request->month . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new AttendanceReportExport($attendances),
                $fileName
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export: ' . $e->getMessage());
        }
    }
}
