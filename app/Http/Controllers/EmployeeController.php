<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\Station;
use App\Models\JobTitle;
use App\Models\Unit;
use App\Models\SubUnit;
use App\Models\Cluster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Controllers\Traits\PreservesIndexState;

class EmployeeController extends Controller
{
    use PreservesIndexState;

    public function index(Request $request)
    {
        abort_unless(Auth::user()->canAccess('user', 'view'), 403, 'Anda tidak memiliki akses ke halaman ini.');

        $search = $request->input('search');

        $employees = Employee::with(['unit', 'subUnit', 'jobTitle', 'cluster', 'user'])
            ->when($search, function ($query, $search) {
                return $query->where('fullname', 'like', "%{$search}%")
                    ->orWhere('no_nik', 'like', "%{$search}%")
                    ->orWhere('no_pas', 'like', "%{$search}%");
            })
            ->orderBy('fullname', 'asc')
            ->paginate(15)
            ->withQueryString();

        $title = 'Konfirmasi Hapus Data Employee';
        $text = 'Data employee yang dihapus tidak dapat dikembalikan. Apakah Anda yakin ingin menghapus data ini?';
        confirmDelete($title, $text);

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        abort_unless(Auth::user()->canAccess('user', 'create'), 403, 'Anda tidak memiliki akses ke halaman ini.');
        $stations = Station::where('is_active', 1)->orderBy('code', 'ASC')->get();
        $jobTitles = JobTitle::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $subUnits = SubUnit::orderBy('name')->get();
        $clusters = Cluster::orderBy('name')->get();

        return view('employees.create', compact('stations', 'jobTitles', 'units', 'subUnits', 'clusters'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->canAccess('user', 'create'), 403, 'Anda tidak memiliki akses ke halaman ini.');

        $request->validate([
            'fullname' => 'required|string|max:255',
            'station' => 'required|string|max:15',
            'gender' => 'required|in:Male,Female',
            'job_title_id' => 'required|exists:job_titles,id',
            'unit_id' => 'required|exists:units,id',
            'sub_unit_id' => 'required|exists:sub_units,id',
            'cluster_id' => 'nullable|exists:clusters,id',
            'join_date' => 'required|date',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'pas_registered' => 'nullable|date',
            'pas_expired' => 'nullable|date',
            'tim_registered' => 'nullable|date',
            'tim_expired' => 'nullable|date',
            'tanggal_lahir' => 'nullable|date',
            'salary' => 'nullable',
        ]);

        try {
            $data = $request->all();
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $data[$key] = null;
                }
            }
            $employee = Employee::create($data);

            Alert::success('Berhasil', 'Data Karyawan berhasil ditambahkan');
            return redirect()->route('employees.index');
        } catch (\Exception $e) {
            Log::error('Error store employee: ' . $e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show(Employee $employee): View
    {
        abort_unless(Auth::user()->canAccess('user', 'view'), 403, 'Anda tidak memiliki akses ke halaman ini.');
        $employee->load(['unit', 'subUnit', 'jobTitle', 'cluster', 'user']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        abort_unless(Auth::user()->canAccess('user', 'edit'), 403, 'Anda tidak memiliki akses ke halaman ini.');
        $stations = Station::where('is_active', 1)->orderBy('code', 'ASC')->get();
        $jobTitles = JobTitle::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();
        $subUnits = SubUnit::orderBy('name')->get();
        $clusters = Cluster::orderBy('name')->get();

        return view('employees.edit', compact('employee', 'stations', 'jobTitles', 'units', 'subUnits', 'clusters'));
    }

    public function update(Request $request, Employee $employee)
    {
        abort_unless(Auth::user()->canAccess('user', 'edit'), 403, 'Anda tidak memiliki akses ke halaman ini.');

        $request->validate([
            'fullname' => 'required|string|max:255',
            'station' => 'required|string|max:15',
            'gender' => 'required|in:Male,Female',
            'job_title_id' => 'required|exists:job_titles,id',
            'unit_id' => 'required|exists:units,id',
            'sub_unit_id' => 'required|exists:sub_units,id',
            'cluster_id' => 'nullable|exists:clusters,id',
            'join_date' => 'required|date',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'pas_registered' => 'nullable|date',
            'pas_expired' => 'nullable|date',
            'tim_registered' => 'nullable|date',
            'tim_expired' => 'nullable|date',
            'tanggal_lahir' => 'nullable|date',
            'salary' => 'nullable',
        ]);

        try {
            $data = $request->all();
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $data[$key] = null;
                }
            }
            $employee->update($data);
            Alert::success('Berhasil', 'Data Karyawan ' . $employee->fullname . ' berhasil diperbarui.');

            return redirect()->route('employees.index');
        } catch (\Exception $e) {
            Log::error('Error update employee: ' . $e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        abort_unless(Auth::user()->canAccess('user', 'delete'), 403, 'Anda tidak memiliki akses ke halaman ini.');

        try {
            $employee->delete();
            Alert::success('Berhasil', 'Data karyawan berhasil dihapus');

            return redirect()->route('employees.index');
        } catch (\Exception $e) {
            Log::error('Error destroy employee: ' . $e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return back();
        }
    }
}
