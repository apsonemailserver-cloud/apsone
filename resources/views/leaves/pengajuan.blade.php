@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Daftar Pengajuan Izin & Cuti</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Riwayat pengajuan izin dan cuti Anda.</p>
            </div>
            <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus-circle me-1"></i> Ajukan Izin/Cuti
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                {{-- Personal Info --}}
                @if (!in_array(Auth::user()->role, ['PIC', 'HEAD_OFFICE', 'ADMIN', 'HRD']))
                <div class="d-flex flex-wrap gap-4 pb-4 mb-4" style="border-bottom: 1px solid #f3f4f6;">
                    <div>
                        <small class="text-muted d-block">NIP</small>
                        <strong>{{ $user->id }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Nama</small>
                        <strong>{{ $user->fullname ?? 'N/A' }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Sisa Cuti</small>
                        <strong>{{ $leaveBalance ?? 0 }} hari</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Terpakai</small>
                        <strong>{{ $usedLeaveDays ?? 0 }} hari</strong>
                    </div>
                </div>
                @endif

                {{-- Toolbar: Search --}}
                <div class="dt-toolbar">
                    <form action="{{ route('leaves.pengajuan') }}" method="GET" class="d-flex flex-wrap gap-3 align-items-center flex-grow-1">
                        <div class="dt-search">
                            <i class="bx bx-search search-icon"></i>
                            <input type="text" name="search" class="form-control" placeholder="Cari Jenis atau Alasan..." value="{{ request('search') }}">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter-alt me-1"></i>Filter
                        </button>
                    </form>
                </div>

                {{-- Tabel --}}
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
                            <tr>
                                <td><strong>{{ $leave->user->fullname ?? 'N/A' }}</strong></td>
                                <td>{{ $leave->leave_type }}</td>
                                <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</td>
                                <td>{{ $leave->total_days }}</td>
                                <td>
                                    @php
                                    $statusConfig = match ($leave->status) {
                                    'pending Apron' => ['class' => 'status-pending', 'text' => 'Menunggu'],
                                    'pending Bge' => ['class' => 'status-pending', 'text' => 'Menunggu'],
                                    'pending' => ['class' => 'status-pending', 'text' => 'Menunggu HO'],
                                    'approved' => ['class' => 'status-approved', 'text' => 'Disetujui'],
                                    'rejected by leader' => ['class' => 'status-rejected', 'text' => 'Ditolak Leader'],
                                    'rejected by ho' => ['class' => 'status-rejected', 'text' => 'Ditolak HO'],
                                    default => ['class' => 'status-canceled', 'text' => 'Dibatalkan'],
                                    };
                                    @endphp
                                    <span class="status-badge {{ $statusConfig['class'] }}">{{ $statusConfig['text'] }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#leaveDetailModal{{ $leave->id }}">
                                            Detail
                                        </button>
                                        @php
                                            $authUser = Auth::user();
                                            $isOwner = (string) $leave->user_id === (string) $authUser->id;
                                            $isAdminOrApprover = $authUser->isAdmin() || $authUser->canAccess('leave', 'approve');
                                            $isPending = in_array($leave->status, ['pending', 'pending Apron', 'pending Bge']);
                                            $isApproved = $leave->status === 'approved';

                                            // Bawahan hanya dapat membatalkan jika status masih Menunggu.
                                            // Atasan / Admin dapat membatalkan jika status Menunggu atau Disetujui.
                                            $canCancel = ($isOwner && $isPending) || ($isAdminOrApprover && ($isPending || $isApproved));
                                        @endphp
                                        @if ($canCancel)
                                            <form action="{{ route('leaves.cancel', $leave->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-cancel-leave" 
                                                    data-type="{{ addslashes($leave->leave_type ?? 'Izin/Cuti') }}"
                                                    data-status="{{ $leave->status }}"
                                                    title="Batalkan Pengajuan">
                                                    <i class="bx bx-x me-1"></i>Batalkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @include('leaves.partials.modal_detail', ['leave' => $leave, 'statusConfig' => $statusConfig])
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
            $(document).on('click', '.btn-cancel-leave', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const leaveType = $(this).data('type') || 'Pengajuan';
                const status = $(this).data('status');
                const isApproved = status === 'approved';

                let warningHtml = `Apakah Anda yakin ingin membatalkan pengajuan <strong>${leaveType}</strong> ini?`;
                if (isApproved) {
                    warningHtml += `<br><div class="alert alert-warning p-2 mt-2 mb-0 text-start" style="font-size:0.85rem;"><i class="bx bx-info-circle me-1"></i>Pengajuan ini sudah disetujui sebelumnya. Membatalkan akan <strong>mengembalikan sisa kuota cuti</strong> Anda.</div>`;
                }

                Swal.fire({
                    title: 'Batalkan Pengajuan?',
                    html: warningHtml,
                    icon: 'warning',
                    iconColor: '#ef4444',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Kembali',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false,
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
