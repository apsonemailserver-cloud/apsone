<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class FaceSampleController extends Controller
{
    // Posisi foto yang wajib ada
    const POSITIONS = ['front', 'right', 'left'];
    const STORAGE_DISK = 'public';
    const BASE_DIR = 'face_samples';

    /**
     * Cek hak akses — hanya Admin
     */
    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()->isAdmin(), 403, 'Hanya Admin yang dapat mengelola foto referensi wajah.');
    }

    /**
     * Ambil path direktori foto referensi user
     */
    public static function userDir(int|string $userId): string
    {
        return self::BASE_DIR . '/' . $userId;
    }

    /**
     * Ambil status kelengkapan foto referensi user
     * Return array ['front' => true/false, 'right' => true/false, 'left' => true/false]
     */
    public static function getStatus(int|string $userId): array
    {
        $status = [];
        foreach (self::POSITIONS as $pos) {
            $path = self::userDir($userId) . '/' . $pos . '.jpg';
            $status[$pos] = Storage::disk(self::STORAGE_DISK)->exists($path);
        }
        return $status;
    }

    /**
     * Cek apakah user sudah punya 3 foto lengkap
     */
    public static function isComplete(int|string $userId): bool
    {
        return !in_array(false, self::getStatus($userId), true);
    }

    /**
     * Halaman manajemen foto referensi wajah untuk user tertentu (Admin)
     */
    public function index(User $user)
    {
        $this->authorizeAdmin();

        $status = self::getStatus($user->id);
        $photos = [];

        foreach (self::POSITIONS as $pos) {
            $path = self::userDir($user->id) . '/' . $pos . '.jpg';
            $photos[$pos] = $status[$pos]
                ? Storage::disk(self::STORAGE_DISK)->url($path)
                : null;
        }

        $isComplete = !in_array(false, $status, true);

        return view('user.face_samples', compact('user', 'photos', 'status', 'isComplete'));
    }

    /**
     * Upload foto referensi (satu posisi sekaligus — front/right/left)
     */
    public function store(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $request->validate([
            'position' => 'required|in:front,right,left',
            'photo'    => 'required|string', // base64 dari kamera / file upload base64
        ]);

        $position = $request->position;
        $photoData = $request->photo;

        // Support base64 dari input kamera browser
        if (str_contains($photoData, ',')) {
            $photoData = explode(',', $photoData)[1];
        }

        $decodedData = base64_decode($photoData);
        if (!$decodedData) {
            return back()->withErrors(['photo' => 'Data foto tidak valid.']);
        }

        $dir  = self::userDir($user->id);
        $path = $dir . '/' . $position . '.jpg';

        Storage::disk(self::STORAGE_DISK)->makeDirectory($dir);
        Storage::disk(self::STORAGE_DISK)->put($path, $decodedData);

        // Update face_registered_at jika ketiga foto sudah lengkap
        if (self::isComplete($user->id)) {
            $user->update(['face_registered_at' => now()]);
        }

        Alert::success('Berhasil', ucfirst($position === 'front' ? 'Depan' : ($position === 'right' ? 'Kanan' : 'Kiri')) . ' foto referensi berhasil disimpan.');
        return redirect()->route('users.face-samples.index', $user->id);
    }

    /**
     * Upload foto referensi via file upload biasa (multipart form)
     */
    public function storeFile(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $request->validate([
            'position' => 'required|in:front,right,left',
            'photo'    => 'required|image|max:5120', // max 5MB
        ]);

        $position = $request->position;
        $dir  = self::userDir($user->id);
        $path = $dir . '/' . $position . '.jpg';

        // Simpan dan convert ke JPEG
        $image = $request->file('photo');
        $image->storeAs($dir, $position . '.' . $image->getClientOriginalExtension(), self::STORAGE_DISK);

        // Rename ke .jpg jika beda ekstensi
        $uploaded = $dir . '/' . $position . '.' . $image->getClientOriginalExtension();
        if ($uploaded !== $path) {
            Storage::disk(self::STORAGE_DISK)->move($uploaded, $path);
        }

        if (self::isComplete($user->id)) {
            $user->update(['face_registered_at' => now()]);
        }

        Alert::success('Berhasil', 'Foto ' . $position . ' berhasil diupload.');
        return redirect()->route('users.face-samples.index', $user->id);
    }

    /**
     * API endpoint: Kembalikan URL 3 foto referensi user (untuk face-api.js di kamera)
     * Hanya untuk user yang sedang login
     */
    public function apiShow(Request $request)
    {
        $user = Auth::user();
        $status = self::getStatus($user->id);
        $photos = [];

        foreach (self::POSITIONS as $pos) {
            $path = self::userDir($user->id) . '/' . $pos . '.jpg';
            $photos[$pos] = $status[$pos]
                ? Storage::disk(self::STORAGE_DISK)->url($path)
                : null;
        }

        $descriptorsPath = self::userDir($user->id) . '/descriptors.json';
        $descriptors = null;
        if (Storage::disk(self::STORAGE_DISK)->exists($descriptorsPath)) {
            $descriptors = json_decode(Storage::disk(self::STORAGE_DISK)->get($descriptorsPath), true);
        }

        return response()->json([
            'user_id'     => $user->id,
            'is_complete' => self::isComplete($user->id),
            'photos'      => $photos,
            'descriptors' => $descriptors,
            'positions'   => self::POSITIONS,
        ]);
    }

    /**
     * Self-service API: Simpan foto referensi wajah user saat verifikasi pertama kali di kamera absensi
     * Menerima payload JSON: { front: base64, right: base64, left: base64, descriptors?: array }
     */
    public function storeSelf(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'front' => 'required|string',
            'right' => 'required|string',
            'left'  => 'required|string',
        ]);

        $dir = self::userDir($user->id);
        Storage::disk(self::STORAGE_DISK)->makeDirectory($dir);

        foreach (self::POSITIONS as $pos) {
            $photoData = $request->$pos;
            if (str_contains($photoData, ',')) {
                $photoData = explode(',', $photoData)[1];
            }
            $decodedData = base64_decode($photoData);
            if ($decodedData) {
                $path = $dir . '/' . $pos . '.jpg';
                Storage::disk(self::STORAGE_DISK)->put($path, $decodedData);
            }
        }

        if ($request->has('descriptors') && is_array($request->descriptors)) {
            $descriptorsPath = $dir . '/descriptors.json';
            Storage::disk(self::STORAGE_DISK)->put($descriptorsPath, json_encode($request->descriptors));
        }

        $user->update(['face_registered_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Foto referensi NIP berhasil didaftarkan.',
            'face_registered_at' => $user->face_registered_at,
        ]);
    }

    /**
     * Hapus semua foto referensi user (Admin)
     */
    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        $dir = self::userDir($user->id);
        Storage::disk(self::STORAGE_DISK)->deleteDirectory($dir);

        $user->update(['face_registered_at' => null]);

        Alert::success('Berhasil', 'Semua foto referensi wajah ' . $user->fullname . ' telah dihapus.');
        return redirect()->route('users.face-samples.index', $user->id);
    }
}
