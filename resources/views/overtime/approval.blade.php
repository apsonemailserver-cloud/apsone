@extends('layout.admin')
@section('title', 'Approval Lembur')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Persetujuan Lembur</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Pengajuan lembur yang menunggu persetujuan Anda.</p>
            </div>
            <span class="badge bg-label-warning" style="font-size: 0.875rem; padding: 0.5em 1em;">
                {{ $pendingOvertimes->count() }} Pending
            </span>
        </div>

        <div class="card">
            <div class="card-body">
                {{-- Toolbar: Search + Station Filter --}}
                <div class="dt-toolbar">
                    <form action="{{ route('overtime.approval') }}" method="GET" class="d-flex flex-wrap gap-3 align-items-center flex-grow-1">
                        <div class="dt-search">
                            <i class="bx bx-search search-icon"></i>
                            <input type="text" name="search" class="form-control" placeholder="Cari Nama atau NIP..." value="{{ request('search') }}">
                        </div>
                        
                        @if(Auth::user()->role == 'Admin')
                        <div style="min-width: 180px;">
                            <select name="station" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Station</option>
                                @foreach(\App\Models\Station::where('is_active', 1)->get() as $st)
                                    <option value="{{ $st->code }}" {{ request('station') == $st->code ? 'selected' : '' }}>{{ $st->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter-alt me-1"></i>Filter
                        </button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Staff</th>
                                <th>Tanggal</th>
                                <th>Judul & Ket</th>
                                <th>Durasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingOvertimes as $ot)
                            <tr>
                                <td>
                                    <strong>{{ $ot->user->fullname }}</strong><br>
                                    <small class="text-muted">{{ $ot->user->station }} - {{ $ot->user->role }}</small>
                                </td>
                                <td>{{ date('d M Y', strtotime($ot->date)) }}</td>
                                <td>
                                    <strong>{{ $ot->title }}</strong><br>
                                    <small class="text-muted">{{ $ot->description }}</small>
                                </td>
                                <td><span class="badge bg-label-primary">{{ $ot->duration }} Jam</span></td>
                                <td>
                                    @if(Auth::user()->canAccess('overtime', 'approve'))
                                    <div class="d-flex gap-2">
                                        <form id="approveForm-{{ $ot->id }}" action="{{ route('overtime.approve', $ot->id) }}" method="POST">
                                            @csrf
                                            <button type="button" class="btn btn-sm btn-success" onclick="confirmApprove({{ $ot->id }}, '{{ addslashes($ot->user->fullname) }}', '{{ addslashes($ot->title) }}')">
                                                <i class="bx bx-check me-1"></i>Approve
                                            </button>
                                        </form>
                                        <form id="rejectForm-{{ $ot->id }}" action="{{ route('overtime.reject', $ot->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="rejection_reason" id="rejection_reason-{{ $ot->id }}" value="">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" style="color:#dc2626 !important; border-color:#fecaca !important;" onclick="confirmReject({{ $ot->id }}, '{{ addslashes($ot->user->fullname) }}', '{{ addslashes($ot->title) }}')">
                                                <i class="bx bx-x me-1"></i>Tolak
                                            </button>
                                        </form>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="bx bx-check-circle d-block"></i>
                                        <p>Tidak ada pengajuan pending saat ini.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="dt-pagination-wrapper">
                    {{ $pendingOvertimes->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.confirmApprove = function(id, staffName, title) {
            Swal.fire({
                icon: 'question',
                iconColor: '#16a34a',
                title: 'Setujui Lembur?',
                html: `
                    <div style="background:#f0fdf4; border-radius:0.75rem; padding:1rem 1.25rem; margin:0.5rem 0; text-align:left;">
                        <div style="margin-bottom:0.75rem; display:flex; align-items:flex-start; gap:0.75rem;">
                            <span style="flex-shrink:0; background:#dcfce7; color:#16a34a; border-radius:999px; padding:0.15rem 0.65rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;">Staff</span>
                            <span style="font-weight:600; color:#111827; font-size:0.9375rem;">${staffName}</span>
                        </div>
                        <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                            <span style="flex-shrink:0; background:#dcfce7; color:#16a34a; border-radius:999px; padding:0.15rem 0.5rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;">Kegiatan</span>
                            <span style="font-weight:500; color:#374151; font-size:0.875rem;">${title}</span>
                        </div>
                    </div>
                    <p style="color:#6b7280; font-size:0.8125rem; margin-top:0.75rem;">
                        Lembur ini akan <strong style="color:#059669;">disetujui</strong> dan dicatat sebagai rekap payroll.
                    </p>
                `,
                showCancelButton: true,
                confirmButtonText: '✓ Ya, Setujui',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#f3f4f6',
                customClass: { confirmButton: 'btn-success' },
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`approveForm-${id}`).submit();
                }
            });
        }

        window.confirmReject = function(id, staffName, title) {
            if (typeof Swal === 'undefined') {
                const reason = prompt('Masukkan alasan penolakan untuk ' + staffName + ':');
                if (reason) {
                    document.getElementById(`rejection_reason-${id}`).value = reason;
                    document.getElementById(`rejectForm-${id}`).submit();
                }
                return;
            }

            Swal.fire({
                icon: 'warning',
                iconColor: '#dc2626',
                title: 'Tolak Pengajuan Lembur?',
                html: `
                    <div style="background:#fef2f2; border-radius:0.75rem; padding:1rem 1.25rem; margin:0.5rem 0; text-align:left;">
                        <div style="margin-bottom:0.75rem; display:flex; align-items:flex-start; gap:0.75rem;">
                            <span style="flex-shrink:0; background:#fecaca; color:#dc2626; border-radius:999px; padding:0.15rem 0.65rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;">Staff</span>
                            <span style="font-weight:600; color:#111827; font-size:0.9375rem;">${staffName}</span>
                        </div>
                        <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                            <span style="flex-shrink:0; background:#fecaca; color:#dc2626; border-radius:999px; padding:0.15rem 0.5rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;">Kegiatan</span>
                            <span style="font-weight:500; color:#374151; font-size:0.875rem;">${title}</span>
                        </div>
                    </div>
                    <div style="text-align:left; margin-top:1rem;">
                        <label for="swal-ot-rejection-reason" style="display:block; font-weight:600; font-size:0.875rem; color:#374151; margin-bottom:0.35rem;">
                            Alasan Penolakan <span style="color:#dc2626;">*</span>
                        </label>
                        <textarea id="swal-ot-rejection-reason" class="form-control" placeholder="Tuliskan alasan penolakan di sini..." style="width:100%; border-radius:0.5rem; border:1px solid #cbd5e1; padding:0.6rem 0.75rem; font-size:0.875rem; min-height:80px; resize:vertical;"></textarea>
                        <div id="swal-ot-rejection-error" style="color:#dc2626; font-size:0.8rem; margin-top:0.35rem; display:none;">Alasan penolakan wajib diisi.</div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '✕ Ya, Tolak',
                cancelButtonText: 'Kembali',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#f3f4f6',
                customClass: { confirmButton: 'btn-danger' },
                reverseButtons: true,
                preConfirm: () => {
                    const reasonInput = document.getElementById('swal-ot-rejection-reason');
                    const errorDiv = document.getElementById('swal-ot-rejection-error');
                    const reason = reasonInput ? reasonInput.value.trim() : '';

                    if (!reason) {
                        if (errorDiv) errorDiv.style.display = 'block';
                        if (reasonInput) reasonInput.style.borderColor = '#dc2626';
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    document.getElementById(`rejection_reason-${id}`).value = result.value;
                    document.getElementById(`rejectForm-${id}`).submit();
                }
            });
        }
    });
</script>
@endsection