<?php

namespace App\Http\Controllers;

use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

class JobTitleController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('job_title', 'view') || Auth::user()->canAccess('user', 'view'), 403, 'Akses Ditolak');
        $jobTitles = JobTitle::orderBy('name', 'asc')->paginate(10);
        return view('master_data.job_titles', compact('jobTitles'));
    }

    public function create()
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('job_title', 'create') || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        return view('master_data.job_titles_create');
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('job_title', 'create') || Auth::user()->canAccess('user', 'create'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:job_titles,name|max:255']);
        JobTitle::create(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Job Title berhasil ditambahkan');
        return redirect()->route('master_data.job_titles.index');
    }

    public function edit(JobTitle $jobTitle)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('job_title', 'edit') || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        return view('master_data.job_titles_edit', compact('jobTitle'));
    }

    public function update(Request $request, JobTitle $jobTitle)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('job_title', 'edit') || Auth::user()->canAccess('user', 'edit'), 403, 'Akses Ditolak');
        $request->validate(['name' => 'required|unique:job_titles,name,' . $jobTitle->id . '|max:255']);
        $jobTitle->update(['name' => trim($request->name)]);
        Alert::success('Berhasil', 'Job Title berhasil diperbarui');
        return redirect()->route('master_data.job_titles.index');
    }

    public function destroy(JobTitle $jobTitle)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->canAccess('job_title', 'delete') || Auth::user()->canAccess('user', 'delete'), 403, 'Akses Ditolak');
        $jobTitle->delete();
        Alert::success('Berhasil', 'Job Title berhasil dihapus');
        return redirect()->route('master_data.job_titles.index');
    }
}
