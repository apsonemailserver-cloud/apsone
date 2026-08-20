@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Daftar Saldo Cuti Karyawan</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Pantau sisa kuota cuti karyawan untuk tahun {{ $year }}.</p>
            </div>
            <div>
                @if(Auth::user()->isAdmin() || Auth::user()->canAccess('master_leave', 'sync'))
                <form action="{{ route('master_leaves.sync') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="ti ti-refresh me-1"></i> Sync Saldo Cuti
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Filter Bar Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('leaves.balances') }}" method="GET" class="row g-3 align-items-end">
                    @if($isAdmin)
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Stasiun</label>
                            <select name="station_id" class="form-select">
                                <option value="">-- Semua Stasiun --</option>
                                @foreach($stations as $st)
                                    <option value="{{ $st->code }}" {{ $stationId == $st->code ? 'selected' : '' }}>
                                        {{ $st->code }} - {{ $st->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tahun</label>
                        <select name="year" class="form-select">
                            @for($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="{{ $isAdmin ? 'col-md-4' : 'col-md-7' }}">
                        <label class="form-label fw-semibold">Cari Data</label>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama atau NIP..." value="{{ $search }}">
                    </div>

                    <div class="col-md-3 text-end">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-filter me-1"></i> Filter
                            </button>
                            <a href="{{ route('leaves.balances') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-rotate-clockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Vertical Table Card --}}
        <div class="card">
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table align-middle table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>NIP / ID</th>
                                <th class="text-center">Stasiun</th>
                                <th>Jenis Cuti</th>
                                <th class="text-center">Total Kuota</th>
                                <th class="text-center">Terpakai</th>
                                <th class="text-center">Pending</th>
                                <th class="text-center">Sisa Kuota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($leaveTypes->isEmpty())
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Belum ada jenis cuti aktif yang terdaftar di sistem.
                                    </td>
                                </tr>
                            @elseif($users->isEmpty())
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Tidak ada data karyawan / saldo cuti ditemukan.
                                    </td>
                                </tr>
                            @else
                                @foreach($users as $user)
                                    @foreach($leaveTypes as $lt)
                                        @php
                                            $b = $user->leaveBalances->firstWhere('leave_type_id', $lt->id);
                                        @endphp
                                        <tr>
                                            @if($loop->first)
                                                <td rowspan="{{ count($leaveTypes) }}" class="align-middle fw-bold text-dark text-uppercase bg-body">
                                                    {{ $user->fullname }}
                                                </td>
                                                <td rowspan="{{ count($leaveTypes) }}" class="align-middle fw-semibold text-body bg-body">
                                                    {{ $user->id }}
                                                </td>
                                                <td rowspan="{{ count($leaveTypes) }}" class="align-middle text-center bg-body">
                                                    <span class="badge bg-label-info">{{ $user->station ?? 'Pusat/HO' }}</span>
                                                </td>
                                            @endif

                                            <td>
                                                <span class="fw-semibold text-dark">{{ $lt->name }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if($lt->is_unlimited)
                                                    <span class="badge bg-label-success">Tidak Terbatas</span>
                                                @else
                                                    <span class="fw-semibold">{{ $b ? $b->total_quota : 0 }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($lt->is_unlimited)
                                                    <span class="text-muted">-</span>
                                                @else
                                                    <span class="{{ ($b && $b->used_days > 0) ? 'text-warning fw-bold' : 'text-muted' }}">{{ $b ? $b->used_days : 0 }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($lt->is_unlimited)
                                                    <span class="text-muted">-</span>
                                                @else
                                                    <span class="{{ ($b && $b->pending_days > 0) ? 'text-info fw-bold' : 'text-muted' }}">{{ $b ? $b->pending_days : 0 }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($lt->is_unlimited)
                                                    <span class="badge bg-label-success">Tidak Terbatas</span>
                                                @else
                                                    <span class="fw-bold fs-6 {{ ($b && $b->remaining_days > 0) ? 'text-primary' : 'text-muted' }}">{{ $b ? $b->remaining_days : 0 }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    {{ $users->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
