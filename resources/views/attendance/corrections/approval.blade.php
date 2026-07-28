@extends('layout.admin')

@section('title', 'Approval Koreksi Absensi')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Approval Koreksi Absensi</h4>
                <p class="text-muted mb-0">Periksa perubahan waktu dan office sebelum mengambil keputusan.</p>
            </div>
            <span class="badge bg-label-warning">{{ $corrections->total() }} Pending</span>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('attendance.corrections.approval') }}" class="d-flex flex-wrap gap-3 mb-4">
                    <div class="flex-grow-1" style="min-width: 220px;">
                        <input type="search" name="search" class="form-control" placeholder="Cari nama atau NIP..." value="{{ request('search') }}">
                    </div>
                    @if ($isAdmin && $stations->count() > 1)
                        <div style="min-width: 200px;">
                            <select name="station_id" class="form-select">
                                <option value="">Semua Office</option>
                                @foreach ($stations as $station)
                                    <option value="{{ $station->id }}" @selected((string) request('station_id') === (string) $station->id)>
                                        {{ $station->code }} — {{ $station->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <button class="btn btn-primary" type="submit">
                        <i class="ti ti-filter me-1"></i>Filter
                    </button>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Tanggal</th>
                                <th>Office</th>
                                <th>Waktu Saat Ini</th>
                                <th>Waktu Koreksi</th>
                                <th>Alasan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($corrections as $correction)
                                <tr>
                                    <td>
                                        <strong>{{ $correction->user->fullname }}</strong>
                                        <div class="small text-muted">{{ $correction->user->id }} · {{ $correction->user->role }}</div>
                                    </td>
                                    <td>{{ $correction->attendance_date->format('d M Y') }}</td>
                                    <td>{{ $correction->station->code }} — {{ $correction->station->name }}</td>
                                    <td class="text-nowrap">
                                        @if ($correction->attendance)
                                            {{ \Carbon\Carbon::parse($correction->attendance->check_in_time)->format('d M, H:i') }}<br>
                                            {{ $correction->attendance->check_out_time ? \Carbon\Carbon::parse($correction->attendance->check_out_time)->format('d M, H:i') : '-' }}
                                        @else
                                            <span class="text-muted">Belum ada data</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <strong>{{ $correction->proposed_check_in_time->format('d M, H:i') }}</strong><br>
                                        <strong>{{ $correction->proposed_check_out_time->format('d M, H:i') }}</strong>
                                    </td>
                                    <td style="min-width: 220px;">{{ $correction->reason }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <form id="approveForm-{{ $correction->id }}" action="{{ route('attendance.corrections.approve', $correction) }}" method="POST">
                                                @csrf
                                                <button type="button" class="btn btn-sm btn-success" onclick="confirmApprove('{{ $correction->id }}', '{{ addslashes($correction->user->fullname) }}', '{{ $correction->attendance_date->format('d M Y') }}')">
                                                    <i class="ti ti-check me-1"></i>Approve
                                                </button>
                                            </form>
                                            <form id="rejectForm-{{ $correction->id }}" action="{{ route('attendance.corrections.reject', $correction) }}" method="POST">
                                                @csrf
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmReject('{{ $correction->id }}', '{{ addslashes($correction->user->fullname) }}', '{{ $correction->attendance_date->format('d M Y') }}')">
                                                    <i class="ti ti-x me-1"></i>Tolak
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="ti ti-circle-check d-block mb-2" style="font-size: 2rem;"></i>
                                        Tidak ada koreksi absensi yang menunggu approval.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $corrections->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmApprove(id, staffName, date) {
        if (typeof Swal === 'undefined') {
            if (confirm('Setujui koreksi absensi untuk ' + staffName + '?')) {
                document.getElementById('approveForm-' + id).submit();
            }
            return;
        }

        Swal.fire({
            icon: 'question',
            title: 'Setujui Koreksi Absensi?',
            html: `
                <div style="background:#f0fdf4; border-radius:0.75rem; padding:1rem 1.25rem; margin:0.5rem 0; text-align:left;">
                    <div style="margin-bottom:0.75rem; display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="flex-shrink:0; background:#dcfce7; color:#16a34a; border-radius:999px; padding:0.15rem 0.65rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;">Staff</span>
                        <span style="font-weight:600; color:#111827; font-size:0.9375rem;">${staffName}</span>
                    </div>
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="flex-shrink:0; background:#dcfce7; color:#16a34a; border-radius:999px; padding:0.15rem 0.5rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;">Tanggal</span>
                        <span style="font-weight:500; color:#374151; font-size:0.875rem;">${date}</span>
                    </div>
                </div>
                <p style="color:#6b7280; font-size:0.8125rem; margin-top:0.75rem;">
                    Koreksi absensi ini akan <strong style="color:#059669;">disetujui</strong> dan waktu absensi staff akan diperbarui.
                </p>
            `,
            showCancelButton: true,
            confirmButtonText: '✓ Ya, Setujui',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#94a3b8',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('approveForm-' + id).submit();
            }
        });
    }

    function confirmReject(id, staffName, date) {
        if (typeof Swal === 'undefined') {
            if (confirm('Tolak koreksi absensi untuk ' + staffName + '?')) {
                document.getElementById('rejectForm-' + id).submit();
            }
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Tolak Koreksi Absensi?',
            html: `
                <div style="background:#fef2f2; border-radius:0.75rem; padding:1rem 1.25rem; margin:0.5rem 0; text-align:left;">
                    <div style="margin-bottom:0.75rem; display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="flex-shrink:0; background:#fecaca; color:#dc2626; border-radius:999px; padding:0.15rem 0.65rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;">Staff</span>
                        <span style="font-weight:600; color:#111827; font-size:0.9375rem;">${staffName}</span>
                    </div>
                    <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                        <span style="flex-shrink:0; background:#fecaca; color:#dc2626; border-radius:999px; padding:0.15rem 0.5rem; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-top:2px;">Tanggal</span>
                        <span style="font-weight:500; color:#374151; font-size:0.875rem;">${date}</span>
                    </div>
                </div>
                <p style="color:#dc2626; font-size:0.8125rem; margin-top:0.75rem;">
                    Pengajuan koreksi absensi ini akan <strong>ditolak</strong> dan keputusan ini tidak dapat dibatalkan.
                </p>
            `,
            showCancelButton: true,
            confirmButtonText: '✕ Ya, Tolak',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ea580c',
            cancelButtonColor: '#94a3b8',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('rejectForm-' + id).submit();
            }
        });
    }
</script>
@endsection
