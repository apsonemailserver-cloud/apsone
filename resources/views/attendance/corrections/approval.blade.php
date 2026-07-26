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
                    @if ($isAdmin)
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
                                            <form action="{{ route('attendance.corrections.approve', $correction) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Setujui koreksi absensi ini?')">
                                                    <i class="ti ti-check me-1"></i>Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('attendance.corrections.reject', $correction) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak koreksi absensi ini? Keputusan tidak dapat dibatalkan.')">
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
@endsection
