<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\SubUnit;
use App\Models\JobTitle;
use App\Models\Cluster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class MasterDataController extends Controller
{
    // ==========================================
    // 1. MASTER UNITS
    // ==========================================
    public function unitsIndex()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $units = Unit::orderBy('name', 'asc')->paginate(10);
        return view('master.units', compact('units'));
    }

    public function unitsStore(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:units,name|max:255']);
        Unit::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Unit berhasil ditambahkan');
        return back();
    }

    public function unitsUpdate(Request $request, Unit $unit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:units,name,' . $unit->id . '|max:255']);
        $unit->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Unit berhasil diperbarui');
        return back();
    }

    public function unitsDestroy(Unit $unit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $unit->delete();
        Alert::success('Berhasil', 'Unit berhasil dihapus');
        return back();
    }

    // ==========================================
    // 2. MASTER SUB UNITS
    // ==========================================
    public function subUnitsIndex()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $subUnits = SubUnit::orderBy('name', 'asc')->paginate(10);
        return view('master.sub_units', compact('subUnits'));
    }

    public function subUnitsStore(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:sub_units,name|max:255']);
        SubUnit::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Sub Unit berhasil ditambahkan');
        return back();
    }

    public function subUnitsUpdate(Request $request, SubUnit $subUnit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:sub_units,name,' . $subUnit->id . '|max:255']);
        $subUnit->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Sub Unit berhasil diperbarui');
        return back();
    }

    public function subUnitsDestroy(SubUnit $subUnit)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $subUnit->delete();
        Alert::success('Berhasil', 'Sub Unit berhasil dihapus');
        return back();
    }

    // ==========================================
    // 3. MASTER JOB TITLES
    // ==========================================
    public function jobTitlesIndex()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $jobTitles = JobTitle::orderBy('name', 'asc')->paginate(10);
        return view('master.job_titles', compact('jobTitles'));
    }

    public function jobTitlesStore(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:job_titles,name|max:255']);
        JobTitle::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Job Title berhasil ditambahkan');
        return back();
    }

    public function jobTitlesUpdate(Request $request, JobTitle $jobTitle)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:job_titles,name,' . $jobTitle->id . '|max:255']);
        $jobTitle->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Job Title berhasil diperbarui');
        return back();
    }

    public function jobTitlesDestroy(JobTitle $jobTitle)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $jobTitle->delete();
        Alert::success('Berhasil', 'Job Title berhasil dihapus');
        return back();
    }

    // ==========================================
    // 4. MASTER CLUSTERS
    // ==========================================
    public function clustersIndex()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $clusters = Cluster::orderBy('name', 'asc')->paginate(10);
        return view('master.clusters', compact('clusters'));
    }

    public function clustersStore(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:clusters,name|max:255']);
        Cluster::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Cluster berhasil ditambahkan');
        return back();
    }

    public function clustersUpdate(Request $request, Cluster $cluster)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:clusters,name,' . $cluster->id . '|max:255']);
        $cluster->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Cluster berhasil diperbarui');
        return back();
    }

    public function clustersDestroy(Cluster $cluster)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $cluster->delete();
        Alert::success('Berhasil', 'Cluster berhasil dihapus');
        return back();
    }
}
