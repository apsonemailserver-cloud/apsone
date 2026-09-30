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
                // Resize ke max-width 640px untuk efisiensi penyimpanan foto log absensi
                $origW = imagesx($image);
                $origH = imagesy($image);
                $maxW = 640;
                if ($origW > $maxW) {
                    $newW = $maxW;
                    $newH = (int) round(($origH * $maxW) / $origW);
                    $resized = imagecreatetruecolor($newW, $newH);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                    imagedestroy($image);
                    $image = $resized;
                }

                ob_start();
                imagejpeg($image, null, 65); // Kompresi 65% untuk efisiensi penyimpanan log harian
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
                $toleranceMinutes = 15;
                $deadline = (clone $shiftStartTime)->addMinutes($toleranceMinutes);

                if ($now->isAfter($deadline)) {
                    return redirect()->route('attendance.corrections.create', ['date' => $today])
                        ->with('error', "Anda terlambat melakukan check-in (Batas toleransi: {$toleranceMinutes} menit). Absensi biasa diblokir, silakan ajukan Koreksi Absen.");
                }
            }

            $status = ($schedule && $schedule->shift && $now->isAfter(Carbon::parse($today . ' ' . $schedule->shift->start_time))) ? 'Terlambat' : 'Tepat Waktu';

            Attendance::create([
                'user_id' => $user->id,
                'station' => $station->code,
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
        $toleranceMinutes = 15;
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
                'station' => $station->code,
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

        $employeeReports = collect();
        $paginatedUsers = collect();
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
                $period = Carbon::parse($selectedMonth . '-01');
                $startDate = $period->copy()->startOfMonth();
                $endDate = $period->copy()->endOfMonth();
                $today = Carbon::today();

                // ===== QUERY USER (GABUNG SEMUA FILTER) =====
                $queryUser = User::with(['employee', 'roleRelation'])
                    ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
                    ->select('users.*');

                if ($request->filled('user_name')) {
                    $queryUser->where(function ($q) use ($request) {
                        $q->where('users.id', $request->user_name)
                            ->orWhere('employees.fullname', 'LIKE', "%{$request->user_name}%");
                    });
                } else {
                    // Ambil karyawan aktif atau yang memiliki rekam absensi pada periode ini
                    $queryUser->where(function ($q) use ($startDate, $endDate) {
                        $q->where('users.is_active', 1)
                          ->orWhereExists(function ($sq) use ($startDate, $endDate) {
                              $sq->select(\Illuminate\Support\Facades\DB::raw(1))
                                 ->from('attendances')
                                 ->whereColumn('attendances.user_id', 'users.id')
                                 ->whereBetween('check_in_time', [
                                     $startDate->copy()->startOfDay(),
                                     $endDate->copy()->endOfDay()
                                 ]);
                          });
                    });
                }

                if ($request->filled('station_id')) {
                    $queryUser->where('employees.station_id', $request->station_id);
                } elseif (!$isFullAccess && $authUser->station) {
                    $queryUser->where('employees.station_id', $authUser->station);
                }

                if ($request->filled('role')) {
                    $queryUser->whereHas('roleRelation', function ($rq) use ($request) {
                        $rq->where('name', $request->role);
                    });
                }

                $perPage = $request->input('per_page', 5);
                $paginatedUsers = $queryUser->orderBy('employees.fullname', 'asc')
                    ->paginate($perPage)
                    ->withQueryString();

                // ===== VALIDASI USER =====
                if ($paginatedUsers->isEmpty()) {
                    $message = 'Data karyawan tidak ditemukan sesuai filter.';
                } else {
                    $userIds = $paginatedUsers->pluck('id')->toArray();

                    // Bulk fetch Attendances
                    $allAttendances = Attendance::with('station')
                        ->whereIn('user_id', $userIds)
                        ->whereBetween('check_in_time', [
                            $startDate->copy()->startOfDay(),
                            $endDate->copy()->endOfDay()
                        ])
                        ->get()
                        ->groupBy('user_id');

                    // Bulk fetch Schedules
                    $allSchedules = Schedule::whereIn('user_id', $userIds)
                        ->selectRaw('schedules.*, shifts.description as shift_description, shifts.start_time, shifts.end_time')
                        ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
                        ->whereBetween('date', [
                            $startDate->toDateString(),
                            $endDate->toDateString()
                        ])
                        ->get()
                        ->groupBy('user_id');

                    // Bulk fetch Leaves
                    $allLeaves = Leave::whereIn('user_id', $userIds)
                        ->where('status', 'approved')
                        ->where(function ($q) use ($startDate, $endDate) {
                            $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                              ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                              ->orWhere(function ($q2) use ($startDate, $endDate) {
                                  $q2->where('start_date', '<=', $startDate->toDateString())
                                     ->where('end_date', '>=', $endDate->toDateString());
                              });
                        })
                        ->get()
                        ->groupBy('user_id');

                    // Bulk fetch Corrections
                    $allCorrections = AttendanceCorrection::whereIn('user_id', $userIds)
                        ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->get()
                        ->groupBy('user_id');

                    // Bangun riwayat rekap per karyawan: Si A 1 bulan, Si B 1 bulan, Si C 1 bulan
                    foreach ($paginatedUsers as $user) {
                        $userAtts = $allAttendances->get($user->id, collect())
                            ->groupBy(fn($att) => Carbon::parse($att->check_in_time)->toDateString());

                        $userScheds = $allSchedules->get($user->id, collect())
                            ->groupBy(fn($item) => Carbon::parse($item->date)->toDateString());

                        $userLeaves = $allLeaves->get($user->id, collect());
                        $leaveDates = collect();
                        foreach ($userLeaves as $leave) {
                            $leaveStart = Carbon::parse($leave->start_date);
                            $leaveEnd   = Carbon::parse($leave->end_date);
                            $leaveCursor = $leaveStart->copy();
                            while ($leaveCursor->lte($leaveEnd)) {
                                $leaveDates->put($leaveCursor->toDateString(), $leave);
                                $leaveCursor->addDay();
                            }
                        }

                        $userCorrections = $allCorrections->get($user->id, collect())
                            ->keyBy(fn ($item) => Carbon::parse($item->attendance_date)->toDateString());

                        $userRecords = collect();
                        $summary = [
                            'hadir'       => 0,
                            'tepat_waktu' => 0,
                            'telat'       => 0,
                            'cuti'        => 0,
                            'koreksi'     => 0,
                            'libur'       => 0,
                            'tidak_hadir' => 0,
                        ];

                        $cursor = $startDate->copy();
                        while ($cursor->lte($endDate)) {
                            $dateStr = $cursor->toDateString();

                            $attendancesForDate = $userAtts->get($dateStr) ?? collect();
                            $schedulesForDate   = $userScheds->get($dateStr) ?? collect();
                            $leaveForDate       = $leaveDates->get($dateStr);
                            $correctionForDate  = $userCorrections->get($dateStr);

                            $record = (object) [
                                'date'       => $dateStr,
                                'attendance' => $attendancesForDate->first(),
                                'schedule'   => $schedulesForDate->first(),
                                'user'       => $user,
                                'leave'      => $leaveForDate,
                                'correction' => $correctionForDate,
                            ];

                            // Hitung status untuk ringkasan karyawan
                            $hasAttendance = $record->attendance !== null;
                            $hasApprovedCorrection = $record->correction && $record->correction->status === 'approved';

                            if ($record->leave) {
                                $summary['cuti']++;
                            } elseif ($hasAttendance || $hasApprovedCorrection) {
                                $summary['hadir']++;
                                if ($hasApprovedCorrection) {
                                    $summary['koreksi']++;
                                }

                                $checkIn = $hasAttendance && $record->attendance->check_in_time ? Carbon::parse($record->attendance->check_in_time) : null;
                                if ($hasApprovedCorrection) {
                                    $checkIn = Carbon::parse($record->correction->proposed_check_in_time);
                                }

                                if ($checkIn && $record->schedule && !empty($record->schedule->start_time)) {
                                    $schedStart = Carbon::parse($dateStr . ' ' . $record->schedule->start_time);
                                    if ($checkIn->gt($schedStart)) {
                                        $summary['telat']++;
                                    } else {
                                        $summary['tepat_waktu']++;
                                    }
                                } elseif ($hasAttendance && $record->attendance->status === 'Terlambat') {
                                    $summary['telat']++;
                                } else {
                                    $summary['tepat_waktu']++;
                                }
                            } elseif ($cursor->lt($today)) {
                                if ($record->schedule && strtolower($record->schedule->shift_id ?? '') !== 'off') {
                                    $summary['tidak_hadir']++;
                                } else {
                                    $summary['libur']++;
                                }
                            } else {
                                if (!$record->schedule || strtolower($record->schedule->shift_id ?? '') === 'off') {
                                    $summary['libur']++;
                                }
                            }

                            $userRecords->push($record);
                            $cursor->addDay();
                        }

                        $employeeReports->push((object) [
                            'user'        => $user,
                            'summary'     => $summary,
                            'attendances' => $userRecords,
                        ]);
                    }

                    $attendances = $employeeReports->first()?->attendances ?? collect();
                }
            } catch (\Exception $e) {
                $message = "Format periode tidak valid: " . $e->getMessage();
            }
        }

        $roles = [
            'Admin', 'Finance', 'Leader Bge', 'SPV Bge', 'SPV Apron', 'Leader Apron',
            'Porter Bge', 'HSE', 'Head Of Airport Service', 'Porter Apron', 'Ass Leader Apron',
            'Dispatcher', 'Ass Leader Bge', 'Driver', 'Aircraft Interior Exterior Cleaning',
            'Leader Aircraft Interior Exterior Cleaning', 'Leader Porter Apron', 'Controller', 'Quality Control'
        ];

        $userStation = !$isFullAccess ? $authUser->station : null;

        return view('attendance.report', compact(
            'employeeReports',
            'paginatedUsers',
            'attendances',
            'message',
            'stations',
            'roles',
            'isFullAccess',
            'userStation',
            'selectedMonth'
        ));
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
            $queryUser = User::with(['employee', 'roleRelation'])
                ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
                ->select('users.*');

            if ($request->filled('user_name')) {
                $queryUser->where(function ($q) use ($request) {
                    $q->where('users.id', $request->user_name)
                        ->orWhere('employees.fullname', 'LIKE', "%{$request->user_name}%");
                });
            } else {
                // Jika tidak mencari user spesifik, sertakan karyawan aktif atau yang memiliki rekam absensi pada periode ini
                $queryUser->where(function ($q) use ($startDate, $endDate) {
                    $q->where('users.is_active', 1)
                      ->orWhereExists(function ($sq) use ($startDate, $endDate) {
                          $sq->select(\Illuminate\Support\Facades\DB::raw(1))
                             ->from('attendances')
                             ->whereColumn('attendances.user_id', 'users.id')
                             ->whereBetween('check_in_time', [
                                 $startDate->copy()->startOfDay(),
                                 $endDate->copy()->endOfDay()
                             ]);
                      });
                });
            }

            if ($request->filled('station_id')) {
                $queryUser->where('employees.station_id', $request->station_id);
            } elseif (!$isFullAccess && $authUser->station) {
                $queryUser->where('employees.station_id', $authUser->station);
            }

            if ($request->filled('role')) {
                $queryUser->whereHas('roleRelation', function ($rq) use ($request) {
                    $rq->where('name', $request->role);
                });
            }

            $users = $queryUser->orderBy('employees.fullname', 'asc')->get();

            if ($users->isEmpty()) {
                return redirect()->back()->with('warning', 'Tidak ada data karyawan yang sesuai kriteria untuk diexport.');
            }

            // ===== AMBIL INFO STATION UNTUK FILENAME =====
            $stationName = 'Semua_Station';
            if ($request->filled('station_id')) {
                $st = Station::where('code', $request->station_id)->first();
                if ($st) $stationName = str_replace(' ', '_', $st->name);
            } elseif (!$isFullAccess && $authUser->station) {
                $st = Station::where('code', $authUser->station)->first();
                if ($st) $stationName = str_replace(' ', '_', $st->name);
            }

            $userIds = $users->pluck('id')->toArray();

            // Bulk fetch Attendances untuk mencegah N+1 query
            $allAttendances = Attendance::with('station')
                ->whereIn('user_id', $userIds)
                ->whereBetween('check_in_time', [
                    $startDate->copy()->startOfDay(),
                    $endDate->copy()->endOfDay()
                ])
                ->get()
                ->groupBy('user_id');

            // Bulk fetch Schedules dengan Shift
            $allSchedules = Schedule::whereIn('user_id', $userIds)
                ->selectRaw('schedules.*, shifts.description as shift_description, shifts.start_time, shifts.end_time')
                ->join('shifts', 'shifts.id', '=', 'schedules.shift_id')
                ->whereBetween('date', [
                    $startDate->toDateString(),
                    $endDate->toDateString()
                ])
                ->get()
                ->groupBy('user_id');

            // Bulk fetch Approved Leaves
            $allLeaves = Leave::whereIn('user_id', $userIds)
                ->where('status', 'approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                      ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<=', $startDate->toDateString())
                             ->where('end_date', '>=', $endDate->toDateString());
                      });
                })
                ->get()
                ->groupBy('user_id');

            // Bulk fetch Corrections
            $allCorrections = AttendanceCorrection::whereIn('user_id', $userIds)
                ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->groupBy('user_id');

            $matrixData = collect();
            $attendances = collect();
            $today = Carbon::today();
            $daysInMonth = $startDate->daysInMonth;

            // ===== LOOP SEMUA USER =====
            foreach ($users as $user) {
                $userAtts = $allAttendances->get($user->id, collect())
                    ->groupBy(fn($att) => Carbon::parse($att->check_in_time)->toDateString());

                $userScheds = $allSchedules->get($user->id, collect())
                    ->groupBy(fn($item) => Carbon::parse($item->date)->toDateString());

                $userLeaves = $allLeaves->get($user->id, collect());
                $leaveDates = collect();
                foreach ($userLeaves as $leave) {
                    $leaveStart = Carbon::parse($leave->start_date);
                    $leaveEnd   = Carbon::parse($leave->end_date);
                    $leaveCursor = $leaveStart->copy();
                    while ($leaveCursor->lte($leaveEnd)) {
                        $leaveDates->put($leaveCursor->toDateString(), $leave);
                        $leaveCursor->addDay();
                    }
                }

                $userCorrections = $allCorrections->get($user->id, collect())
                    ->keyBy(fn ($item) => Carbon::parse($item->attendance_date)->toDateString());

                $userDays = [];
                $summary = [
                    'hadir'       => 0,
                    'tepat_waktu' => 0,
                    'telat'       => 0,
                    'cuti'        => 0,
                    'libur'       => 0,
                    'tidak_hadir' => 0,
                ];

                // LOOP TANGGAL 1 s/d daysInMonth
                $cursor = $startDate->copy();
                while ($cursor->lte($endDate)) {
                    $dateStr = $cursor->toDateString();
                    $dayNum = $cursor->day;

                    $attendancesForDate = $userAtts->get($dateStr) ?? collect();
                    $schedulesForDate   = $userScheds->get($dateStr) ?? collect();
                    $leaveForDate       = $leaveDates->get($dateStr);
                    $correctionForDate  = $userCorrections->get($dateStr);

                    $att = $attendancesForDate->first();
                    $sched = $schedulesForDate->first();
                    $leave = $leaveForDate;
                    $corr = $correctionForDate;

                    $hasAttendance = $att !== null;
                    $hasApprovedCorrection = $corr && $corr->status === 'approved';

                    $checkInTime = $hasAttendance && $att->check_in_time ? Carbon::parse($att->check_in_time) : null;
                    $checkOutTime = $hasAttendance && $att->check_out_time ? Carbon::parse($att->check_out_time) : null;
                    if ($hasApprovedCorrection) {
                        $checkInTime = Carbon::parse($corr->proposed_check_in_time);
                        $checkOutTime = Carbon::parse($corr->proposed_check_out_time);
                    }

                    $statusCode = '-';
                    $timeDisplay = '-';
                    $isWeekend = in_array($cursor->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);

                    if ($leave) {
                        $statusCode = 'C';
                        $timeDisplay = 'Cuti';
                        $summary['cuti']++;
                    } elseif ($hasAttendance || $hasApprovedCorrection) {
                        $summary['hadir']++;

                        $isLate = false;
                        if ($checkInTime && $sched && !empty($sched->start_time)) {
                            $schedStart = Carbon::parse($dateStr . ' ' . $sched->start_time);
                            if ($checkInTime->gt($schedStart)) {
                                $isLate = true;
                            }
                        } elseif ($hasAttendance && $att->status === 'Terlambat') {
                            $isLate = true;
                        }

                        if ($isLate) {
                            $statusCode = 'T';
                            $timeDisplay = ($checkInTime ? $checkInTime->format('H:i') : '-') . ' (T)';
                            $summary['telat']++;
                        } else {
                            $statusCode = 'H';
                            $timeDisplay = $checkInTime ? $checkInTime->format('H:i') : 'H';
                            $summary['tepat_waktu']++;
                        }
                    } elseif ($cursor->lt($today)) {
                        if ($sched && strtolower($sched->shift_id ?? '') !== 'off') {
                            $statusCode = 'A';
                            $timeDisplay = 'A';
                            $summary['tidak_hadir']++;
                        } else {
                            $statusCode = 'OFF';
                            $timeDisplay = 'OFF';
                            $summary['libur']++;
                        }
                    } else {
                        if (!$sched || strtolower($sched->shift_id ?? '') === 'off') {
                            $statusCode = 'OFF';
                            $timeDisplay = 'OFF';
                            $summary['libur']++;
                        } else {
                            $statusCode = '-';
                            $timeDisplay = '-';
                        }
                    }

                    $userDays[$dayNum] = [
                        'day'          => $dayNum,
                        'date'         => $dateStr,
                        'day_name'     => $cursor->isoFormat('ddd'),
                        'is_weekend'   => $isWeekend,
                        'status_code'  => $statusCode,
                        'time_display' => $timeDisplay,
                        'check_in'     => $checkInTime ? $checkInTime->format('H:i:s') : '-',
                        'check_out'    => $checkOutTime ? $checkOutTime->format('H:i:s') : '-',
                    ];

                    // Data untuk Sheet 3: Log Detail Presensi
                    if ($schedulesForDate->isEmpty()) {
                        $attendances->push((object) [
                            'date'       => $dateStr,
                            'attendance' => $att,
                            'schedule'   => null,
                            'user'       => $user,
                            'leave'      => $leaveForDate,
                            'correction' => $correctionForDate,
                        ]);
                    } else {
                        foreach ($schedulesForDate as $schedule) {
                            $attendances->push((object) [
                                'date'       => $dateStr,
                                'attendance' => $att,
                                'schedule'   => $schedule,
                                'user'       => $user,
                                'leave'      => $leaveForDate,
                                'correction' => $correctionForDate,
                            ]);
                        }
                    }

                    $cursor->addDay();
                }

                $matrixData->push((object) [
                    'user'    => $user,
                    'days'    => $userDays,
                    'summary' => $summary,
                ]);
            }

            // ===== SORTING DATA DETAIL =====
            $attendances = $attendances->sortBy([
                ['user.fullname', 'asc'],
                ['date', 'asc'],
            ])->values();

            // ===== FILE NAME =====
            $exportName = $users->count() === 1 ? str_replace(' ', '_', $users->first()->fullname) : $stationName;
            $fileName = 'Laporan_Absensi_' . $exportName . '_' . $month . '.xlsx';
            $readableStation = str_replace('_', ' ', $stationName);

            return Excel::download(
                new AttendanceReportExport($matrixData, $attendances, $month, $readableStation),
                $fileName
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat export: ' . $e->getMessage());
        }
    }
}
