<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use App\Services\LeaveQuotaService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class LeaveTypeController extends Controller
{
    public function __construct(protected LeaveQuotaService $quotaService) {}

    private function checkView(): void
    {
        $user = auth()->user();
        abort_unless($user->canAccess('master_leave', 'view') || $user->isAdmin(), 403, 'Akses ditolak.');
    }

    private function checkManage(): void
    {
        $user = auth()->user();
        abort_unless(
            $user->isAdmin() || $user->canAccess('master_leave', 'create') || $user->canAccess('master_leave', 'edit'),
            403, 'Akses ditolak.'
        );
    }

    public function index(Request $request)
    {
        $this->checkView();
        $query = LeaveType::with('rules')->orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        $leaveTypes = $query->paginate((int) $request->input('per_page', 10))->withQueryString();
        return view('master_leaves.index', compact('leaveTypes'));
    }

    public function create()
    {
        $this->checkManage();
        return view('master_leaves.create');
    }

    public function store(Request $request)
    {
        $this->checkManage();
        $request->merge(['is_unlimited' => $request->boolean('is_unlimited') ? 1 : 0]);
        $request->validate([
            'name'               => 'required|string|max:100',
            'default_quota'      => 'required|integer|min:0',
            'gender_restriction' => 'required|string|in:All,Male,Female',
            'is_unlimited'       => 'required|boolean',
        ]);

        LeaveType::create($request->only(['name', 'default_quota', 'gender_restriction', 'is_unlimited']));
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Tipe Cuti berhasil ditambahkan dan saldo karyawan diperbarui.');
        return redirect()->route('master_leaves.index');
    }

    public function edit(LeaveType $leaveType)
    {
        $this->checkManage();
        return view('master_leaves.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $this->checkManage();
        $request->merge([
            'is_unlimited' => $request->boolean('is_unlimited') ? 1 : 0,
            'is_active'    => $request->boolean('is_active') ? 1 : 0,
        ]);
        $request->validate([
            'name'               => 'required|string|max:100',
            'default_quota'      => 'required|integer|min:0',
            'gender_restriction' => 'required|string|in:All,Male,Female',
            'is_unlimited'       => 'required|boolean',
            'is_active'          => 'required|boolean',
        ]);

        $leaveType->update($request->only(['name', 'default_quota', 'gender_restriction', 'is_unlimited', 'is_active']));
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Tipe Cuti berhasil diperbarui.');
        return redirect()->route('master_leaves.index');
    }

    public function destroy(LeaveType $leaveType)
    {
        $this->checkManage();
        $leaveType->delete();
        Alert::success('Berhasil', 'Tipe Cuti berhasil dihapus.');
        return redirect()->route('master_leaves.index');
    }

    public function syncBalances()
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || $user->canAccess('master_leave', 'sync'), 403, 'Akses ditolak.');
        $this->quotaService->syncAllBalances(date('Y'));
        Alert::success('Berhasil', 'Semua saldo cuti telah disinkronisasikan ulang.');
        return redirect()->back();
    }
}
