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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Controllers\Traits\PreservesIndexState;

class EmployeeController extends Controller
{
    use PreservesIndexState;

    public function index(Request $request)
    {
        abort_unless(Auth::user()->canAccess('employee', 'view') || Auth::user()->canAccess('user', 'view'), 403, 'Anda tidak memiliki akses ke halaman ini.');

        $search = $request->input('search');

        $employees = Employee::with(['unit', 'subUnit', 'jobTitle', 'cluster', 'user'])
            ->when($search, function ($query, $search) {
                return $query->where('id', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
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
        abort_unless(Auth::user()->canAccess('employee', 'create') || Auth::user()->canAccess('user', 'create'), 403, 'Anda tidak memiliki akses ke halaman ini.');
        $stations = Station::where('is_active', 1)->orderBy('code', 'ASC')->get();
        $jobTitles = JobTitle::orderBy('name')->get();
        $units = Unit::orderBy('name', 'asc')->get();
        $subUnits = SubUnit::orderBy('name', 'asc')->get();
        $clusters = Cluster::orderBy('name')->get();
        $users = User::with('employee')->orderBy('id', 'asc')->get();

        return view('employees.create', compact('stations', 'jobTitles', 'units', 'subUnits', 'clusters', 'users'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->canAccess('employee', 'create') || Auth::user()->canAccess('user', 'create'), 403, 'Anda tidak memiliki akses ke halaman ini.');

        $request->validate([
            'fullname' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'station_id' => 'required|string|max:15|exists:stations,code',
            'job_title_id' => 'required|exists:job_titles,id',
            'unit_id' => 'required|exists:units,id',
            'sub_unit_id' => 'required|exists:sub_units,id',
            'join_date' => 'required|date',
            'is_qantas' => 'required|in:0,1',
            'cluster_id' => 'nullable|exists:clusters,id',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'pas_registered' => 'nullable|date',
            'pas_expired' => 'nullable|date',
            'tim_registered' => 'nullable|date',
            'tim_expired' => 'nullable|date',
            'tanggal_lahir' => 'nullable|date|before_or_equal:today',
            'salary' => 'nullable',
        ], [
            'fullname.required' => 'Nama lengkap wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'station_id.required' => 'Station wajib dipilih.',
            'job_title_id.required' => 'Job title wajib dipilih.',
            'unit_id.required' => 'Unit wajib dipilih.',
            'sub_unit_id.required' => 'Sub unit wajib dipilih.',
            'join_date.required' => 'Tanggal masuk (join date) wajib diisi.',
            'is_qantas.required' => 'Status Staf Qantas wajib dipilih.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except(['_token', '_method', 'user_id', 'salary_display']);
            if (empty($data['station_id']) && !empty($request->station)) {
                $data['station_id'] = $request->station;
            }
            unset($data['station']);
            if ($request->has('salary') || $request->has('salary_display')) {
                $rawSalary = $request->salary ?? $request->salary_display;
                $data['salary'] = preg_replace('/[^0-9]/', '', (string) $rawSalary) ?: null;
            }
            if ($request->has('is_qantas')) {
                $data['is_qantas'] = $request->input('is_qantas') == 1 ? 1 : 0;
            }
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $data[$key] = null;
                }
            }
            $employee = Employee::create($data);

            if ($request->filled('user_id')) {
                $user = User::find($request->user_id);
                if ($user) {
                    $user->update(['employee_id' => $employee->id]);
                }
            }

            DB::commit();

            Alert::success('Berhasil', 'Data Karyawan berhasil ditambahkan');
            return redirect()->route('employees.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store employee: ' . $e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function show(Employee $employee): View
    {
        abort_unless(Auth::user()->canAccess('employee', 'view') || Auth::user()->canAccess('user', 'view'), 403, 'Anda tidak memiliki akses ke halaman ini.');
        $employee->load(['unit', 'subUnit', 'jobTitle', 'cluster', 'user']);

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        abort_unless(Auth::user()->canAccess('employee', 'edit') || Auth::user()->canAccess('user', 'edit'), 403, 'Anda tidak memiliki akses ke halaman ini.');
        $stations = Station::where('is_active', 1)->orderBy('code', 'ASC')->get();
        $jobTitles = JobTitle::orderBy('name')->get();
        $units = Unit::orderBy('name', 'asc')->get();
        $subUnits = SubUnit::orderBy('name', 'asc')->get();
        $clusters = Cluster::orderBy('name')->get();
        $users = User::with('employee')->orderBy('id', 'asc')->get();

        return view('employees.edit', compact('employee', 'stations', 'jobTitles', 'units', 'subUnits', 'clusters', 'users'));
    }

    public function update(Request $request, Employee $employee)
    {
        abort_unless(Auth::user()->canAccess('employee', 'edit') || Auth::user()->canAccess('user', 'edit'), 403, 'Anda tidak memiliki akses ke halaman ini.');

        $request->validate([
            'fullname' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female',
            'station_id' => 'required|string|max:15|exists:stations,code',
            'job_title_id' => 'required|exists:job_titles,id',
            'unit_id' => 'required|exists:units,id',
            'sub_unit_id' => 'required|exists:sub_units,id',
            'join_date' => 'required|date',
            'is_qantas' => 'required|in:0,1',
            'cluster_id' => 'nullable|exists:clusters,id',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date',
            'pas_registered' => 'nullable|date',
            'pas_expired' => 'nullable|date',
            'tim_registered' => 'nullable|date',
            'tim_expired' => 'nullable|date',
            'tanggal_lahir' => 'nullable|date|before_or_equal:today',
            'salary' => 'nullable',
        ], [
            'fullname.required' => 'Nama lengkap wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'station_id.required' => 'Station wajib dipilih.',
            'job_title_id.required' => 'Job title wajib dipilih.',
            'unit_id.required' => 'Unit wajib dipilih.',
            'sub_unit_id.required' => 'Sub unit wajib dipilih.',
            'join_date.required' => 'Tanggal masuk (join date) wajib diisi.',
            'is_qantas.required' => 'Status Staf Qantas wajib dipilih.',
            'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
        ]);

        DB::beginTransaction();
        try {
            $data = $request->except(['_token', '_method', 'user_id', 'salary_display']);
            if (empty($data['station_id']) && !empty($request->station)) {
                $data['station_id'] = $request->station;
            }
            unset($data['station']);
            if ($request->has('salary') || $request->has('salary_display')) {
                $rawSalary = $request->salary ?? $request->salary_display;
                $data['salary'] = preg_replace('/[^0-9]/', '', (string) $rawSalary) ?: null;
            }
            if ($request->has('is_qantas')) {
                $data['is_qantas'] = $request->input('is_qantas') == 1 ? 1 : 0;
            }
            foreach ($data as $key => $value) {
                if ($value === '') {
                    $data[$key] = null;
                }
            }
            $employee->update($data);

            if ($request->has('user_id')) {
                if ($request->filled('user_id')) {
                    // Disassociate old user if any
                    User::where('employee_id', $employee->id)->where('id', '!=', $request->user_id)->update(['employee_id' => null]);
                    $user = User::find($request->user_id);
                    if ($user) {
                        $user->update(['employee_id' => $employee->id]);
                    }
                } else {
                    User::where('employee_id', $employee->id)->update(['employee_id' => null]);
                }
            }

            DB::commit();

            Alert::success('Berhasil', 'Data Karyawan ' . $employee->fullname . ' berhasil diperbarui.');

            return redirect()->route('employees.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update employee: ' . $e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    public function destroy(Employee $employee)
    {
        abort_unless(Auth::user()->canAccess('employee', 'delete') || Auth::user()->canAccess('user', 'delete'), 403, 'Anda tidak memiliki akses ke halaman ini.');

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
