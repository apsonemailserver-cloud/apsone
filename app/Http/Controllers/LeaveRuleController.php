<?php

namespace App\Http\Controllers;

use App\Models\LeaveRule;
use App\Models\LeaveType;
use App\Services\LeaveQuotaService;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class LeaveRuleController extends Controller
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

    /**
     * Tampilkan semua aturan masa kerja untuk tipe cuti tertentu.
     */
    public function index(LeaveType $leaveType)
    {
        $this->checkView();
        $rules = LeaveRule::where('leave_type_id', $leaveType->id)
            ->orderBy('min_tenure_years')
            ->get();
        return view('master_leaves.rules', compact('leaveType', 'rules'));
    }

    public function create(LeaveType $leaveType)
    {
        $this->checkManage();
        return view('master_leaves.rules_create', compact('leaveType'));
    }

    public function store(Request $request, LeaveType $leaveType)
    {
        $this->checkManage();
        $request->validate([
            'min_tenure_years' => 'required|integer|min:0',
            'max_tenure_years' => 'nullable|integer|gt:min_tenure_years',
            'quota_days'       => 'required|integer|min:0',
            'description'      => 'nullable|string|max:255',
        ]);

        LeaveRule::create([
            'leave_type_id'    => $leaveType->id,
            'min_tenure_years' => $request->min_tenure_years,
            'max_tenure_years' => $request->max_tenure_years,
            'quota_days'       => $request->quota_days,
            'description'      => $request->description,
        ]);

        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Aturan Masa Kerja berhasil ditambahkan.');
        return redirect()->route('master_leaves.rules.index', $leaveType->id);
    }

    public function edit(LeaveRule $leaveRule)
    {
        $this->checkManage();
        $leaveType = LeaveType::findOrFail($leaveRule->leave_type_id);
        return view('master_leaves.rules_edit', compact('leaveRule', 'leaveType'));
    }

    public function update(Request $request, LeaveRule $leaveRule)
    {
        $this->checkManage();
        $request->validate([
            'min_tenure_years' => 'required|integer|min:0',
            'max_tenure_years' => 'nullable|integer|gt:min_tenure_years',
            'quota_days'       => 'required|integer|min:0',
            'description'      => 'nullable|string|max:255',
        ]);

        $leaveRule->update($request->only(['min_tenure_years', 'max_tenure_years', 'quota_days', 'description']));
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Aturan Masa Kerja berhasil diperbarui.');
        return redirect()->route('master_leaves.rules.index', $leaveRule->leave_type_id);
    }

    public function destroy(LeaveRule $leaveRule)
    {
        $this->checkManage();
        $typeId = $leaveRule->leave_type_id;
        $leaveRule->delete();
        $this->quotaService->syncAllBalances(date('Y'));

        Alert::success('Berhasil', 'Aturan Masa Kerja berhasil dihapus.');
        return redirect()->route('master_leaves.rules.index', $typeId);
    }
}
