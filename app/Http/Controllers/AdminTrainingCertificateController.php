<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminTrainingCertificateController extends Controller
{
    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $query = Certificate::leftJoin('users', 'certificates.user_id', '=', 'users.id')
            ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
            ->select('certificates.*', 'employees.fullname');

        // User biasa hanya dapat melihat sertifikat miliknya sendiri, Admin dapat melihat semua
        if (!$user->isAdmin() && $user->role !== 'Admin') {
            $query->where('certificates.user_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('certificates.certificate_name', 'like', "%{$search}%")
                    ->orWhere('employees.fullname', 'like', "%{$search}%")
                    ->orWhere('users.id', 'like', "%{$search}%");
            });
        }

        $certificates = $query->orderBy('certificates.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('training.admin.index', compact('certificates'));
    }

    public function create()
    {
        $users = User::with('employee')
            ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
            ->orderBy('employees.fullname', 'asc')
            ->select('users.id', 'employees.fullname')
            ->get();

        return view('training.admin.create', compact('users'));
    }

    public function store(Request $request)
    {
        if (!\Illuminate\Support\Facades\Auth::user()->isAdmin() && \Illuminate\Support\Facades\Auth::user()->role !== 'Admin') {
            $request->merge(['user_id' => \Illuminate\Support\Facades\Auth::id()]);
        }

        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'certificate_name' => 'required|string|max:255',
            'certificate_type' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'user_id.required' => 'Staff wajib dipilih.',
            'certificate_name.required' => 'Nama sertifikat wajib diisi.',
            'start_date.required' => 'Tanggal mulai berlaku wajib diisi.',
            'end_date.required' => 'Tanggal akhir berlaku wajib diisi.',
            'certificate_file.mimes' => 'Format file sertifikat harus PDF, JPG, JPEG, atau PNG.',
            'certificate_file.max' => 'Ukuran file sertifikat tidak boleh lebih dari 2MB.',
        ]);

        // Format tanggal
        $validatedData['start_date'] = date('Y-m-d', strtotime($validatedData['start_date']));
        $validatedData['end_date'] = date('Y-m-d', strtotime($validatedData['end_date']));

        // Upload file (jika ada)
        if ($request->hasFile('certificate_file')) {
            $file = $request->file('certificate_file');
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            
            // Bersihkan nama sertifikat agar aman untuk nama file (hilangkan karakter aneh/slash)
            $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $validatedData['certificate_name']);
            $filename = $safeName . '_' . $validatedData['user_id'] . '_' . time() . '.' . $extension;
            
            // Simpan langsung ke public/storage/certificates
            $certDir = public_path('storage/certificates');
            if (!file_exists($certDir)) {
                mkdir($certDir, 0775, true);
            }
            $file->move($certDir, $filename);
            
            $validatedData['certificate_file'] = 'certificates/' . $filename;
        }

        Certificate::create($validatedData);

        return redirect()
            ->route('admin.training.certificates.index')
            ->with('success', 'Sertifikat berhasil ditambahkan!');
    }

    public function show(Certificate $certificate)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->isAdmin() && $user->role !== 'Admin' && $certificate->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        return view('training.admin.show', compact('certificate'));
    }

    public function edit(Certificate $certificate)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->isAdmin() && $user->role !== 'Admin' && $certificate->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        if (!$user->isAdmin() && $user->role !== 'Admin') {
            $users = User::where('id', $user->id)->get(['id', 'fullname']);
        } else {
            $users = User::orderBy('fullname')->get(['id', 'fullname']);
        }

        return view('training.admin.edit', compact('certificate', 'users'));
    }

    public function update(Request $request, Certificate $certificate)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->isAdmin() && $user->role !== 'Admin' && $certificate->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        if (!$user->isAdmin() && $user->role !== 'Admin') {
            $request->merge(['user_id' => $certificate->user_id]);
        }

        $validatedData = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id'),
            ],
            'certificate_name' => 'required|string|max:255',
            'certificate_type' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'user_id.required' => 'Staff wajib dipilih.',
            'certificate_name.required' => 'Nama sertifikat wajib diisi.',
            'start_date.required' => 'Tanggal mulai berlaku wajib diisi.',
            'end_date.required' => 'Tanggal akhir berlaku wajib diisi.',
            'certificate_file.mimes' => 'Format file sertifikat harus PDF, JPG, JPEG, atau PNG.',
            'certificate_file.max' => 'Ukuran file sertifikat tidak boleh lebih dari 2MB.',
        ]);

        if ($request->hasFile('certificate_file')) {
            // Hapus file lama jika ada
            if ($certificate->certificate_file) {
                $oldPath = public_path('storage/' . $certificate->certificate_file);
                if (file_exists($oldPath) && is_file($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            $file = $request->file('certificate_file');
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            
            // Bersihkan nama sertifikat agar aman untuk nama file
            $safeName = preg_replace('/[^A-Za-z0-9\-]/', '_', $validatedData['certificate_name']);
            $filename = $safeName . '_' . $validatedData['user_id'] . '_' . time() . '.' . $extension;
            
            $certDir = public_path('storage/certificates');
            if (!file_exists($certDir)) {
                mkdir($certDir, 0775, true);
            }
            $file->move($certDir, $filename);
            
            $validatedData['certificate_file'] = 'certificates/' . $filename;
        } elseif ($request->boolean('remove_file')) {
            if ($certificate->certificate_file) {
                $oldPath = public_path('storage/' . $certificate->certificate_file);
                if (file_exists($oldPath) && is_file($oldPath)) {
                    unlink($oldPath);
                }
                $validatedData['certificate_file'] = null;
            }
        }

        $certificate->update($validatedData);

        return redirect()->route('admin.training.certificates.index')->with('success', 'Sertifikat berhasil diperbarui!');
    }

    public function destroy(Certificate $certificate)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->isAdmin() && $user->role !== 'Admin' && $certificate->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }
        if ($certificate->certificate_file) {
            $oldPath = public_path('storage/' . $certificate->certificate_file);
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        $certificate->delete();

        return redirect()->route('admin.training.certificates.index')->with('success', 'Sertifikat berhasil dihapus!');
    }
}
