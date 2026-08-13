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
use App\Services\LeaveQuotaService;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\Station;

class LeaveController extends Controller
{
    public const ANNUAL_LEAVE_QUOTA_DAYS = 12;

    protected LeaveQuotaService $quotaService;

    public function __construct(LeaveQuotaService $quotaService)
    {
        $this->quotaService = $quotaService;
    }

    /**
     * Menampilkan daftar riwayat pengajuan cuti.
     * Admin melihat semua, user biasa hanya melihat miliknya.
     */
    public function index()
    {
        $user = Auth::user();

        // Hanya yang punya akses leave.approve, Admin, atau yang memiliki bawahan (atasan) yang bisa melihat daftar approval
        $hasSubordinates = User::where('pic_id', $user->id)->exists();
        if (!$user->canAccess('leave', 'approve') && !$user->isAdmin() && !$hasSubordinates) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $query = Leave::with('user')
            ->select('leaves.*')
            ->join('users', 'users.id', '=', 'leaves.user_id')
            ->latest('leaves.created_at');

        if (!$user->isAdmin()) {
            $query->where('leaves.user_id', '!=', $user->id);
        }

        // Halaman Approval HANYA menampilkan pengajuan yang MASIH MENUNGGU PERSETUJUAN (Pending)
        if ($user->isAdmin()) {
            // Admin dapat melihat semua pengajuan pending
            $query->whereIn('leaves.status', ['pending', 'pending Apron', 'pending Bge']);
        } else {
            // Non-admin hanya melihat pengajuan pending dari bawahan langsungnya (pic_id)
            // Atau untuk tier 2, jika statusnya pending dan $user adalah supervisor dari supervisor pemohon
            $query->whereIn('leaves.status', ['pending', 'pending Apron', 'pending Bge'])
                ->where(function ($q) use ($user) {
                    // Case 1: Status pending Bge/Apron dan supervisor langsung adalah $user
                    $q->where(function ($q1) use ($user) {
                        $q1->whereIn('leaves.status', ['pending Apron', 'pending Bge'])
                           ->where('users.pic_id', $user->id);
                    });

                    // Case 2: Status pending dan:
                    $q->orWhere(function ($q2) use ($user) {
                        $q2->where('leaves.status', 'pending')
                           ->where(function ($q3) use ($user) {
                               // Direct supervisor (hanya jika pemohon adalah Leader/SPV, yaitu punya bawahan)
                               $q3->where(function ($qDirect) use ($user) {
                                   $qDirect->where('users.pic_id', $user->id)
                                           ->whereExists(function ($subExists) {
                                               $subExists->selectRaw('1')
                                                   ->from('users as subs')
                                                   ->whereColumn('subs.pic_id', 'users.id');
                                           });
                               })
                               // Or supervisor's supervisor (jika pemohon adalah staff biasa, yaitu tidak punya bawahan)
                               ->orWhere(function ($qIndirect) use ($user) {
                                   $qIndirect->whereIn('users.pic_id', function ($sub) use ($user) {
                                       $sub->select('id')
                                           ->from('users')
                                           ->where('pic_id', $user->id);
                                   })
                                   ->whereNotExists(function ($subNotExists) {
                                       $subNotExists->selectRaw('1')
                                           ->from('users as subs')
                                           ->whereColumn('subs.pic_id', 'users.id');
                                   });
                               });
                           });
                    });
                });
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
            $query->where('users.' . User::getStationColumn(), $user->station);
        } elseif ((str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) && !in_array($userRole, ['Porter Bge'])) {
            // Leader Bge sees own leaves + Bge subordinates
            $query->where(function ($q) use ($user) {
                $q->where('leaves.user_id', $user->id)
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('users.' . User::getStationColumn(), $user->station)
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
                      $q2->where('users.' . User::getStationColumn(), $user->station)
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
        $user = Auth::user();
        
        // Sync balances
        $this->quotaService->syncBalancesForUser($user, date('Y'));

        // Get active leave types
        $leaveTypes = LeaveType::where('is_active', true)->get();

        // Get user balances for active types in the current year
        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', date('Y'))
            ->get()
            ->keyBy('leave_type_id');

        $annualLeaveUsage = $this->annualLeaveUsage($user->id);
        $leaveBalance = $annualLeaveUsage['balance'];

        return view('leaves.create', compact('leaveBalance', 'leaveTypes', 'balances', 'user'));
    }

    /**
     * Menyimpan data pengajuan cuti baru.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // 1. Initial Validation of type ID and minimum date limit (allow backdate up to 7 days)
        $minBackdate = Carbon::today()->subDays(7)->format('Y-m-d');
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:' . $minBackdate,
            'end_date'   => 'required|date|after_or_equal:start_date',
        ], [
            'start_date.after_or_equal' => 'Tanggal mulai pengajuan cuti maksimal mundur (backdate) 7 hari dari hari ini.',
        ]);

        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // 2. Full validation based on leave type rules
        $rules = [
            'reason'     => 'required|string|max:1000',
            'attachment' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'replacement_employee_name' => 'nullable|string|max:255',
        ];
        $request->validate($rules, [
            'attachment.required' => 'File lampiran bukti cuti wajib diunggah untuk tipe cuti ini.',
            'attachment.image' => 'File lampiran harus berupa foto / gambar.',
            'attachment.mimes' => 'Format foto lampiran harus berupa JPG, JPEG, PNG, atau WEBP.',
            'attachment.max'   => 'Ukuran foto lampiran maksimal 2MB.',
        ]);

        // ===== CEK OVERLAP CUTI =====
        $overlappingLeave = Leave::where('user_id', $user->id)
            ->whereNotIn('status', ['rejected by ho', 'rejected by leader', 'canceled'])
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

        // 3. Verify Eligibility (gender, notice days, quota)
        $status = 'pending';
        $isAutomaticallyRejected = false;
        $rejectionMessage = null;

        $eligibility = $this->quotaService->verifyEligibility($user, $leaveType, $totalDays, $startDate, $endDate);
        if (!$eligibility['eligible']) {
            if ($leaveType->name === 'Cuti Tahunan') {
                $status = 'rejected by ho';
                $isAutomaticallyRejected = true;
                $rejectionMessage = $eligibility['message'];
            } else {
                Alert::error('Gagal Mengajukan Cuti', $eligibility['message']);
                return redirect()->back()->withInput();
            }
        }

        if (!$isAutomaticallyRejected) {
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
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave_attachments', 'public');
        }

        $leaveData = [
            'user_id'       => Auth::id(),
            'leave_type_id' => $leaveType->id,
            'leave_type'    => $leaveType->name,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'reason'        => $request->reason,
            'total_days'    => $totalDays,
            'attachment_path' => $attachmentPath,
            'replacement_employee_name' => $request->replacement_employee_name,
            'status'        => $status,
        ];

        if ($isAutomaticallyRejected) {
            $leaveData['manager_comment'] = $rejectionMessage;
            $leaveData['rejected_by'] = Auth::id();
        } elseif ($status === 'approved') {
            $leaveData['approved_by'] = Auth::id();
            $leaveData['approved_at'] = now();
        }

        $leave = Leave::create($leaveData);

        // Sync balance after creating
        $this->quotaService->syncBalancesForUser($user, $startDate->year);

        if ($isAutomaticallyRejected) {
            RequestNotificationMailService::sendDecisionEmail(
                $user,
                'Cuti (' . $leaveType->name . ')',
                'Rejected',
                [
                    'Jenis Cuti'         => $leaveType->name,
                    'Tanggal Mulai'      => $startDate->translatedFormat('d F Y'),
                    'Tanggal Selesai'    => $endDate->translatedFormat('d F Y'),
                    'Total Hari'         => $totalDays . ' Hari',
                    'Alasan'             => $request->reason,
                    'Keterangan'         => $rejectionMessage,
                    'Status'             => 'Ditolak Otomatis (Kuota Terlampaui)',
                ],
                'Sistem (Kuota Terlampaui)'
            );

            Alert::warning('Ditolak Otomatis', $rejectionMessage);
            return redirect()->route('leaves.pengajuan');
        }

        // Kirim email pemberitahuan ke pemohon (creator)
        if ($status === 'approved') {
            RequestNotificationMailService::sendDecisionEmail(
                $user,
                'Cuti (' . $leaveType->name . ')',
                'Approved',
                [
                    'Jenis Cuti'         => $leaveType->name,
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
                'Cuti (' . $leaveType->name . ')',
                [
                    'Jenis Cuti'         => $leaveType->name,
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
        $hasSubordinates = User::where('pic_id', $user->id)->exists();
        if (!$user->canAccess('leave', 'approve') && !$user->isAdmin() && !$hasSubordinates) {
            abort(403, 'Anda tidak memiliki akses untuk menyetujui pengajuan ini.');
        }

        if (!$user->isAdmin() && (string) $leave->user_id === (string) $user->id) {
            abort(403, 'Anda tidak dapat menyetujui pengajuan cuti Anda sendiri.');
        }

        $validStatuses = ['approved', 'rejected by ho', 'pending', 'rejected by leader', 'canceled'];

        $request->validate([
            'status' => ['required', Rule::in($validStatuses)]
        ]);

        $status = $request->status;

        // Validasi transisi status dan hak akses menggunakan pic_id
        if (!$user->isAdmin()) {
            $applicant = $leave->user;
            if (!$applicant) {
                abort(404, 'Data pemohon tidak ditemukan.');
            }

            if (in_array($leave->status, ['pending Apron', 'pending Bge'])) {
                // Harus merupakan atasan langsung
                if ((string) $applicant->pic_id !== (string) $user->id) {
                    abort(403, 'Anda hanya dapat menyetujui pengajuan dari bawahan langsung Anda.');
                }
                if (!in_array($status, ['pending', 'rejected by leader'])) {
                    abort(400, 'Status tidak valid untuk tingkat persetujuan pertama.');
                }
            } elseif ($leave->status === 'pending') {
                // Harus merupakan atasan langsung (jika tingkat 1 langsung ke HOAS) atau atasan dari atasan langsung (HOAS)
                $hasSubordinates = User::where('pic_id', $applicant->id)->exists();
                if ($hasSubordinates) {
                    // Pemohon adalah Leader/SPV, maka yang boleh menyetujui adalah atasan langsungnya
                    $isDirectSuper = (string) $applicant->pic_id === (string) $user->id;
                    if (!$isDirectSuper) {
                        abort(403, 'Anda tidak memiliki wewenang struktural untuk menyetujui pengajuan ini.');
                    }
                } else {
                    // Pemohon adalah staff biasa, maka yang boleh menyetujui adalah atasan dari atasan langsungnya (HOAS)
                    $isSuperOfSuper = $applicant->pic && (string) $applicant->pic->pic_id === (string) $user->id;
                    if (!$isSuperOfSuper) {
                        abort(403, 'Anda tidak memiliki wewenang struktural untuk menyetujui pengajuan ini.');
                    }
                }
                if (!in_array($status, ['approved', 'rejected by ho'])) {
                    abort(400, 'Status tidak valid untuk tingkat persetujuan akhir.');
                }
            } else {
                abort(403, 'Pengajuan ini tidak sedang dalam status menunggu persetujuan.');
            }
        }

        $leaveType = $leave->leaveType;
        if (!$leaveType && $leave->leave_type === 'Cuti Tahunan') {
            $leaveType = LeaveType::where('name', 'Cuti Tahunan')->first();
        }

        if ($status === 'approved' && $leaveType && !$leaveType->is_unlimited) {
            $eligibility = $this->quotaService->verifyEligibility(
                $leave->user,
                $leaveType,
                (int) $leave->total_days,
                Carbon::parse($leave->start_date),
                Carbon::parse($leave->end_date),
                $leave->id
            );

            if (!$eligibility['eligible']) {
                $leave->status = 'rejected by ho';
                $leave->rejected_by = Auth::id();
                $leave->approved_by = null;
                $leave->approved_at = null;
                $leave->manager_comment = $eligibility['message'];
                $leave->save();

                if ($leave->user) {
                    $this->quotaService->syncBalancesForUser($leave->user, Carbon::parse($leave->start_date)->year);
                }

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
                            'Keterangan'      => $eligibility['message'],
                            'Status'          => 'Ditolak Otomatis (Kuota Terlampaui)',
                        ],
                        Auth::user()->fullname
                    );
                }

                Alert::warning('Ditolak Otomatis', $eligibility['message']);
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

        if ($leave->user) {
            $this->quotaService->syncBalancesForUser($leave->user, Carbon::parse($leave->start_date)->year);
        }

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

        $user = User::find($userId);
        if ($user) {
            $this->quotaService->syncBalancesForUser($user, $year);
        }

        $annualType = LeaveType::where('name', 'Cuti Tahunan')->first();
        $balance = $annualType 
            ? LeaveBalance::where('user_id', $userId)->where('leave_type_id', $annualType->id)->where('year', $year)->first() 
            : null;

        $quota = $balance ? $balance->total_quota : self::ANNUAL_LEAVE_QUOTA_DAYS;
        
        if ($balance) {
            $used = $balance->used_days;
        } else {
            $query = Leave::where('user_id', $userId)
                ->where('status', 'approved')
                ->where('leave_type', 'Cuti Tahunan')
                ->whereYear('start_date', $year);

            if ($excludeLeaveId !== null) {
                $query->where('id', '!=', $excludeLeaveId);
            }

            $used = (int) $query->sum('total_days');
        }

        return [
            'quota' => $quota,
            'used' => $used,
            'balance' => max(0, $quota - $used),
            'raw_balance' => $quota - $used,
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

    /**
     * Display list of leave balances for all users (for Admin & Atasan).
     */
    public function balances(Request $request)
    {
        $currentUser = Auth::user();
        $year = (int) $request->input('year', date('Y'));
        $stationId = $request->input('station_id');
        $search = trim($request->input('search'));

        // Ensure balances exist for target year
        $this->quotaService->syncAllBalances($year);

        // Fetch active leave types
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        // Build User query with balances
        $userQuery = User::where('is_active', true)
            ->with(['stationRelation', 'leaveBalances' => function($q) use ($year) {
                $q->where('year', $year)->with('leaveType');
            }]);

        // Access control: if not Admin, scope by station
        if (!$currentUser->isAdmin()) {
            if ($currentUser->station) {
                $userQuery->where('station', $currentUser->station);
            } else {
                $userQuery->where('id', $currentUser->id);
            }
        } elseif ($stationId) {
            $userQuery->where('station', $stationId);
        }

        if ($search) {
            $userQuery->where(function($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $users = $userQuery->orderBy('fullname')->paginate(15)->appends($request->all());
        $stations = Station::all();

        return view('leaves.balances', compact('users', 'leaveTypes', 'stations', 'year', 'stationId', 'search'));
    }
}
