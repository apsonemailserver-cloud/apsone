@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1"><i class="ti ti-users me-2"></i>Master Karyawan</h4>
            <p class="text-muted mb-0">Kelola master data karyawan (biodata, BPJS, NIK, KK, data kontrak, dll).</p>
        </div>
        <div>
            <a href="{{ route('employees.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Tambah Karyawan
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Karyawan</h5>
            <form action="{{ route('employees.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari ID / Nama / NIK..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ti ti-search"></i></button>
            </form>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lengkap</th>
                        <th>Jabatan</th>
                        <th>Unit</th>
                        <th>Station</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($employees as $emp)
                    <tr>
                        <td><strong>{{ $emp->id }}</strong></td>
                        <td>{{ $emp->fullname }}</td>
                        <td>{{ $emp->jobTitle->name ?? '-' }}</td>
                        <td>{{ $emp->unit->name ?? '-' }}</td>
                        <td><span class="badge bg-label-info">{{ $emp->station ?? '-' }}</span></td>
                        <td><span class="badge bg-label-success">{{ $emp->status ?? 'Aktif' }}</span></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('employees.show', $emp->id) }}" class="btn btn-sm btn-icon btn-label-info" title="Detail">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('employees.edit', $emp->id) }}" class="btn btn-sm btn-icon btn-label-warning" title="Edit">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin menghapus data karyawan ini?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Hapus">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">Belum ada data karyawan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $employees->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>
@endsection
