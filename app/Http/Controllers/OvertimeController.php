<?php

namespace App\Http\Controllers;

use App\Models\Overtime;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use App\Exports\OvertimeReportExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Services\RequestNotificationMailService;

class OvertimeController extends Controller
{
    // ==========================================
    // 1. HALAMAN STAFF (Riwayat & Input)
    // ==========================================
    public function index()
    {
        // Staff melihat riwayat lemburnya sendiri
        $overtimes = Overtime::where('user_id', Auth::id())
                        ->orderBy('date', 'desc')
                        ->paginate(10)
                        ->withQueryString();

        return view('overtime.index', compact('overtimes'));
    }

    public function create()
    {
        return view('overtime.create');
    }

    public function calculateDuration(Request $request)
    {
        $date = $request->query('date');
        $user = Auth::user();

        if (!$date) {
            return response()->json(['success' => false, 'message' => 'Tanggal belum dipilih.']);
        }

        $attendance = \App\Models\Attendance::where('user_id', $user->id)
            ->whereDate('check_in_time', $date)
            ->first();

        if (!$attendance || !$attendance->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'Absensi check-out belum ditemukan pada tanggal ini. Durasi dapat diisi manual.',
                'duration' => null
            ]);
        }

        $schedule = \App\Models\Schedule::with('shift')
            ->where('user_id', $user->id)
            ->where('date', $date)
            ->first();

        $checkOut = \Carbon\Carbon::parse($attendance->check_out_time);

        if ($schedule && $schedule->shift) {
            $shiftEndTime = \Carbon\Carbon::parse($date . ' ' . $schedule->shift->end_time);
            $minutes = $checkOut->diffInMinutes($shiftEndTime, false);
            $hours = round(max(0.5, $minutes / 60), 1);
        } else {
            $checkIn = \Carbon\Carbon::parse($attendance->check_in_time);
            $minutes = $checkOut->diffInMinutes($checkIn);
            $hours = round(max(0.5, $minutes / 60), 1);
        }

        return response()->json([
            'success' => true,
            'duration' => $hours,
            'check_out' => $checkOut->format('H:i'),
            'message' => "Durasi dikalkulasi otomatis dari jam check-out ({$checkOut->format('H:i')})"
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'duration' => 'required|numeric|min:1',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if (\Illuminate\Support\Facades\Schema::hasTable('leaves')) {
            $hasLeave = \App\Models\Leave::where('user_id', Auth::id())
                ->whereIn('status', ['pending', 'pending Apron', 'pending Bge', 'approved'])
                ->whereDate('start_date', '<=', $request->date)
                ->whereDate('end_date', '>=', $request->date)
                ->exists();

            if ($hasLeave) {
                Alert::error('Gagal', 'Anda tidak dapat mengajukan lembur pada tanggal tersebut karena sedang dalam masa cuti.');
                return redirect()->back()->withInput();
            }
        }

        $overtime = Overtime::create([
            'user_id' => Auth::id(),
            'date' => $request->date,
            'duration' => $request->duration,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'Pending', // Otomatis Pending
        ]);

        // Kirim email pemberitahuan ke pemohon (creator)
        RequestNotificationMailService::sendSubmissionEmail(
            Auth::user(),
            'Lembur',
            [
                'Tanggal Lembur' => \Carbon\Carbon::parse($request->date)->translatedFormat('d F Y'),
                'Durasi'         => $request->duration . ' Jam',
                'Judul'          => $request->title,
                'Keterangan'     => $request->description,
                'Status'         => 'Pending (Menunggu Persetujuan Atasan)',
            ]
        );

        Alert::success('Terkirim', 'Pengajuan lembur berhasil dikirim ke Leader.');
        return redirect()->route('overtime.index');
    }

    // ==========================================
    // 2. HALAMAN LEADER (Approval)
    // ==========================================
    public function approvalList(Request $request)
    {
        $user = Auth::user();

        // Security Check: Hanya yg punya overtime.approve yg boleh masuk
        abort_unless(Auth::user()->canAccess('overtime', 'approve'), 403);

        $query = Overtime::with('user')
            ->select('overtimes.*')
            ->join('users', 'users.id', '=', 'overtimes.user_id')
            ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
            ->where('overtimes.status', 'Pending');

        if (!$user->isAdmin()) {
            $query->where('overtimes.user_id', '!=', $user->id);
        }

        // Filter Search (NIP / Nama)
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('employees.fullname', 'like', "%{$search}%")
                  ->orWhere('users.id', 'like', "%{$search}%");
            });
        }

        $userRole = $user->role ?? '';

        // Filter Station dan Divisi berdasarkan hak akses
        if ($user->isAdmin()) {
            if ($request->filled('station')) {
                $query->where('users.' . User::getStationColumn(), $request->station);
            }
        } elseif ($userRole === 'Head Of Airport Service' || $user->station === 'Ho') {
            $query->where('users.' . User::getStationColumn(), $user->station);
        } elseif ((str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) && !in_array($userRole, ['Porter Bge'])) {
            $query->where('users.' . User::getStationColumn(), $user->station)
                  ->where(function($q) {
                      $q->where('users.role', 'LIKE', '%Bge%')
                        ->orWhere('users.role', 'LIKE', '%BGE%')
                        ->orWhere('users.role', 'LIKE', '%Baggage%');
                  });
        } elseif ((str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) && !in_array($userRole, ['Porter Apron'])) {
            $query->where('users.' . User::getStationColumn(), $user->station)
                  ->where(function($q) {
                      $q->where('users.role', 'LIKE', '%Apron%')
                        ->orWhere('users.role', 'LIKE', '%APRON%');
                  });
        } else {
            $query->where('users.' . User::getStationColumn(), $user->station);
        }

        $perPage = $request->input('per_page', 20);
        $pendingOvertimes = $query->orderBy('overtimes.date', 'desc')->paginate($perPage)->withQueryString();

        return view('overtime.approval', compact('pendingOvertimes'));
    }

    public function approve($id)
    {
        $ot = Overtime::with('user')->findOrFail($id);
        $this->authorizeDecision($ot);

        $ot->update([
            'status' => 'Approved',
            'approved_by' => Auth::user()->fullname
        ]);

        if ($ot->user) {
            RequestNotificationMailService::sendDecisionEmail(
                $ot->user,
                'Lembur',
                'Approved',
                [
                    'Tanggal Lembur' => \Carbon\Carbon::parse($ot->date)->translatedFormat('d F Y'),
                    'Durasi'         => $ot->duration . ' Jam',
                    'Judul'          => $ot->title,
                    'Status'         => 'Disetujui (Approved)',
                    'Disetujui Oleh' => Auth::user()->fullname,
                ],
                Auth::user()->fullname
            );
        }

        Alert::success('Approved', 'Lembur staff telah disetujui.');
        return back();
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $ot = Overtime::with('user')->findOrFail($id);
        $this->authorizeDecision($ot);

        $ot->update([
            'status' => 'Rejected',
            'rejection_reason' => $request->input('rejection_reason'),
            'approved_by' => Auth::user()->fullname
        ]);

        if ($ot->user) {
            RequestNotificationMailService::sendDecisionEmail(
                $ot->user,
                'Lembur',
                'Rejected',
                [
                    'Tanggal Lembur'  => \Carbon\Carbon::parse($ot->date)->translatedFormat('d F Y'),
                    'Durasi'          => $ot->duration . ' Jam',
                    'Judul'           => $ot->title,
                    'Status'          => 'Ditolak (Rejected)',
                    'Alasan Penolakan'=> $request->input('rejection_reason'),
                    'Ditolak Oleh'    => Auth::user()->fullname,
                ],
                Auth::user()->fullname
            );
        }

        Alert::warning('Rejected', 'Pengajuan lembur ditolak.');
        return back();
    }

    // ==========================================
    // 3. HALAMAN REKAP (Admin/HO)
    // ==========================================
    public function report(Request $request)
    {
        $authUser = Auth::user();
        abort_unless($authUser->canAccess('overtime', 'export'), 403);

        $query = Overtime::with('user')->where('status', 'Approved');
        $search = $request->input('search');

        // Filter Search (NIP / Nama)
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $isFullAccess = $authUser->hasRole(['Admin', 'Head Of Airport Service']) || ($authUser->station === 'Ho');

        // Filter Station
        if ($request->filled('station')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('station', $request->station);
            });
        } elseif (!$isFullAccess && $authUser->station) {
            $query->whereHas('user', function($q) use ($authUser) {
                $q->where('station', $authUser->station);
            });
        }
        
        // Filter Tanggal (Opsional)
        if ($request->date_start && $request->date_end) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        $overtimes = $query->latest()->paginate(20)->withQueryString();
        $totalHours = $query->sum('duration'); // Total jam untuk payroll

        if ($isFullAccess) {
            $stations = \App\Models\Station::where('is_active', 1)->orderBy('name', 'asc')->get();
        } else {
            $stations = \App\Models\Station::where('is_active', 1)->where('code', $authUser->station)->orderBy('name', 'asc')->get();
        }

        $userStation = !$isFullAccess ? $authUser->station : null;

        return view('overtime.report', compact('overtimes', 'totalHours', 'stations', 'isFullAccess', 'userStation'));
    }

    public function exportExcel(Request $request)
    {
        $authUser = Auth::user();
        abort_unless($authUser->canAccess('overtime', 'export'), 403);

        try {
            $query = Overtime::with('user')->where('status', 'Approved');
            $search = $request->input('search');

            if ($search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%");
                });
            }

            $isFullAccess = $authUser->hasRole(['Admin', 'Head Of Airport Service']) || ($authUser->station === 'Ho');

            if ($request->filled('station')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('station', $request->station);
                });
            } elseif (!$isFullAccess && $authUser->station) {
                $query->whereHas('user', function($q) use ($authUser) {
                    $q->where('station', $authUser->station);
                });
            }
            
            if ($request->date_start && $request->date_end) {
                $query->whereBetween('date', [$request->date_start, $request->date_end]);
            }

            $overtimes = $query->latest()->get();

            if ($overtimes->isEmpty()) {
                return redirect()->back()->with('warning', 'Tidak ada data lembur yang disetujui untuk diexport.');
            }

            $stationLabel = $request->filled('station') ? '_' . $request->station : '';
            return Excel::download(new OvertimeReportExport($overtimes), 'Laporan_Lembur' . $stationLabel . '_'.date('YmdHis').'.xlsx');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengunduh laporan lembur: ' . $e->getMessage());
        }
    }

    private function authorizeDecision(Overtime $ot)
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return;
        }

        // Prevent self-approval
        if ((string) $ot->user_id === (string) $user->id) {
            abort(403, 'Anda tidak dapat menyetujui/menolak pengajuan lembur Anda sendiri.');
        }

        $userRole = $user->role ?? '';
        $applicant = $ot->user;

        if (!$applicant) {
            abort(404, 'Data pemohon tidak ditemukan.');
        }

        // Station must match
        if ($applicant->station !== $user->station) {
            abort(403, 'Anda hanya dapat menyetujui/menolak pengajuan lembur di station yang sama.');
        }

        if ($userRole === 'Head Of Airport Service' || $user->station === 'Ho') {
            // HOAS has access to all at station
            return;
        }

        $applicantRole = $applicant->role ?? '';

        if (str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) {
            $isBgeSub = str_contains($applicantRole, 'Bge') || str_contains($applicantRole, 'BGE') || str_contains($applicantRole, 'Baggage');
            if (!$isBgeSub) {
                abort(403, 'Leader BGE hanya dapat menyetujui/menolak pengajuan divisi Baggage.');
            }
        } elseif (str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) {
            $isApronSub = str_contains($applicantRole, 'Apron') || str_contains($applicantRole, 'APRON');
            if (!$isApronSub) {
                abort(403, 'Leader Apron hanya dapat menyetujui/menolak pengajuan divisi Apron.');
            }
        }
    }
}