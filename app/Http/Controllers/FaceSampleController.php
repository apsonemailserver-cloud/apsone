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

        $photos_b64 = [];
        foreach (self::POSITIONS as $pos) {
            $path = self::userDir($user->id) . '/' . $pos . '.jpg';
            if ($status[$pos] && Storage::disk(self::STORAGE_DISK)->exists($path)) {
                $content = Storage::disk(self::STORAGE_DISK)->get($path);
                $enhanced = self::enhanceImageForFaceDetection($content);
                $photos_b64[$pos] = 'data:image/jpeg;base64,' . base64_encode($enhanced ?? $content);
            } else {
                $photos_b64[$pos] = null;
            }
        }

        $descriptorsPath = self::userDir($user->id) . '/descriptors.json';
        $descriptors = null;
        if (Storage::disk(self::STORAGE_DISK)->exists($descriptorsPath)) {
            $descriptors = json_decode(Storage::disk(self::STORAGE_DISK)->get($descriptorsPath), true);
        } elseif (self::isComplete($user->id)) {
            $descriptors = self::extractAndSaveDescriptors($user->id);
        }

        return response()->json([
            'user_id'     => $user->id,
            'is_complete' => self::isComplete($user->id),
            'photos'      => $photos,
            'photos_b64'  => $photos_b64,
            'descriptors' => $descriptors,
            'positions'   => self::POSITIONS,
        ]);
    }

    /**
     * Ekstrak dan simpan descriptors.json untuk user dari 3 foto referensi
     */
    public static function extractAndSaveDescriptors(int|string $userId): ?array
    {
        $dir = self::userDir($userId);
        $refPaths = [];
        foreach (self::POSITIONS as $pos) {
            $relPath = $dir . '/' . $pos . '.jpg';
            $absPath = Storage::disk(self::STORAGE_DISK)->path($relPath);
            if (file_exists($absPath)) {
                $refPaths[] = $absPath;
            }
        }

        if (empty($refPaths)) {
            return null;
        }

        $scriptPath = base_path('scripts/face_compare.py');
        $pythonBin = self::getPythonBinary();

        if ($pythonBin && file_exists($scriptPath)) {
            $cmd = escapeshellcmd($pythonBin) . ' ' . escapeshellarg($scriptPath) . ' --extract-only --refs ' . implode(' ', array_map('escapeshellarg', $refPaths));
            $output = shell_exec($cmd);
            if ($output) {
                $res = json_decode(trim($output), true);
                if (!empty($res['descriptors']) && is_array($res['descriptors'])) {
                    $descriptorsPath = $dir . '/descriptors.json';
                    Storage::disk(self::STORAGE_DISK)->put($descriptorsPath, json_encode($res['descriptors']));
                    return $res['descriptors'];
                }
            }
        }
        return null;
    }

    /**
     * Enhance backlit / low-contrast face photos using PHP GD:
     * 1. Auto-levels (stretch histogram to full 0-255 range)
     * 2. Adaptive brightness boost if image is too dark (median < 100)
     * 3. Mild contrast boost
     * Returns enhanced JPEG binary string, or null if GD not available.
     */
    private static function enhanceImageForFaceDetection(string $jpegData): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $img = @imagecreatefromstring($jpegData);
        if (!$img) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // Build luminance histogram to find min/max brightness
        $min = 255; $max = 0; $sum = 0; $count = 0;
        $step = max(1, (int)($w * $h / 2000)); // sample ~2000 pixels for speed
        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                // Luminance (perceived brightness)
                $luma = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                if ($luma < $min) $min = $luma;
                if ($luma > $max) $max = $luma;
                $sum += $luma;
                $count++;
            }
        }
        $mean = $count > 0 ? $sum / $count : 128;

        // Auto-levels stretch: remap [min, max] → [0, 255]
        // Only apply if there's meaningful dynamic range to stretch
        if ($max - $min > 30) {
            $range = $max - $min;
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    $rgb = imagecolorat($img, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $r = (int)(($r - $min) / $range * 255);
                    $g = (int)(($g - $min) / $range * 255);
                    $b = (int)(($b - $min) / $range * 255);
                    $r = max(0, min(255, $r));
                    $g = max(0, min(255, $g));
                    $b = max(0, min(255, $b));
                    imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
                }
            }
        }

        // Adaptive brightness boost for backlit / dark face scenarios
        // Mean < 100 = face area is underexposed; boost brightness
        if ($mean < 100) {
            $boost = (int)(min(80, (100 - $mean) * 0.9));
            imagefilter($img, IMG_FILTER_BRIGHTNESS, $boost);
        }

        // Mild contrast enhancement
        imagefilter($img, IMG_FILTER_CONTRAST, -15);

        ob_start();
        imagejpeg($img, null, 90);
        $output = ob_get_clean();
        imagedestroy($img);

        return $output ?: null;
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
        } else {
            self::extractAndSaveDescriptors($user->id);
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

    /**
     * API: Server-side ML face verification via Python face_recognition (dlib ResNet)
     * POST /attendance/face-verify
     * Body: { live_b64: "data:image/jpeg;base64,..." }
     * Returns: { matched: bool, distance: float, match_pct: float, method: string }
     */
    public function verifyFace(Request $request)
    {
        $user = Auth::user();
        $request->validate(['live_b64' => 'required|string']);

        // Check if reference photos exist
        if (!self::isComplete($user->id)) {
            return response()->json([
                'matched'   => false,
                'distance'  => null,
                'match_pct' => 0,
                'method'    => 'none',
                'error'     => 'Foto referensi NIP belum mendaftar.',
            ], 200);
        }

        $dir = self::userDir($user->id);
        $descriptorsPath = $dir . '/descriptors.json';
        $cachedDescriptors = null;

        if (Storage::disk(self::STORAGE_DISK)->exists($descriptorsPath)) {
            $cachedDescriptors = json_decode(Storage::disk(self::STORAGE_DISK)->get($descriptorsPath), true);
        }

        // Build reference photo paths
        $refPaths = [];
        foreach (self::POSITIONS as $pos) {
            $relPath = $dir . '/' . $pos . '.jpg';
            $absPath = Storage::disk(self::STORAGE_DISK)->path($relPath);
            if (file_exists($absPath)) {
                $refPaths[] = $absPath;
            }
        }

        if (empty($refPaths) && empty($cachedDescriptors)) {
            return response()->json([
                'matched'   => false,
                'distance'  => null,
                'match_pct' => 0,
                'method'    => 'none',
                'error'     => 'File foto referensi tidak ditemukan di server.',
            ], 200);
        }

        $scriptPath = base_path('scripts/face_compare.py');
        $pythonBin = self::getPythonBinary();

        if (file_exists($scriptPath) && $pythonBin) {
            $payload = json_encode([
                'live_b64'        => $request->live_b64,
                'ref_paths'       => $refPaths,
                'ref_descriptors' => $cachedDescriptors,
            ]);

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $cmd = escapeshellcmd($pythonBin) . ' ' . escapeshellarg($scriptPath);
            $process = proc_open($cmd, $descriptors, $pipes);

            if (is_resource($process)) {
                fwrite($pipes[0], $payload);
                fclose($pipes[0]);

                $output = stream_get_contents($pipes[1]);
                $errorOutput = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                proc_close($process);

                if ($output) {
                    $result = json_decode(trim($output), true);
                    if (is_array($result) && !isset($result['error'])) {
                        // Auto-cache descriptors if not cached yet
                        if (!$cachedDescriptors && !empty($result['descriptors'])) {
                            Storage::disk(self::STORAGE_DISK)->put($descriptorsPath, json_encode($result['descriptors']));
                        }
                        return response()->json(array_merge($result, ['method' => 'dlib_resnet']));
                    }
                    return response()->json(array_merge($result ?? [], ['method' => 'dlib_resnet_error']), 200);
                }
            }
        }

        // Error / Python failed -> STRICT: Reject verification
        return response()->json([
            'matched'   => false,
            'distance'  => null,
            'match_pct' => 0,
            'method'    => 'failed',
            'error'     => 'Server ML verification process failed.',
        ], 200);
    }

    /**
     * Locate working Python 3 binary with face_recognition installed
     */
    private static function getPythonBinary(): ?string
    {
        static $cachedBin = false;
        if ($cachedBin !== false) return $cachedBin;

        $candidates = [
            'python3',
            '/usr/local/bin/python3',
            '/usr/bin/python3',
            '/opt/homebrew/bin/python3',
            '/Library/Frameworks/Python.framework/Versions/Current/bin/python3',
        ];

        foreach ($candidates as $bin) {
            $test = shell_exec(escapeshellcmd($bin) . ' -c "import face_recognition; print(1)" 2>/dev/null');
            if (trim($test ?? '') === '1') {
                $cachedBin = $bin;
                return $cachedBin;
            }
        }

        $cachedBin = null;
        return null;
    }
}

