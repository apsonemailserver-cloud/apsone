<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Blacklist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;

class BlacklistController extends Controller
{
    // Tampilkan Daftar Blacklist
    public function index(Request $request)
    {
        abort_unless(Auth::user()->canAccess('blacklist', 'view'), 403);

        $query = Blacklist::query();

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('fullname', 'like', '%'.$request->search.'%')
                  ->orWhere('nik', 'like', '%'.$request->search.'%')
                  ->orWhere('reason', 'like', '%'.$request->search.'%');
            });
        }

        $blacklists = $query->latest()->paginate(10)->withQueryString();
        return view('blacklist.index', compact('blacklists'));
    }

    // PROSES BAN USER (DARI HALAMAN USER / MONITOR STATION)
    public function store(Request $request)
    {
        abort_unless(Auth::user()->canAccess('blacklist', 'create'), 403);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason'  => 'required|string|min:3'
        ], [
            'user_id.required' => 'Staff wajib dipilih.',
            'user_id.exists'   => 'Data staff tidak ditemukan.',
            'reason.required'  => 'Alasan pelanggaran wajib diisi.',
            'reason.min'       => 'Alasan pelanggaran minimal 3 karakter.'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $blacklistKey = trim((string) ($user->no_nik ?: $user->id));

            if ($blacklistKey === '') {
                Alert::error('Gagal', 'NIK/NIP staff tidak ditemukan, blacklist tidak dapat diproses.');
                return back();
            }

            $alreadyBlacklisted = Blacklist::where('nik', $blacklistKey)->first();

            if ($alreadyBlacklisted) {
                $user->forceFill(['is_active' => 0])->save();
                Alert::warning('Sudah Blacklist', "{$user->fullname} sudah ada di daftar blacklist dan akun telah dinonaktifkan.");
                return redirect()->route('blacklist.index');
            }

            $bannedBy = Auth::user()->fullname ?? Auth::user()->name ?? 'Admin';
            $station  = $user->station ?: (Auth::user()->station ?: '-');

            DB::transaction(function () use ($user, $request, $blacklistKey, $bannedBy, $station) {
                // 1. Simpan ke Tabel Blacklist
                Blacklist::create([
                    'nik'       => $blacklistKey,
                    'fullname'  => $user->fullname,
                    'reason'    => trim($request->reason),
                    'station'   => $station,
                    'banned_by' => $bannedBy
                ]);

                // 2. Nonaktifkan Akun User (Kill Switch)
                $user->forceFill(['is_active' => 0])->save();
            });

            Alert::success('Sanksi Tegas', "{$user->fullname} berhasil di-blacklist dan akun telah dinonaktifkan.");
            return redirect()->route('blacklist.index');
        } catch (\Exception $e) {
            Log::error('Gagal memproses blacklist:', ['error' => $e->getMessage()]);
            Alert::error('Gagal', 'Terjadi kesalahan saat memproses blacklist: '.$e->getMessage());
            return back()->withInput();
        }
    }

    // Hapus dari Blacklist (Jika ternyata salah paham/banding diterima)
    public function destroy($id)
    {
        abort_unless(Auth::user()->canAccess('blacklist', 'delete'), 403);

        Blacklist::destroy($id);
        Alert::success('Berhasil', 'Data dihapus dari daftar hitam.');
        return back();
    }
}

