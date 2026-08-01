<div class="modal fade" id="leaveDetailModal{{ $leave->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p><strong>Nama:</strong> {{ $leave->user->fullname ?? 'N/A' }}</p>
                <p><strong>Alasan:</strong> {{ $leave->reason }}</p>
                @if($leave->manager_comment)
                <p><strong>Catatan:</strong> {{ $leave->manager_comment }}</p>
                @endif

                <hr>

                <p>
                    <strong>Status Saat Ini:</strong>
                    <span class="status-badge {{ $statusConfig['class'] }}">
                        {{ $statusConfig['text'] }}
                    </span>
                </p>

                @if($leave->pic_approved_at)
                <p>
                    <strong>Persetujuan PIC:</strong>
                    Disetujui oleh {{ $leave->picApprover->fullname ?? 'N/A' }}
                    ({{ \Carbon\Carbon::parse($leave->pic_approved_at)->format('d M Y H:i') }})
                </p>
                @endif

                @if($leave->status == 'approved')
                @php
                    $isSelfApprovedFallback = empty($leave->approved_by);
                    $hoApproverName = $leave->hoApprover->fullname
                        ?? ($isSelfApprovedFallback ? ($leave->user->fullname ?? 'N/A') : 'N/A');
                    $hoApprovedAt = $leave->approved_at
                        ?: ($isSelfApprovedFallback ? ($leave->updated_at ?: $leave->created_at) : null);
                @endphp
                <p>
                    <strong>Persetujuan HO:</strong>
                    Disetujui oleh {{ $hoApproverName }}
                    @if($hoApprovedAt)
                        ({{ \Carbon\Carbon::parse($hoApprovedAt)->format('d M Y H:i') }})
                    @endif
                </p>
                @endif
            </div>

            <div class="modal-footer">

                @if ($canApproveThis ?? false)
                <form action="{{ route('leaves.updateStatus', $leave->id) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $rejectStatus ?? 'rejected by leader' }}">
                    <button type="button" class="btn btn-danger btn-reject-leave" 
                        data-name="{{ addslashes($leave->user->fullname ?? 'Karyawan') }}" 
                        data-type="{{ addslashes($leave->leave_type ?? 'Cuti') }}">
                        <i class="ti ti-x me-1"></i>Tolak
                    </button>
                </form>

                <form action="{{ route('leaves.updateStatus', $leave->id) }}" method="POST" style="display:inline-block; margin-left:5px;">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $approveStatus ?? 'approved' }}">
                    <button type="button" class="btn btn-success btn-approve-leave" 
                        data-name="{{ addslashes($leave->user->fullname ?? 'Karyawan') }}" 
                        data-type="{{ addslashes($leave->leave_type ?? 'Cuti') }}">
                        <i class="ti ti-check me-1"></i>Setujui
                    </button>
                </form>
                @endif

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>
