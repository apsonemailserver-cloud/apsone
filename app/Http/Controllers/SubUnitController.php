<?php

namespace App\Http\Controllers;

use App\Models\SubUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class SubUnitController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $subUnits = SubUnit::with('unit')->orderBy('name', 'asc')->paginate(10);
        $units = \App\Models\Unit::orderBy('name', 'asc')->get();
        return view('master_data.sub_units', compact('subUnits', 'units'));
    }

    public function create()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $units = \App\Models\Unit::orderBy('name', 'asc')->get();
        return view('master_data.sub_units_create', compact('units'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate([
            'name' => 'required|unique:sub_units,name|max:255',
            'unit_id' => 'nullable|exists:units,id',
        ]);
        SubUnit::create([
            'name' => trim($request->name),
            'unit_id' => $request->unit_id,
        ]);
        Alert::success('Berhasil', 'Sub Unit berhasil ditambahkan');
        return redirect()->route('master_data.sub_units.index');
    }

    public function edit(SubUnit $subUnit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $units = \App\Models\Unit::orderBy('name', 'asc')->get();
        return view('master_data.sub_units_edit', compact('subUnit', 'units'));
    }

    public function update(Request $request, SubUnit $subUnit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate([
            'name' => 'required|unique:sub_units,name,' . $subUnit->id . '|max:255',
            'unit_id' => 'nullable|exists:units,id',
        ]);
        $subUnit->update([
            'name' => trim($request->name),
            'unit_id' => $request->unit_id,
        ]);
        Alert::success('Berhasil', 'Sub Unit berhasil diperbarui');
        return redirect()->route('master_data.sub_units.index');
    }

    public function destroy(SubUnit $subUnit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $subUnit->delete();
        Alert::success('Berhasil', 'Sub Unit berhasil dihapus');
        return redirect()->route('master_data.sub_units.index');
    }
}
