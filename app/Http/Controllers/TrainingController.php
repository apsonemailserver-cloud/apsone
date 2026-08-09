<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class TrainingController extends Controller
{
    public function myCertificates()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Anda harus login untuk melihat sertifikat Anda.');
        }

        $certificates = Certificate::where('user_id', Auth::id())
                                   ->orderBy('end_date', 'asc')
                                   ->paginate(10);

        return view('training.my-certificates', compact('certificates'));
    }

    public function create()
    {
        $types = Certificate::TYPES;
        return view('training.create', compact('types'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'certificate_name' => 'required|string|max:255',
            'certificate_type' => 'nullable|string|max:255',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'certificate_name.required' => 'Nama sertifikat wajib diisi.',
            'start_date.required'       => 'Tanggal mulai berlaku wajib diisi.',
            'end_date.required'         => 'Tanggal berakhir wajib diisi.',
            'end_date.after_or_equal'   => 'Tanggal berakhir harus sama atau setelah tanggal mulai.',
            'certificate_file.mimes'    => 'Format file sertifikat harus PDF, JPG, JPEG, atau PNG.',
            'certificate_file.max'      => 'Ukuran file sertifikat tidak boleh lebih dari 2MB.'
        ]);

        $validatedData['user_id'] = Auth::id();
        $validatedData['status'] = 'Approved';
        $validatedData['submitted_by'] = Auth::user()->fullname ?? Auth::user()->name;

        // Upload file (jika ada)
        if ($request->hasFile('certificate_file')) {
            $file = $request->file('certificate_file');
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $validatedData['certificate_name']);
            $filename = $safeName . '_' . Auth::id() . '_' . time() . '.' . $extension;

            $certDir = public_path('storage/certificates');
            if (!file_exists($certDir)) {
                mkdir($certDir, 0775, true);
            }
            $file->move($certDir, $filename);
            $validatedData['certificate_file'] = 'certificates/' . $filename;
        }

        Certificate::create($validatedData);

        Alert::success('Berhasil', 'Sertifikat training telah berhasil ditambahkan.');
        return redirect()->route('my.certificates');
    }

    public function approval(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->canAccess('training', 'approve')) {
            abort(403, 'Anda tidak memiliki hak akses untuk approval training.');
        }

        $query = Certificate::with('user')
            ->where('status', 'Pending');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('fullname', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                  });
            });
        }

        $pendingCertificates = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('training.approval', compact('pendingCertificates'));
    }

    public function approve($id)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->canAccess('training', 'approve')) {
            abort(403, 'Akses ditolak.');
        }

        $cert = Certificate::findOrFail($id);
        $cert->update(['status' => 'Approved']);

        Alert::success('Disetujui', 'Sertifikat training telah disetujui.');
        return redirect()->back();
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->canAccess('training', 'approve')) {
            abort(403, 'Akses ditolak.');
        }

        $cert = Certificate::findOrFail($id);
        $cert->update([
            'status' => 'Rejected',
            'rejection_reason' => $request->input('reason', 'Ditolak oleh atasan.')
        ]);

        Alert::warning('Ditolak', 'Pengajuan sertifikat training telah ditolak.');
        return redirect()->back();
    }
}