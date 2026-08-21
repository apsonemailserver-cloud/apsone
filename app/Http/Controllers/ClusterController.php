<?php

namespace App\Http\Controllers;

use App\Models\Cluster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class ClusterController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('cluster', 'view') || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $clusters = Cluster::orderBy('name', 'asc')->paginate(10);
        return view('master_data.clusters', compact('clusters'));
    }

    public function create()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('cluster', 'create') || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        return view('master_data.clusters_create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('cluster', 'create') || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:clusters,name|max:255']);
        Cluster::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Cluster berhasil ditambahkan');
        return redirect()->route('master_data.clusters.index');
    }

    public function edit(Cluster $cluster)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('cluster', 'edit') || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        return view('master_data.clusters_edit', compact('cluster'));
    }

    public function update(Request $request, Cluster $cluster)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('cluster', 'edit') || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:clusters,name,' . $cluster->id . '|max:255']);
        $cluster->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Cluster berhasil diperbarui');
        return redirect()->route('master_data.clusters.index');
    }

    public function destroy(Cluster $cluster)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('cluster', 'delete') || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $cluster->delete();
        Alert::success('Berhasil', 'Cluster berhasil dihapus');
        return redirect()->route('master_data.clusters.index');
    }
}
