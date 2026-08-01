@extends('layout.admin')

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
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#leaveDetailModal{{ $leave->id }}">
                                            Detail
                                        </button>

                                        @if ($canApproveThis)
                                            <form action="{{ route('leaves.updateStatus', $leave->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $approveStatus }}">
                                                <button type="submit" class="action-btn action-edit" title="Setujui Pengajuan Cuti" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?')">
                                                    <i class="ti ti-check"></i>
                                                </button>
                                            </form>

                                            <form action="{{ route('leaves.updateStatus', $leave->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $rejectStatus }}">
                                                <button type="submit" class="action-btn action-delete" title="Tolak Pengajuan Cuti" onclick="return confirm('Apakah Anda yakin ingin menolak pengajuan cuti ini?')">
                                                    <i class="ti ti-x"></i>
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
