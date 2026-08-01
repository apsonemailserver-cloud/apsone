@extends('layout.admin')

@section('styles')
<style>
    .btn-detail-pill {
        background: #ffffff !important;
        color: #475569 !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 9999px !important;
        padding: 0.38rem 1.15rem !important;
        font-size: 0.8125rem !important;
        font-weight: 500 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.18s ease !important;
        cursor: pointer;
        text-decoration: none !important;
        line-height: 1.2 !important;
        box-shadow: none !important;
    }
    .btn-detail-pill:hover {
        background: #f8fafc !important;
        color: #1e293b !important;
        border-color: #cbd5e1 !important;
    }

    .btn-approve-pill {
        background: #00a66c !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 9999px !important;
        padding: 0.42rem 1.15rem !important;
        font-size: 0.8125rem !important;
        font-weight: 500 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.35rem !important;
        box-shadow: 0 4px 14px rgba(0, 166, 108, 0.24) !important;
        transition: all 0.18s ease !important;
        cursor: pointer;
        text-decoration: none !important;
        line-height: 1.2 !important;
    }
    .btn-approve-pill:hover {
        background: #008f5d !important;
        color: #ffffff !important;
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(0, 166, 108, 0.35) !important;
    }

    .btn-reject-pill {
        background: #ffffff !important;
        color: #ef4444 !important;
        border: 1.2px solid #ff7a7a !important;
        border-radius: 9999px !important;
        padding: 0.38rem 1.15rem !important;
        font-size: 0.8125rem !important;
        font-weight: 500 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.35rem !important;
        transition: all 0.18s ease !important;
        cursor: pointer;
        text-decoration: none !important;
        line-height: 1.2 !important;
    }
    .btn-reject-pill:hover {
        background: #fff5f5 !important;
        color: #dc2626 !important;
        border-color: #dc2626 !important;
        transform: translateY(-1px);
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Approval Pengajuan Izin & Cuti</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Kelola persetujuan pengajuan izin dan cuti karyawan.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                {{-- Toolbar: Search --}}
                <div class="dt-toolbar">
                    <form action="{{ route('leaves.index') }}" method="GET" class="d-flex flex-wrap gap-3 align-items-center flex-grow-1">
                        <div class="dt-search">
                            <i class="bx bx-search search-icon"></i>
                            <input type="text" name="search" class="form-control" placeholder="Cari Nama, NIP, atau Alasan..." value="{{ request('search') }}">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter-alt me-1"></i>Filter
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Pengaju</th>
                                <th>Jenis</th>
                                <th>Tanggal</th>
                                <th>Total Hari</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leaves as $leave)
                            @php
                                $isPending = in_array($leave->status, ['pending Apron', 'pending Bge', 'pending']);
                                $authUser = Auth::user();
                                $userRole = $authUser->role ?? '';
                                
                                $canApproveThis = false;
                                $approveStatus = 'approved';
                                $rejectStatus = 'rejected by ho';

                                if ($isPending && ($authUser->canAccess('leave', 'approve') || $authUser->isAdmin())) {
                                    $canApproveThis = true;
                                    if ($authUser->isAdmin() || $userRole === 'Head Of Airport Service') {
                                        $approveStatus = 'approved';
                                        $rejectStatus = 'rejected by ho';
                                    } elseif (str_contains($userRole, 'Bge') || str_contains($userRole, 'BGE')) {
                                        $approveStatus = ($leave->status === 'pending Bge') ? 'pending' : 'approved';
                                        $rejectStatus = 'rejected by leader';
                                    } elseif (str_contains($userRole, 'Apron') || str_contains($userRole, 'APRON')) {
                                        $approveStatus = ($leave->status === 'pending Apron') ? 'pending' : 'approved';
                                        $rejectStatus = 'rejected by leader';
                                    } else {
                                        $approveStatus = ($leave->status === 'pending') ? 'approved' : 'pending';
                                        $rejectStatus = 'rejected by leader';
                                    }
                                }

                                $statusConfig = match ($leave->status) {
                                    'pending Apron' => ['class' => 'status-pending', 'text' => 'Menunggu Apron'],
                                    'pending Bge' => ['class' => 'status-pending', 'text' => 'Menunggu BGE'],
                                    'pending' => ['class' => 'status-pending', 'text' => 'Menunggu HO'],
                                    'approved' => ['class' => 'status-approved', 'text' => 'Disetujui'],
                                    'rejected by leader' => ['class' => 'status-rejected', 'text' => 'Ditolak Leader'],
                                    'rejected by ho' => ['class' => 'status-rejected', 'text' => 'Ditolak HO'],
                                    default => ['class' => 'status-canceled', 'text' => 'Dibatalkan'],
                                };
                            @endphp
                            <tr>
                                <td><strong>{{ $leave->user->fullname ?? 'N/A' }}</strong></td>
                                <td>{{ $leave->leave_type }}</td>
                                <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</td>
                                <td>{{ $leave->total_days }}</td>
                                <td>
                                    <span class="status-badge {{ $statusConfig['class'] }}">{{ $statusConfig['text'] }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn-detail-pill" data-bs-toggle="modal" data-bs-target="#leaveDetailModal{{ $leave->id }}">
                                            Detail
                                        </button>

                                        @if ($canApproveThis)
                                            <form action="{{ route('leaves.updateStatus', $leave->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $approveStatus }}">
                                                <button type="button" class="btn-approve-pill btn-approve-leave" 
                                                    data-name="{{ addslashes($leave->user->fullname ?? 'Karyawan') }}" 
                                                    data-type="{{ addslashes($leave->leave_type ?? 'Cuti') }}" 
                                                    title="Setujui Pengajuan Cuti">
                                                    <i class="ti ti-check"></i> Approve
                                                </button>
                                            </form>

                                            <form action="{{ route('leaves.updateStatus', $leave->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $rejectStatus }}">
                                                <button type="button" class="btn-reject-pill btn-reject-leave" 
                                                    data-name="{{ addslashes($leave->user->fullname ?? 'Karyawan') }}" 
                                                    data-type="{{ addslashes($leave->leave_type ?? 'Cuti') }}" 
                                                    title="Tolak Pengajuan Cuti">
                                                    <i class="ti ti-x"></i> Tolak
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @include('leaves.partials.modal_detail', [
                                'leave' => $leave, 
                                'statusConfig' => $statusConfig, 
                                'canApproveThis' => $canApproveThis, 
                                'approveStatus' => $approveStatus, 
                                'rejectStatus' => $rejectStatus
                            ])
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="bx bx-calendar-check d-block"></i>
                                        <p>Tidak ada data pengajuan.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-4 pt-3" style="border-top: 1px solid #f3f4f6;">
                    {{ $leaves->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $(document).on('click', '.btn-approve-leave', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const userName = $(this).data('name') || 'Karyawan';
                const leaveType = $(this).data('type') || 'Cuti';

                Swal.fire({
                    title: 'Setujui Pengajuan Cuti?',
                    html: `Apakah Anda yakin ingin menyetujui pengajuan <strong>${leaveType}</strong> dari <strong>${userName}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ti ti-check me-1"></i>Ya, Setujui',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.btn-reject-leave', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const userName = $(this).data('name') || 'Karyawan';
                const leaveType = $(this).data('type') || 'Cuti';

                Swal.fire({
                    title: 'Tolak Pengajuan Cuti?',
                    html: `Apakah Anda yakin ingin menolak pengajuan <strong>${leaveType}</strong> dari <strong>${userName}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ti ti-x me-1"></i>Ya, Tolak',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
