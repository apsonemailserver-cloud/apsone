<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class UnitController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('unit', 'view') || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $units = Unit::orderBy('name', 'asc')->paginate(10);
        return view('master_data.units', compact('units'));
    }

    public function create()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('unit', 'create') || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        return view('master_data.units_create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('unit', 'create') || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:units,name|max:255']);
        Unit::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Unit berhasil ditambahkan');
        return redirect()->route('master_data.units.index');
    }

    public function edit(Unit $unit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('unit', 'edit') || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        return view('master_data.units_edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('unit', 'edit') || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:units,name,' . $unit->id . '|max:255']);
        $unit->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Unit berhasil diperbarui');
        return redirect()->route('master_data.units.index');
    }

    public function destroy(Unit $unit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('unit', 'delete') || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $unit->delete();
        Alert::success('Berhasil', 'Unit berhasil dihapus');
        return redirect()->route('master_data.units.index');
    }
}
