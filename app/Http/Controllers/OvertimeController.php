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

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'duration' => 'required|numeric|min:1',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

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

        // Security Check: Hanya Admin/Leader yg boleh masuk
        if (!in_array($user->role, ['Admin', 'LEADER', 'Head Of Airport Service', 'ASS LEADER'])) {
            abort(403);
        }

        $query = Overtime::with('user')->where('status', 'Pending');

        // Filter Search (NIP / Nama)
        if ($search = $request->input('search')) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Filter Station
        if ($user->role == 'Admin') {
            if ($request->filled('station')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('station', $request->station);
                });
            }
        } else {
            // Jika BUKAN Admin, hanya tampilkan request dari Station yang sama
            $query->whereHas('user', function($q) use ($user) {
                $q->where('station', $user->station);
            });
        }

        $perPage = $request->input('per_page', 20);
        $pendingOvertimes = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();

        return view('overtime.approval', compact('pendingOvertimes'));
    }

    public function approve($id)
    {
        $ot = Overtime::with('user')->findOrFail($id);
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
        if (Auth::user()->role !== 'Admin') { abort(403); }

        $query = Overtime::with('user')->where('status', 'Approved');
        $search = $request->input('search');

        // Filter Search (NIP / Nama)
        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // Filter Station
        if ($request->has('station') && $request->station != null) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('station', $request->station);
            });
        }
        
        // Filter Tanggal (Opsional)
        if ($request->date_start && $request->date_end) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        $overtimes = $query->latest()->paginate(20)->withQueryString();
        $totalHours = $query->sum('duration'); // Total jam untuk payroll

        $stations = \App\Models\Station::where('is_active', 1)->orderBy('name', 'asc')->get();
        return view('overtime.report', compact('overtimes', 'totalHours', 'stations'));
    }

    public function exportExcel(Request $request)
    {
        if (Auth::user()->role !== 'Admin') { abort(403); }

        $query = Overtime::with('user')->where('status', 'Approved');
        $search = $request->input('search');

        if ($search) {
            $query->whereHas('user', function($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($request->has('station') && $request->station != null) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('station', $request->station);
            });
        }
        
        if ($request->date_start && $request->date_end) {
            $query->whereBetween('date', [$request->date_start, $request->date_end]);
        }

        $overtimes = $query->latest()->get();

        return Excel::download(new OvertimeReportExport($overtimes), 'Laporan_Lembur_'.date('YmdHis').'.xlsx');
    }
}