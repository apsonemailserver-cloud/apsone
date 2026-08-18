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
        return view('master_data.units', compact('units'));
    }

    public function unitsCreate()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        return view('master_data.units_create');
    }

    public function unitsEdit($unit)
    {
        if (!($unit instanceof Unit)) {
            $unit = Unit::findOrFail($unit);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        return view('master_data.units_edit', compact('unit'));
    }

    private function smartRedirect(string $fallbackRoute)
    {
        $prev = url()->previous();
        if (!empty($prev) && $prev !== url('/')) {
            return redirect()->back();
        }
        return redirect()->route($fallbackRoute);
    }

    public function unitsStore(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:units,name|max:255']);
        Unit::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Unit berhasil ditambahkan');
        return $this->smartRedirect('master_data.units.index');
    }

    public function unitsUpdate(Request $request, $unit)
    {
        if (!($unit instanceof Unit)) {
            $unit = Unit::findOrFail($unit);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:units,name,' . $unit->id . '|max:255']);
        $unit->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Unit berhasil diperbarui');
        return $this->smartRedirect('master_data.units.index');
    }

    public function unitsDestroy($unit)
    {
        if (!($unit instanceof Unit)) {
            $unit = Unit::findOrFail($unit);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $unit->delete();
        Alert::success('Berhasil', 'Unit berhasil dihapus');
        return $this->smartRedirect('master_data.units.index');
    }

    // ==========================================
    // 2. MASTER SUB UNITS
    // ==========================================
    public function subUnitsIndex()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $subUnits = SubUnit::with('unit')->orderBy('name', 'asc')->paginate(10);
        $units = Unit::orderBy('name', 'asc')->get();
        return view('master_data.sub_units', compact('subUnits', 'units'));
    }

    public function subUnitsCreate()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $units = Unit::orderBy('name', 'asc')->get();
        return view('master_data.sub_units_create', compact('units'));
    }

    public function subUnitsEdit($subUnit)
    {
        if (!($subUnit instanceof SubUnit)) {
            $subUnit = SubUnit::findOrFail($subUnit);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $units = Unit::orderBy('name', 'asc')->get();
        return view('master_data.sub_units_edit', compact('subUnit', 'units'));
    }

    public function subUnitsStore(Request $request)
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
        return $this->smartRedirect('master_data.sub_units.index');
    }

    public function subUnitsUpdate(Request $request, $subUnit)
    {
        if (!($subUnit instanceof SubUnit)) {
            $subUnit = SubUnit::findOrFail($subUnit);
        }
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
        return $this->smartRedirect('master_data.sub_units.index');
    }

    public function subUnitsDestroy($subUnit)
    {
        if (!($subUnit instanceof SubUnit)) {
            $subUnit = SubUnit::findOrFail($subUnit);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $subUnit->delete();
        Alert::success('Berhasil', 'Sub Unit berhasil dihapus');
        return $this->smartRedirect('master_data.sub_units.index');
    }

    // ==========================================
    // 3. MASTER JOB TITLES
    // ==========================================
    public function jobTitlesIndex()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $jobTitles = JobTitle::orderBy('name', 'asc')->paginate(10);
        return view('master_data.job_titles', compact('jobTitles'));
    }

    public function jobTitlesCreate()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        return view('master_data.job_titles_create');
    }

    public function jobTitlesEdit($jobTitle)
    {
        if (!($jobTitle instanceof JobTitle)) {
            $jobTitle = JobTitle::findOrFail($jobTitle);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        return view('master_data.job_titles_edit', compact('jobTitle'));
    }

    public function jobTitlesStore(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:job_titles,name|max:255']);
        JobTitle::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Job Title berhasil ditambahkan');
        return $this->smartRedirect('master_data.job_titles.index');
    }

    public function jobTitlesUpdate(Request $request, $jobTitle)
    {
        if (!($jobTitle instanceof JobTitle)) {
            $jobTitle = JobTitle::findOrFail($jobTitle);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:job_titles,name,' . $jobTitle->id . '|max:255']);
        $jobTitle->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Job Title berhasil diperbarui');
        return $this->smartRedirect('master_data.job_titles.index');
    }

    public function jobTitlesDestroy($jobTitle)
    {
        if (!($jobTitle instanceof JobTitle)) {
            $jobTitle = JobTitle::findOrFail($jobTitle);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $jobTitle->delete();
        Alert::success('Berhasil', 'Job Title berhasil dihapus');
        return $this->smartRedirect('master_data.job_titles.index');
    }

    // ==========================================
    // 4. MASTER CLUSTERS
    // ==========================================
    public function clustersIndex()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $clusters = Cluster::orderBy('name', 'asc')->paginate(10);
        return view('master_data.clusters', compact('clusters'));
    }

    public function clustersCreate()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        return view('master_data.clusters_create');
    }

    public function clustersEdit($cluster)
    {
        if (!($cluster instanceof Cluster)) {
            $cluster = Cluster::findOrFail($cluster);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        return view('master_data.clusters_edit', compact('cluster'));
    }

    public function clustersStore(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:clusters,name|max:255']);
        Cluster::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Cluster berhasil ditambahkan');
        return $this->smartRedirect('master_data.clusters.index');
    }

    public function clustersUpdate(Request $request, $cluster)
    {
        if (!($cluster instanceof Cluster)) {
            $cluster = Cluster::findOrFail($cluster);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:clusters,name,' . $cluster->id . '|max:255']);
        $cluster->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Cluster berhasil diperbarui');
        return $this->smartRedirect('master_data.clusters.index');
    }

    public function clustersDestroy($cluster)
    {
        if (!($cluster instanceof Cluster)) {
            $cluster = Cluster::findOrFail($cluster);
        }
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $cluster->delete();
        Alert::success('Berhasil', 'Cluster berhasil dihapus');
        return $this->smartRedirect('master_data.clusters.index');
    }

    // Method aliases for backward compatibility and test routes
    public function indexJobTitles() { return $this->jobTitlesIndex(); }
    public function createJobTitle() { return $this->jobTitlesCreate(); }
    public function editJobTitle($jobTitle) { return $this->jobTitlesEdit($jobTitle); }
    public function storeJobTitle(Request $request) { return $this->jobTitlesStore($request); }
    public function updateJobTitle(Request $request, $jobTitle) { return $this->jobTitlesUpdate($request, $jobTitle); }
    public function destroyJobTitle($jobTitle) { return $this->jobTitlesDestroy($jobTitle); }

    public function indexUnits() { return $this->unitsIndex(); }
    public function createUnit() { return $this->unitsCreate(); }
    public function editUnit($unit) { return $this->unitsEdit($unit); }
    public function storeUnit(Request $request) { return $this->unitsStore($request); }
    public function updateUnit(Request $request, $unit) { return $this->unitsUpdate($request, $unit); }
    public function destroyUnit($unit) { return $this->unitsDestroy($unit); }

    public function indexSubUnits() { return $this->subUnitsIndex(); }
    public function createSubUnit() { return $this->subUnitsCreate(); }
    public function editSubUnit($subUnit) { return $this->subUnitsEdit($subUnit); }
    public function storeSubUnit(Request $request) { return $this->subUnitsStore($request); }
    public function updateSubUnit(Request $request, $subUnit) { return $this->subUnitsUpdate($request, $subUnit); }
    public function destroySubUnit($subUnit) { return $this->subUnitsDestroy($subUnit); }
}
