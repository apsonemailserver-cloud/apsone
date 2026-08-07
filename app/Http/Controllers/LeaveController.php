<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Validation\Rule;
use App\Exports\LeavesReportExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Services\RequestNotificationMailService;

class LeaveController extends Controller
{
    private const ANNUAL_LEAVE_QUOTA_DAYS = 12;

    /**
     * Menampilkan daftar riwayat pengajuan cuti.
     * Admin melihat semua, user biasa hanya melihat miliknya.
     */
    public function index()
    {
        $user = Auth::user();

        // Hanya yang punya akses leave.approve atau Admin yang bisa melihat daftar approval
        if (!$user->canAccess('leave', 'approve') && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $query = Leave::with('user')
            ->select('leaves.*')
            ->join('users', 'users.id', '=', 'leaves.user_id')
            ->latest('leaves.created_at');

        if (!$user->isAdmin()) {
            $query->where('leaves.user_id', '!=', $user->id);
        }

        $userRole = $user->role ?? '';

        // Halaman Approval HANYA menampilkan pengajuan yang MASIH MENUNGGU PERSETUJUAN (Pending)
        if ($user->isAdmin()) {
            // Admin dapat melihat semua pengajuan pending
            $query->whereIn('leaves.status', ['pending', 'pending Apron', 'pending Bge']);
        } elseif ($userRole === 'Head Of Airport Service' || $user->station === 'Ho') {
            // Head of Airport Service / HO dapat melihat pengajuan pending
            $query->whereIn('leaves.status', ['pending', 'pending Apron', 'pending Bge']);
        } elseif ((str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) && !in_array($userRole, ['Porter Bge'])) {
            // Leader / SPV BGE melihat pengajuan pending BGE di station miliknya
            $query->where('users.station', $user->station)
                  ->where('leaves.status', 'pending Bge');
        } elseif ((str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) && !in_array($userRole, ['Porter Apron'])) {
            // Leader / SPV Apron melihat pengajuan pending Apron di station miliknya
            $query->where('users.station', $user->station)
                  ->where('leaves.status', 'pending Apron');
        } else {
            // Atasan/Supervisor lain hanya melihat pengajuan pending di station miliknya
            $query->where('users.station', $user->station)
                  ->where('leaves.status', 'pending');
        }

        // Search Filter
        if ($search = request('search')) {
            $query->where(function($q) use ($search) {
                $q->where('users.fullname', 'LIKE', "%{$search}%")
                  ->orWhere('users.id', 'LIKE', "%{$search}%")
                  ->orWhere('leaves.reason', 'LIKE', "%{$search}%");
            });
        }

        $leaves = $query->paginate(10)->withQueryString();

        // --- Logika Perhitungan Sisa Cuti ---
        $annualLeaveUsage = $this->annualLeaveUsage($user->id);
        $usedLeaveDays = $annualLeaveUsage['used'];
        $leaveBalance = $annualLeaveUsage['balance'];

        return view('leaves.index', compact('leaves', 'user', 'leaveBalance', 'usedLeaveDays'));
    }

    public function pengajuan()
    {
        $user = Auth::user();
        $query = Leave::with('user')
            ->select('leaves.*')
            ->join('users', 'users.id', '=', 'leaves.user_id')
            ->latest(); // Eager load relasi user

        $userRole = $user->role ?? '';

        if ($user->isAdmin()) {
            // Admin sees all leaves
        } elseif ($userRole === 'Head Of Airport Service' || $user->station === 'Ho') {
            // HOAS sees leaves in their station
            $query->where('users.station', $user->station);
        } elseif ((str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) && !in_array($userRole, ['Porter Bge'])) {
            // Leader Bge sees own leaves + Bge subordinates
            $query->where(function ($q) use ($user) {
                $q->where('leaves.user_id', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('users.station', $user->station)
                         ->where(function ($q3) {
                             $q3->where('users.role', 'LIKE', '%Bge%')
                                ->orWhere('users.role', 'LIKE', '%BGE%')
                                ->orWhere('users.role', 'LIKE', '%Baggage%');
                         });
                  });
            });
        } elseif ((str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) && !in_array($userRole, ['Porter Apron'])) {
            // Leader Apron sees own leaves + Apron subordinates
            $query->where(function ($q) use ($user) {
                $q->where('leaves.user_id', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('users.station', $user->station)
                         ->where(function ($q3) {
                             $q3->where('users.role', 'LIKE', '%Apron%')
                                ->orWhere('users.role', 'LIKE', '%APRON%');
                         });
                  });
            });
        } else {
            // Standard user sees only own leaves
            $query->where('leaves.user_id', $user->id);
        }

        // Search Filter
        if ($search = request('search')) {
            $query->where(function($q) use ($search) {
                $q->where('users.fullname', 'LIKE', "%{$search}%")
                  ->orWhere('users.id', 'LIKE', "%{$search}%")
                  ->orWhere('leaves.reason', 'LIKE', "%{$search}%");
            });
        }

        $leaves = $query->paginate(10)->withQueryString(); // Paginasi data

        // --- Logika Perhitungan Sisa Cuti ---
        $annualLeaveUsage = $this->annualLeaveUsage($user->id);
        $usedLeaveDays = $annualLeaveUsage['used'];
        $leaveBalance = $annualLeaveUsage['balance'];

        return view('leaves.pengajuan', compact('leaves', 'user', 'leaveBalance', 'usedLeaveDays'));
    }

    public function laporan(Request $request)
    {
        $authUser = Auth::user();
        $year = $request->year ?? date('Y');

        \Log::info('Leave Laporan Request details:', [
            'url' => $request->fullUrl(),
            'user' => $authUser ? $authUser->id : null,
            'role' => $authUser ? $authUser->role : null,
            'station_param' => $request->station,
            'all' => $request->all()
        ]);

        // Ambil data leaves join users (pemohon, approver, rejector)
        $query = \App\Models\Leave::join('users as u', 'leaves.user_id', '=', 'u.id')
            ->leftJoin('users as approved', 'leaves.approved_by', '=', 'approved.id')
            ->leftJoin('users as rejected', 'leaves.rejected_by', '=', 'rejected.id')
            ->whereYear('leaves.start_date', $year)
            ->select(
                'leaves.*',
                'u.id as user_id',
                'u.fullname as user_leave',
                'u.station as station',
                'approved.fullname as user_approve',
                'rejected.fullname as user_rejected'
            )
            ->orderBy('leaves.created_at', 'desc');

        // Access Scoping: Admin & Head / HO have full access across all stations
        $isFullAccess = $authUser->hasRole(['Admin', 'Head Of Airport Service']) || ($authUser->station === 'Ho');

        if ($request->filled('station')) {
            $query->where('u.station', $request->station);
        } elseif (!$isFullAccess && $authUser->station) {
            $query->where('u.station', $authUser->station);
        }

        if ($request->filled('user_name')) {
            $query->where(function ($q) use ($request) {
                $q->whereRaw("CAST(u.id AS CHAR) LIKE ?", ["%{$request->user_name}%"])
                    ->orWhere('u.fullname', 'LIKE', "%{$request->user_name}%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $leaves = $query->paginate($perPage)->withQueryString();

        $stations = \App\Models\Station::where('is_active', 1)->orderBy('name', 'asc')->get();
        $userStation = !$isFullAccess ? $authUser->station : null;

        return view('leaves.laporan', compact('leaves', 'stations', 'userStation', 'isFullAccess'));
    }

    public function export(Request $request)
    {
        try {
            $authUser = Auth::user();
            $year = $request->input('year', date('Y'));

            // Build query with joins to get full data matching the laporan view
            $query = \App\Models\Leave::join('users as u', 'leaves.user_id', '=', 'u.id')
                ->leftJoin('users as approved', 'leaves.approved_by', '=', 'approved.id')
                ->leftJoin('users as rejected', 'leaves.rejected_by', '=', 'rejected.id')
                ->whereYear('leaves.start_date', $year)
                ->select(
                    'leaves.*',
                    'u.id as user_nip',
                    'u.fullname as user_leave',
                    'u.station as station',
                    'approved.fullname as user_approve',
                    'rejected.fullname as user_rejected'
                )
                ->orderBy('u.fullname')
                ->orderBy('leaves.start_date');

            $isFullAccess = $authUser->hasRole(['Admin', 'Head Of Airport Service']) || ($authUser->station === 'Ho');

            if ($request->filled('station')) {
                $query->where('u.station', $request->station);
            } elseif (!$isFullAccess && $authUser->station) {
                $query->where('u.station', $authUser->station);
            }

            // Optional: filter by specific user
            if ($request->filled('user_name')) {
                $query->where(function ($q) use ($request) {
                    $q->whereRaw("CAST(u.id AS CHAR) LIKE ?", ["%{$request->user_name}%"])
                      ->orWhere('u.fullname', 'LIKE', "%{$request->user_name}%");
                });
            }

            $leaves = $query->get();

            if ($leaves->isEmpty()) {
                return redirect()->back()->with('warning', 'Tidak ada data pengajuan cuti untuk dicetak pada tahun ' . $year);
            }

            $stationLabel = $request->filled('station') ? '_' . $request->station : '';
            $userLabel = $request->filled('user_name') ? '_' . preg_replace('/[^A-Za-z0-9]/', '_', $request->user_name) : '_Semua';
            $fileName = 'Laporan_Cuti' . $stationLabel . $userLabel . '_' . $year . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\LeavesReportExport($leaves),
                $fileName
            );
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengunduh data cuti: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form pengajuan cuti.
     */
    public function create()
    {
        // Ambil data sisa cuti untuk ditampilkan di form
        $user = Auth::user();
        $annualLeaveUsage = $this->annualLeaveUsage($user->id);
        $leaveBalance = $annualLeaveUsage['balance'];

        return view('leaves.create', compact('leaveBalance'));
    }

    /**
     * Menyimpan data pengajuan cuti baru.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        // Validasi
        $request->validate([
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'replacement_employee_name' => 'nullable|string|max:255',
        ], [
            'attachment.image' => 'File lampiran harus berupa foto / gambar.',
            'attachment.mimes' => 'Format foto lampiran harus berupa JPG, JPEG, PNG, atau WEBP.',
            'attachment.max'   => 'Ukuran foto lampiran maksimal 5MB.',
        ]);

        // ===== CEK OVERLAP CUTI =====
        // Tidak boleh mengajukan cuti jika sudah ada cuti (pending/approved) di rentang tanggal yang sama
        $overlappingLeave = Leave::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected by ho', 'rejected by leader'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_date', '<=', $request->start_date)
                         ->where('end_date', '>=', $request->end_date);
                  });
            })
            ->first();

        if ($overlappingLeave) {
            $existStart = Carbon::parse($overlappingLeave->start_date)->translatedFormat('d M Y');
            $existEnd   = Carbon::parse($overlappingLeave->end_date)->translatedFormat('d M Y');
            Alert::error(
                'Tanggal Sudah Digunakan',
                "Anda sudah memiliki pengajuan cuti ({$overlappingLeave->leave_type}) pada tanggal {$existStart} – {$existEnd}. Tidak dapat mengajukan cuti di tanggal yang sama."
            );
            return redirect()->back()->withInput();
        }

        $status = 'pending';

        if ($user->isAdmin()) {
            $status = 'approved';
        } else {
            $userRole = $user->role ?? '';
            $isLeaderOrSpv = (str_contains($userRole, 'Leader') && !str_contains($userRole, 'Ass'))
                || str_contains($userRole, 'SPV')
                || str_contains($userRole, 'Manager')
                || str_contains($userRole, 'Head');

            if (!$isLeaderOrSpv) {
                if (str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE') || str_contains($userRole, 'Baggage')) {
                    $status = 'pending Bge';
                } elseif (str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) {
                    $status = 'pending Apron';
                }
            }
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $annualLeaveDecision = null;
        $isAutomaticallyRejected = false;

        // Cek sisa cuti jika jenisnya adalah 'Cuti Tahunan'
        if ($request->leave_type === 'Cuti Tahunan') {
            $annualLeaveDecision = $this->annualLeaveDecision($user->id, $startDate, $totalDays);

            if ($annualLeaveDecision['exceeds']) {
                $status = 'rejected by ho';
                $isAutomaticallyRejected = true;
            }
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        $leaveData = [
            'user_id'       => Auth::id(),
            'leave_type'    => $request->leave_type,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'reason'        => $request->reason,
            'total_days'    => $totalDays,
            'attachment_path' => $attachmentPath,
            'replacement_employee_name' => $request->replacement_employee_name,
            'status'        => $status,
        ];

        if ($isAutomaticallyRejected) {
            $leaveData['manager_comment'] = $this->annualLeaveRejectionComment($annualLeaveDecision);
        } elseif ($status === 'approved') {
            $leaveData['approved_by'] = Auth::id();
            $leaveData['approved_at'] = now();
        }

        Leave::create($leaveData);

        if ($isAutomaticallyRejected) {
            RequestNotificationMailService::sendDecisionEmail(
                $user,
                'Cuti (' . $request->leave_type . ')',
                'Rejected',
                [
                    'Jenis Cuti'         => $request->leave_type,
                    'Tanggal Mulai'      => $startDate->translatedFormat('d F Y'),
                    'Tanggal Selesai'    => $endDate->translatedFormat('d F Y'),
                    'Total Hari'         => $totalDays . ' Hari',
                    'Alasan'             => $request->reason,
                    'Keterangan'         => 'Pengajuan cuti tahunan melebihi kuota 12 hari.',
                    'Status'             => 'Ditolak Otomatis (Kuota Terlampaui)',
                ],
                'Sistem (Kuota Terlampaui)'
            );

            Alert::warning('Ditolak Otomatis', 'Pengajuan cuti tahunan melebihi kuota 12 hari dan otomatis ditolak.');
            return redirect()->route('leaves.pengajuan');
        }

        // Kirim email pemberitahuan ke pemohon (creator)
        if ($status === 'approved') {
            RequestNotificationMailService::sendDecisionEmail(
                $user,
                'Cuti (' . $request->leave_type . ')',
                'Approved',
                [
                    'Jenis Cuti'         => $request->leave_type,
                    'Tanggal Mulai'      => $startDate->translatedFormat('d F Y'),
                    'Tanggal Selesai'    => $endDate->translatedFormat('d F Y'),
                    'Total Hari'         => $totalDays . ' Hari',
                    'Alasan'             => $request->reason,
                    'Karyawan Pengganti' => $request->replacement_employee_name ?: '-',
                    'Status'             => 'Disetujui (Otomatis Approved)',
                ],
                'Sistem (Admin Auto-Approve)'
            );
        } else {
            RequestNotificationMailService::sendSubmissionEmail(
                $user,
                'Cuti (' . $request->leave_type . ')',
                [
                    'Jenis Cuti'         => $request->leave_type,
                    'Tanggal Mulai'      => $startDate->translatedFormat('d F Y'),
                    'Tanggal Selesai'    => $endDate->translatedFormat('d F Y'),
                    'Total Hari'         => $totalDays . ' Hari',
                    'Alasan'             => $request->reason,
                    'Karyawan Pengganti' => $request->replacement_employee_name ?: '-',
                    'Status'             => 'Pending (Menunggu Persetujuan Atasan)',
                ]
            );
        }

        Alert::success('Berhasil', 'Pengajuan Anda telah berhasil dikirim.');
        return redirect()->route('leaves.pengajuan');
    }

    /**
     * Membatalkan pengajuan (cancel).
     */
    public function cancel(Request $request, Leave $leave)
    {
        $user = Auth::user();
        $isOwner = (string) $leave->user_id === (string) $user->id;
        $isAdminOrApprover = $user->canAccess('leave', 'approve') || $user->isAdmin();
        $isPending = in_array($leave->status, ['pending', 'pending Apron', 'pending Bge']);
        $isApproved = $leave->status === 'approved';

        // Bawahan (karyawan pemohon) hanya boleh membatalkan jika status masih Menunggu.
        // Atasan / Admin boleh membatalkan jika status Menunggu atau Disetujui.
        $canCancel = ($isOwner && $isPending) || ($isAdminOrApprover && ($isPending || $isApproved));

        if (!$canCancel) {
            if ($isOwner && $isApproved && !$isAdminOrApprover) {
                Alert::warning('Peringatan', 'Pengajuan yang sudah Disetujui hanya dapat dibatalkan oleh Atasan / Admin.');
                return redirect()->back();
            }
            abort(403, 'Anda tidak memiliki akses untuk membatalkan pengajuan ini.');
        }

        $leave->status = 'canceled';
        $leave->save();

        Alert::success('Berhasil', 'Pengajuan izin/cuti telah dibatalkan.');
        return redirect()->back();
    }

    /**
     * Mengubah status pengajuan (approve/reject/cancel).
     */
    public function updateStatus(Request $request, Leave $leave)
    {
        $user = Auth::user();
        if (!$user->canAccess('leave', 'approve') && !$user->isAdmin()) {
            abort(403, 'Anda tidak memiliki akses untuk menyetujui pengajuan ini.');
        }

        if (!$user->isAdmin() && (string) $leave->user_id === (string) $user->id) {
            abort(403, 'Anda tidak dapat menyetujui pengajuan cuti Anda sendiri.');
        }

        $validStatuses = ['approved', 'rejected by ho', 'pending', 'rejected by leader', 'canceled'];

        $request->validate([
            'status' => ['required', Rule::in($validStatuses)]
        ]);

        $userRole = $user->role ?? '';
        $status = $request->status;

        // Validasi transisi status dan hak akses
        if ($user->isAdmin() || $userRole === 'Head Of Airport Service') {
            if (!in_array($status, ['approved', 'rejected by ho'])) {
                abort(400, 'Status tidak valid untuk HOAS/Admin.');
            }
        } elseif (str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) {
            if ($leave->status !== 'pending Bge') {
                abort(403, 'Leader BGE hanya dapat menyetujui pengajuan berstatus Menunggu BGE.');
            }
            if (!in_array($status, ['pending', 'rejected by leader'])) {
                abort(400, 'Status tidak valid untuk Leader BGE.');
            }
        } elseif (str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) {
            if ($leave->status !== 'pending Apron') {
                abort(403, 'Leader Apron hanya dapat menyetujui pengajuan berstatus Menunggu Apron.');
            }
            if (!in_array($status, ['pending', 'rejected by leader'])) {
                abort(400, 'Status tidak valid untuk Leader Apron.');
            }
        } else {
            if ($leave->status !== 'pending') {
                abort(403, 'Anda hanya dapat menyetujui pengajuan berstatus Menunggu HO.');
            }
            if (!in_array($status, ['approved', 'rejected by leader'])) {
                abort(400, 'Status tidak valid.');
            }
        }

        if ($status === 'approved' && $leave->leave_type === 'Cuti Tahunan') {
            $annualLeaveDecision = $this->annualLeaveDecision(
                $leave->user_id,
                Carbon::parse($leave->start_date),
                (int) $leave->total_days,
                $leave->id
            );

            if ($annualLeaveDecision['exceeds']) {
                $leave->status = 'rejected by ho';
                $leave->rejected_by = Auth::id();
                $leave->approved_by = null;
                $leave->approved_at = null;
                $leave->manager_comment = $this->annualLeaveRejectionComment($annualLeaveDecision);
                $leave->save();

                $leave->load('user');
                if ($leave->user) {
                    RequestNotificationMailService::sendDecisionEmail(
                        $leave->user,
                        'Cuti (' . $leave->leave_type . ')',
                        'Rejected',
                        [
                            'Jenis Cuti'      => $leave->leave_type,
                            'Tanggal Mulai'   => Carbon::parse($leave->start_date)->translatedFormat('d F Y'),
                            'Tanggal Selesai' => Carbon::parse($leave->end_date)->translatedFormat('d F Y'),
                            'Total Hari'      => $leave->total_days . ' Hari',
                            'Keterangan'      => 'Pengajuan cuti tahunan melebihi kuota 12 hari.',
                            'Status'          => 'Ditolak Otomatis (Kuota Terlampaui)',
                        ],
                        Auth::user()->fullname
                    );
                }

                Alert::warning('Ditolak Otomatis', 'Cuti tahunan ini melebihi kuota 12 hari, sehingga otomatis ditolak.');
                return redirect()->route('leaves.index');
            }
        }

        $leave->status = $status;

        if ($status == 'approved') {
            $leave->approved_by = Auth::id();
            $leave->approved_at = now();
            $leave->rejected_by = null;
        } elseif (str_starts_with($status, 'rejected')) {
            $leave->rejected_by = Auth::id();
            $leave->approved_by = null;
            $leave->approved_at = null;
            if ($request->filled('manager_comment')) {
                $leave->manager_comment = $request->manager_comment;
            }
        } else {
            $leave->rejected_by = null;
        }

        $leave->save();

        // Kirim email pemberitahuan status keputusan ke pemohon
        if ($leave->user) {
            $isApprovedByLeader = ($status === 'pending');
            $isApprovedByHo     = ($status === 'approved');
            $isRejected         = str_starts_with($status, 'rejected');

            if ($isApprovedByLeader) {
                $statusText = 'Disetujui Leader (Menunggu HOAS)';
                $mailStatus = 'Forwarded';
            } elseif ($isApprovedByHo) {
                $statusText = 'Disetujui (Approved)';
                $mailStatus = 'Approved';
            } else {
                $statusText = 'Ditolak (Rejected)';
                $mailStatus = 'Rejected';
            }

            $emailDetails = [
                'Jenis Cuti'      => $leave->leave_type,
                'Tanggal Mulai'   => Carbon::parse($leave->start_date)->translatedFormat('d F Y'),
                'Tanggal Selesai' => Carbon::parse($leave->end_date)->translatedFormat('d F Y'),
                'Total Hari'      => $leave->total_days . ' Hari',
                'Status'          => $statusText,
            ];
            if ($isRejected && $leave->manager_comment) {
                $emailDetails['Alasan Penolakan'] = $leave->manager_comment;
            }

            RequestNotificationMailService::sendDecisionEmail(
                $leave->user,
                'Cuti (' . $leave->leave_type . ')',
                $mailStatus,
                $emailDetails,
                Auth::user()->fullname
            );
        }

        Alert::success('Berhasil', 'Status pengajuan telah diubah.');
        return redirect()->route('leaves.index');
    }

    private function annualLeaveUsage(string $userId, ?int $year = null, ?int $excludeLeaveId = null): array
    {
        $year ??= (int) date('Y');

        $query = Leave::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('leave_type', 'Cuti Tahunan')
            ->whereYear('start_date', $year);

        if ($excludeLeaveId !== null) {
            $query->where('id', '!=', $excludeLeaveId);
        }

        $used = (int) $query->sum('total_days');

        return [
            'quota' => self::ANNUAL_LEAVE_QUOTA_DAYS,
            'used' => $used,
            'balance' => max(0, self::ANNUAL_LEAVE_QUOTA_DAYS - $used),
            'raw_balance' => self::ANNUAL_LEAVE_QUOTA_DAYS - $used,
            'year' => $year,
        ];
    }

    private function annualLeaveDecision(string $userId, Carbon $startDate, int $requestedDays, ?int $excludeLeaveId = null): array
    {
        $usage = $this->annualLeaveUsage($userId, (int) $startDate->year, $excludeLeaveId);
        $projected = $usage['used'] + $requestedDays;

        return array_merge($usage, [
            'requested' => $requestedDays,
            'projected' => $projected,
            'exceeds' => $projected > self::ANNUAL_LEAVE_QUOTA_DAYS,
        ]);
    }

    private function annualLeaveRejectionComment(array $decision): string
    {
        return sprintf(
            'Otomatis ditolak karena total cuti tahunan %d menjadi %d hari, melebihi kuota %d hari.',
            $decision['year'],
            $decision['projected'],
            $decision['quota']
        );
    }
}
