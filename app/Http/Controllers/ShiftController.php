<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RealRashid\SweetAlert\Facades\Alert;

use App\Http\Controllers\Traits\PreservesIndexState;

class ShiftController extends Controller
{
    use PreservesIndexState;

    public function index(Request $request)
    {
        if (! Auth::user()->canAccess('shift', 'view')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        if ($redirect = $this->checkIndexState($request, 'shift', '#^/shift(/\d+)?(/edit)?$|/shift/create#')) {
            return $redirect;
        }

        $perPage = $request->input('per_page', 30);
        $shifts = Shift::orderByRaw("CASE WHEN id LIKE 'P%' THEN 1 WHEN id LIKE 'S%' THEN 2 WHEN id LIKE 'M%' THEN 3 WHEN LOWER(id) = 'off' THEN 5 ELSE 4 END, LENGTH(id) ASC, id ASC")
            ->paginate($perPage)
            ->withQueryString();

        return view('shift.index', compact('shifts'));
    }

    public function getNextId(Request $request)
    {
        $name = $request->query('name', 'Pagi');
        $nextId = Shift::generateNextId($name);
        return response()->json(['id' => $nextId]);
    }

    public function create(): View
    {
        if (! Auth::user()->canAccess('shift', 'create')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $autoShiftId = Shift::generateNextId('Pagi');

        return view('shift.create', compact('autoShiftId'));
    }

    public function store(Request $request)
    {
        $shiftName = $request->input('name', 'Pagi');

        if (!$request->filled('id')) {
            $autoShiftId = Shift::generateNextId($shiftName);
            $request->merge(['id' => $autoShiftId]);
        }

        $request->validate([
            'id' => 'required',
            'name' => 'required',
            'description' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'use_manpower' => 'required',
            'tolerance_minutes' => 'nullable|integer|min:0',
        ]);

        try {
            Shift::create([
                'id' => $request->id,
                'name' => $request->name,
                'description' => $request->description,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'tolerance_minutes' => $request->input('tolerance_minutes', 15),
                'use_manpower' => $request->use_manpower,
            ]);

            Alert::success('Success', 'Data berhasil disimpan');

            return redirect()->route('shift.index');
        } catch (\Exception $e) {
            Log::error('Error saat create data: '.$e->getMessage());
            Alert::error('Terjadi Kesalahan', 'Gagal create data.');

            return back()->withInput();
        }
    }

    public function edit(Shift $shift): View
    {
        if (! Auth::user()->canAccess('shift', 'create')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return view('shift.edit', compact('shift'));
    }

    public function update(Request $request, Shift $shift)
    {
        Log::info('Request masuk ke update()', ['data' => $request->all()]);

        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'use_manpower' => 'required|integer|min:0',
            'tolerance_minutes' => 'nullable|integer|min:0',
        ]);

        try {
            $shift->update([
                'name' => $request->name,
                'description' => $request->description,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'use_manpower' => $request->use_manpower,
                'tolerance_minutes' => $request->input('tolerance_minutes', $shift->tolerance_minutes ?? 15),
            ]);

            Alert::success('Success', 'Data shift berhasil diperbarui');

            return redirect()->route('shift.index');
        } catch (\Exception $e) {
            Log::error('Gagal update shift: '.$e->getMessage(), [
                'request' => $request->all(),
                'shift_id' => $shift->id,
            ]);

            Alert::error('Terjadi Kesalahan', 'Gagal memperbarui data shift.');

            return back()->withInput();
        }
    }

    public function destroy(Shift $shift)
    {
        if (! Auth::user()->canAccess('shift', 'create')) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        try {
            // Cek apakah shift digunakan di tabel schedules
            $isUsed = \App\Models\Schedule::where('shift_id', $shift->id)->exists();
            if ($isUsed) {
                Alert::error('Gagal', 'Shift ini tidak bisa dihapus karena sedang digunakan dalam jadwal kerja.');

                return back();
            }

            $shift->delete();
            Alert::success('Berhasil', 'Data shift berhasil dihapus');

            return redirect()->route('shift.index');
        } catch (\Exception $e) {
            Log::error('Gagal hapus shift: '.$e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan saat menghapus data: '.$e->getMessage());

            return back();
        }
    }
}
