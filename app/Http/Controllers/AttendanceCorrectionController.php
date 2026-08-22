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
use RealRashid\SweetAlert\Facades\Alert;

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
            $query->where('user_id', '!=', $actor->id);

            $userRole = $actor->role ?? '';

            if ($userRole === 'Head Of Airport Service' || $actor->station === 'Ho') {
                if (! empty($userStations)) {
                    $query->whereHas('station', fn ($b) => $b->whereIn('code', $userStations));
                }
            } elseif ((str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) && !in_array($userRole, ['Porter Bge'])) {
                if (! empty($userStations)) {
                    $query->whereHas('station', fn ($b) => $b->whereIn('code', $userStations));
                }
                $query->whereHas('user', function ($q) {
                    $q->where(function ($sq) {
                        if (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
                            $sq->whereHas('roleRelation', function ($rq) {
                                $rq->where('name', 'LIKE', '%Bge%')
                                  ->orWhere('name', 'LIKE', '%BGE%')
                                  ->orWhere('name', 'LIKE', '%Baggage%');
                            });
                        }
                        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
                            $sq->orWhere('users.role', 'LIKE', '%Bge%')
                              ->orWhere('users.role', 'LIKE', '%BGE%')
                              ->orWhere('users.role', 'LIKE', '%Baggage%');
                        }
                    });
                });
            } elseif ((str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) && !in_array($userRole, ['Porter Apron'])) {
                if (! empty($userStations)) {
                    $query->whereHas('station', fn ($b) => $b->whereIn('code', $userStations));
                }
                $query->whereHas('user', function ($q) {
                    $q->where(function ($sq) {
                        if (\Illuminate\Support\Facades\Schema::hasTable('roles')) {
                            $sq->whereHas('roleRelation', function ($rq) {
                                $rq->where('name', 'LIKE', '%Apron%')
                                  ->orWhere('name', 'LIKE', '%APRON%');
                            });
                        }
                        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
                            $sq->orWhere('users.role', 'LIKE', '%Apron%')
                              ->orWhere('users.role', 'LIKE', '%APRON%');
                        }
                    });
                });
            } else {
                $query->whereHas(
                    'user',
                    fn ($builder) => $builder->whereHas('employee', fn ($eq) => $eq->where('manager', $actor->fullname))
                );
                if (! empty($userStations)) {
                    $query->whereHas('station', fn ($b) => $b->whereIn('code', $userStations));
                }
            }
        } else {
            if (count($userStations) === 1) {
                $singleCode = reset($userStations);
                $query->whereHas('station', fn ($b) => $b->where('code', $singleCode));
            } elseif (count($userStations) > 1) {
                if ($request->filled('station_id')) {
                    $stVal = $request->input('station_id');
                    $query->where(function ($q) use ($stVal) {
                        $q->where('station', $stVal)
                          ->orWhereHas('station', fn ($b) => $b->where('code', $stVal));
                    });
                } else {
                    $query->whereHas('station', fn ($b) => $b->whereIn('code', $userStations));
                }
            } else {
                if ($request->filled('station_id')) {
                    $stVal = $request->input('station_id');
                    $query->where(function ($q) use ($stVal) {
                        $q->where('station', $stVal)
                          ->orWhereHas('station', fn ($b) => $b->where('code', $stVal));
                    });
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

        if (\Illuminate\Support\Facades\Schema::hasTable('leaves')) {
            $hasLeave = \App\Models\Leave::where('user_id', Auth::id())
                ->whereIn('status', ['pending', 'pending Apron', 'pending Bge', 'approved'])
                ->whereDate('start_date', '<=', $attendanceDate)
                ->whereDate('end_date', '>=', $attendanceDate)
                ->exists();

            if ($hasLeave) {
                Alert::error('Gagal', 'Anda tidak dapat mengajukan koreksi absen pada tanggal tersebut karena sedang dalam masa cuti.');
                return redirect()->back()->withInput();
            }
        }

        $stationColumn = \Illuminate\Support\Facades\Schema::hasColumn('stations', 'code') ? 'code' : 'id';

        $validated = $request->validate([
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'station_id' => [
                'required',
                Rule::exists('stations', $stationColumn)->where(
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

        // Cek batas pengajuan koreksi (Maksimal 1 bulan mundur dari bulan berjalan)
        $dateMonth = Carbon::parse($date)->startOfMonth();
        $currentMonth = Carbon::now()->startOfMonth();
        $monthsDiff = $dateMonth->diffInMonths($currentMonth, false);

        if ($dateMonth > $currentMonth || $monthsDiff > 1) {
            throw ValidationException::withMessages([
                'attendance_date' => 'Pengajuan koreksi absensi hanya diperbolehkan untuk bulan ini dan 1 bulan sebelumnya.',
            ]);
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
        if ($this->isAdmin($actor)) {
            return;
        }

        // Prevent self-approval
        if ((string) $correction->user_id === (string) $actor->id) {
            abort(403, 'Anda tidak dapat menyetujui/menolak koreksi absensi Anda sendiri.');
        }

        $rawStation = trim((string) $actor->station);
        $userStations = ($rawStation !== '' && strtoupper($rawStation) !== 'ALL' && strtoupper($rawStation) !== 'SEMUA')
            ? array_filter(array_map('trim', explode(',', $rawStation)))
            : [];

        // Station must match if actor has specific stations configured
        if (!empty($userStations) && $correction->station) {
            if (!in_array($correction->station->code, $userStations)) {
                abort(403, 'Anda tidak dapat memproses koreksi absensi dari station lain.');
            }
        }

        $userRole = $actor->role ?? '';
        $applicant = $correction->user;
        $applicantRole = $applicant->role ?? '';

        if ($userRole === 'Head Of Airport Service' || $actor->station === 'Ho') {
            // HOAS has access to all at station
            return;
        }

        if (str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) {
            $isBgeSub = str_contains($applicantRole, 'Bge') || str_contains($applicantRole, 'BGE') || str_contains($applicantRole, 'Baggage');
            if (!$isBgeSub) {
                abort(403, 'Leader BGE hanya dapat menyetujui/menolak koreksi divisi Baggage.');
            }
        } elseif (str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) {
            $isApronSub = str_contains($applicantRole, 'Apron') || str_contains($applicantRole, 'APRON');
            if (!$isApronSub) {
                abort(403, 'Leader Apron hanya dapat menyetujui/menolak koreksi divisi Apron.');
            }
        } else {
            $isConfiguredManager = (trim((string) $applicant->manager) === trim((string) $actor->fullname))
                || ($applicant->pic_id && (string) $applicant->pic_id === (string) $actor->id)
                || ($applicant->unit_id && $actor->unit_id && $applicant->unit_id === $actor->unit_id && (str_contains($userRole, 'Leader') || str_contains($userRole, 'Supervisor') || str_contains($userRole, 'Head')));
            abort_unless($isConfiguredManager, 403, 'Anda tidak memiliki wewenang struktural untuk memproses pengajuan ini.');
        }
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
        return $user->isAdmin();
    }
}
