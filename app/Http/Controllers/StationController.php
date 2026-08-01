<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Station;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Auth; // Tambahkan ini agar Auth::user() terbaca
use Illuminate\Support\Facades\DB;
use App\Models\User;

class StationController extends Controller
{
    // =================================================================
    // 1. FITUR BUKA STATION BARU
    // =================================================================
    private function availableRoles(): array
    {
        return User::query()
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->select('role')
            ->distinct()
            ->orderBy('role')
            ->pluck('role')
            ->filter()
            ->values()
            ->all();
    }

    public function create()
    {
        $availableRoles = $this->availableRoles();
        return view('stations.create', compact('availableRoles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:stations,code|max:3|alpha',
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:1',
            'role' => 'nullable',
        ]);

        $roles = $request->role;
        if (is_array($roles)) {
            $roles = implode(', ', $roles);
        }

        Station::create([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'is_active' => true,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
            'role' => $roles,
        ]);

        Alert::success('Berhasil', 'Station baru berhasil dibuka!');
        return redirect()->route('stations.index');
    }

    // =================================================================
    // 2. FITUR MANAJEMEN STATION (INDEX & KILL SWITCH)
    // =================================================================

    // Menampilkan Daftar Station
    public function index()
    {
        abort_unless(Auth::user()->canAccess('station', 'view'), 403, 'Akses Ditolak');

        $perPage = request()->input('per_page', 10);
        $stations = Station::paginate($perPage)->withQueryString();
        return view('stations.index', compact('stations'));
    }

    // Proses Ganti Status ON/OFF
    public function toggleStatus($id)
    {
        abort_unless(Auth::user()->canAccess('station', 'edit'), 403, 'Akses Ditolak');

        $station = Station::findOrFail($id);

        // Balik statusnya (Jika 1 jadi 0, Jika 0 jadi 1)
        $station->is_active = !$station->is_active;
        $station->save();

        // Pesan Notifikasi
        $statusText = $station->is_active ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';

        Alert::success('Berhasil', "Station {$station->code} berhasil {$statusText}.");

        return back();
    }

    // Menampilkan Form Ubah Station
    public function edit($id)
    {
        $station = Station::findOrFail($id);
        $availableRoles = $this->availableRoles();

        return view('stations.edit', compact('station', 'availableRoles'));
    }

    // Proses Ubah Station
    public function update(Request $request, Station $station)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:1',
            'role' => 'nullable',
        ]);

        $roles = $request->role;
        if (is_array($roles)) {
            $roles = implode(', ', $roles);
        }

        $station->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
            'role' => $roles,
        ]);

        Alert::success('Berhasil', 'Station berhasil diubah!');
        return redirect()->route('stations.index');
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // Ambil data station berdasarkan id
            $station = Station::findOrFail($id);

            // Hapus user yang station-nya sama dengan code station
            User::where('station', $station->code)->delete();

            // Hapus station
            $station->delete();

            DB::commit();

            return redirect()
                ->route('stations.index')
                ->with('success', 'Station dan user terkait berhasil dihapus.');
        } catch (\Exception $e) {

            DB::rollBack();

            return redirect()
                ->route('stations.index')
                ->with('error', $e->getMessage());
        }
    }
} // <--- PENUTUP CLASS HARUS ADA DI SINI (PALING BAWAH)