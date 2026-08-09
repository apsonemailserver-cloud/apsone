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
        $subUnits = SubUnit::orderBy('name', 'asc')->paginate(10);
        return view('master_data.sub_units', compact('subUnits'));
    }

    public function create()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        return view('master_data.sub_units_create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:sub_units,name|max:255']);
        SubUnit::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Sub Unit berhasil ditambahkan');
        return redirect()->route('master_data.sub_units.index');
    }

    public function edit(SubUnit $subUnit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        return view('master_data.sub_units_edit', compact('subUnit'));
    }

    public function update(Request $request, SubUnit $subUnit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:sub_units,name,' . $subUnit->id . '|max:255']);
        $subUnit->update(['name' => trim($request->name)]);
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
