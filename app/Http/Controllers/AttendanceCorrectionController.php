<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Station;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use App\Services\RequestNotificationMailService;

class AttendanceCorrectionController extends Controller
{
    public function approval(Request $request)
    {
        $actor = Auth::user();
        $isAdmin = $this->isAdmin($actor);
        $query = AttendanceCorrection::with(['user', 'station', 'attendance'])
            ->where('status', AttendanceCorrection::STATUS_PENDING);

        $rawStation = trim((string) $actor->station);
        $userStations = ($rawStation !== '' && strtoupper($rawStation) !== 'ALL' && strtoupper($rawStation) !== 'SEMUA')
            ? array_filter(array_map('trim', explode(',', $rawStation)))
            : [];

        if (! $isAdmin) {
            $query->whereHas(
                'user',
                fn ($builder) => $builder->where('manager', $actor->fullname)
            );
            if (! empty($userStations)) {
                $query->whereHas('station', fn ($b) => $b->whereIn('code', $userStations));
            }
        } else {
            if (count($userStations) === 1) {
                $singleCode = reset($userStations);
                $query->whereHas('station', fn ($b) => $b->where('code', $singleCode));
            } elseif (count($userStations) > 1) {
                if ($request->filled('station_id')) {
                    $query->where('station_id', $request->integer('station_id'));
                } else {
                    $query->whereHas('station', fn ($b) => $b->whereIn('code', $userStations));
                }
            } else {
                if ($request->filled('station_id')) {
                    $query->where('station_id', $request->integer('station_id'));
                }
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('user', function ($builder) use ($search): void {
                $builder->where('fullname', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $corrections = $query
            ->orderByDesc('attendance_date')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        if ($isAdmin) {
            if (count($userStations) === 1) {
                $stations = collect();
            } elseif (count($userStations) > 1) {
                $stations = Station::where('is_active', true)
                    ->whereIn('code', $userStations)
                    ->orderBy('code')
                    ->get();
            } else {
                $stations = Station::where('is_active', true)
                    ->orderBy('code')
                    ->get();
            }
        } else {
            $stations = collect();
        }

        return view('attendance.corrections.approval', compact('corrections', 'stations', 'isAdmin'));
    }

    public function create(string $date)
    {
        $attendanceDate = $this->validateAttendanceDate($date);

        if ($this->correctionExists(Auth::id(), $attendanceDate)) {
            return redirect()
                ->route('attendance.history')
                ->withErrors(['attendance_date' => 'Koreksi untuk tanggal ini sudah pernah diajukan.']);
        }

        $attendance = Attendance::with('station')
            ->where('user_id', Auth::id())
            ->whereDate('check_in_time', $attendanceDate)
            ->first();

        $stations = Station::where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('attendance.corrections.create', compact(
            'attendanceDate',
            'attendance',
            'stations'
        ));
    }

    public function store(Request $request, string $date)
    {
        $attendanceDate = $this->validateAttendanceDate($date);

        if ($this->correctionExists(Auth::id(), $attendanceDate)) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Koreksi untuk tanggal ini sudah pernah diajukan.',
            ]);
        }

        $validated = $request->validate([
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'station_id' => [
                'required',
                Rule::exists('stations', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $checkIn = Carbon::createFromFormat(
            'Y-m-d H:i',
            "{$attendanceDate} {$validated['check_in_time']}"
        );
        $checkOut = Carbon::createFromFormat(
            'Y-m-d H:i',
            "{$attendanceDate} {$validated['check_out_time']}"
        );

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            $checkOut->addDay();
        }

        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('check_in_time', $attendanceDate)
            ->first();

        try {
            AttendanceCorrection::create([
                'user_id' => Auth::id(),
                'attendance_id' => $attendance?->id,
                'station_id' => $validated['station_id'],
                'attendance_date' => $attendanceDate,
                'proposed_check_in_time' => $checkIn,
                'proposed_check_out_time' => $checkOut,
                'reason' => $validated['reason'],
                'status' => AttendanceCorrection::STATUS_PENDING,
            ]);
        } catch (QueryException $exception) {
            if (in_array((string) ($exception->errorInfo[0] ?? ''), ['19', '23000'], true)) {
                throw ValidationException::withMessages([
                    'attendance_date' => 'Koreksi untuk tanggal ini sudah pernah diajukan.',
                ]);
            }

            throw $exception;
        }

        // Kirim email pemberitahuan ke pemohon (creator)
        RequestNotificationMailService::sendSubmissionEmail(
            Auth::user(),
            'Koreksi Absensi',
            [
                'Tanggal Absen'    => Carbon::parse($attendanceDate)->translatedFormat('d F Y'),
                'Usulan Jam Masuk' => $checkIn->format('H:i'),
                'Usulan Jam Keluar' => $checkOut->format('H:i'),
                'Alasan Koreksi'   => $validated['reason'],
                'Status'           => 'Pending (Menunggu Persetujuan Atasan)',
            ]
        );

        return redirect()
            ->route('attendance.history')
            ->with('success', 'Pengajuan koreksi absensi berhasil dikirim ke atasan.');
    }

    public function approve(AttendanceCorrection $correction)
    {
        DB::transaction(function () use ($correction): void {
            $lockedCorrection = AttendanceCorrection::with('user')
                ->lockForUpdate()
                ->findOrFail($correction->id);

            $this->authorizeDecision($lockedCorrection);
            $this->ensurePending($lockedCorrection);

            $attendanceDate = $lockedCorrection->attendance_date->toDateString();
            $attendance = Attendance::where('user_id', $lockedCorrection->user_id)
                ->whereDate('check_in_time', $attendanceDate)
                ->first();

            $attendanceValues = [
                'station_id' => $lockedCorrection->station_id,
                'check_in_time' => $lockedCorrection->proposed_check_in_time,
                'check_out_time' => $lockedCorrection->proposed_check_out_time,
            ];

            if ($attendance) {
                $attendance->update($attendanceValues);
            } else {
                $attendance = Attendance::create([
                    'user_id' => $lockedCorrection->user_id,
                    ...$attendanceValues,
                ]);
            }

            $lockedCorrection->update([
                'attendance_id' => $attendance->id,
                'status' => AttendanceCorrection::STATUS_APPROVED,
                'decided_by' => Auth::id(),
                'decided_at' => now(),
            ]);
        });

        $correction->load('user');
        if ($correction->user) {
            RequestNotificationMailService::sendDecisionEmail(
                $correction->user,
                'Koreksi Absensi',
                'Approved',
                [
                    'Tanggal Absen'    => Carbon::parse($correction->attendance_date)->translatedFormat('d F Y'),
                    'Usulan Jam Masuk' => Carbon::parse($correction->proposed_check_in_time)->format('H:i'),
                    'Usulan Jam Keluar' => Carbon::parse($correction->proposed_check_out_time)->format('H:i'),
                    'Status'           => 'Disetujui (Approved)',
                    'Disetujui Oleh'   => Auth::user()->fullname,
                ],
                Auth::user()->fullname
            );
        }

        return back()->with('success', 'Koreksi absensi berhasil disetujui.');
    }

    public function reject(Request $request, AttendanceCorrection $correction)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($correction, $validated): void {
            $lockedCorrection = AttendanceCorrection::with('user')
                ->lockForUpdate()
                ->findOrFail($correction->id);

            $this->authorizeDecision($lockedCorrection);
            $this->ensurePending($lockedCorrection);

            $lockedCorrection->update([
                'status' => AttendanceCorrection::STATUS_REJECTED,
                'rejection_reason' => $validated['rejection_reason'],
                'decided_by' => Auth::id(),
                'decided_at' => now(),
            ]);
        });

        $correction->load('user');
        if ($correction->user) {
            RequestNotificationMailService::sendDecisionEmail(
                $correction->user,
                'Koreksi Absensi',
                'Rejected',
                [
                    'Tanggal Absen'    => Carbon::parse($correction->attendance_date)->translatedFormat('d F Y'),
                    'Usulan Jam Masuk' => Carbon::parse($correction->proposed_check_in_time)->format('H:i'),
                    'Usulan Jam Keluar' => Carbon::parse($correction->proposed_check_out_time)->format('H:i'),
                    'Status'           => 'Ditolak (Rejected)',
                    'Alasan Penolakan' => $validated['rejection_reason'],
                    'Ditolak Oleh'     => Auth::user()->fullname,
                ],
                Auth::user()->fullname
            );
        }

        return back()->with('success', 'Koreksi absensi ditolak.');
    }

    private function validateAttendanceDate(string $date): string
    {
        $validator = validator(
            ['attendance_date' => $date],
            ['attendance_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today']]
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $date;
    }

    private function correctionExists(string $userId, string $date): bool
    {
        return AttendanceCorrection::where('user_id', $userId)
            ->whereDate('attendance_date', $date)
            ->exists();
    }

    private function authorizeDecision(AttendanceCorrection $correction): void
    {
        $actor = Auth::user();
        $isConfiguredManager = trim((string) $correction->user->manager) === trim((string) $actor->fullname);

        abort_unless($this->isAdmin($actor) || $isConfiguredManager, 403);
    }

    private function ensurePending(AttendanceCorrection $correction): void
    {
        abort_unless(
            $correction->status === AttendanceCorrection::STATUS_PENDING,
            409,
            'Pengajuan koreksi ini sudah diproses.'
        );
    }

    private function isAdmin(User $user): bool
    {
        $roles = array_map('trim', explode(',', (string) $user->role));

        return in_array('Admin', $roles, true);
    }
}
