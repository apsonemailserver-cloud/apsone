@extends('layout.admin')

@section('title', 'User Accounts')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1"><i class="ti ti-user-check me-2"></i>User Accounts</h4>
            <p class="text-muted mb-0">Kelola akun pengguna, autentikasi, status aktif, dan reset password.</p>
        </div>
        <div>
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> Tambah User Baru
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Akun Pengguna</h5>
            <form action="{{ route('users.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NIP / Nama..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="ti ti-search"></i></button>
            </form>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>NIP</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terakhir Diperbarui</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($user as $item)
                    <tr>
                        <td><strong>{{ $item->id }}</strong></td>
                        <td>{{ $item->employee->fullname ?? $item->fullname ?? '-' }}</td>
                        <td>{{ $item->email }}</td>
                        <td>
                            <span class="badge bg-label-primary">
                                {{ $item->roleRelation->name ?? $item->role ?? 'User' }}
                            </span>
                        </td>
                        <td>
                            @if($item->is_active ?? true)
                                <span class="badge bg-label-success">Aktif</span>
                            @else
                                <span class="badge bg-label-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td>{{ $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->translatedFormat('d M Y H:i') : '-' }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('users.show', ['user' => $item->id, 'page' => request('page')]) }}" class="btn btn-sm btn-icon btn-label-info" title="Lihat Detail">
                                    <i class="ti ti-eye"></i>
                                </a>

                                <a href="{{ route('users.edit', ['user' => $item->id, 'page' => request('page'), 'redirect_to' => url()->full()]) }}" class="btn btn-sm btn-icon btn-label-warning" title="Edit User">
                                    <i class="ti ti-edit"></i>
                                </a>

                                <button type="button" class="btn btn-sm btn-icon btn-label-primary" title="Reset Password" onclick="confirmReset('{{ $item->id }}', '{{ $item->employee->fullname ?? $item->id }}')">
                                    <i class="ti ti-key"></i>
                                </button>

                                <form id="resetPasswordForm-{{ $item->id }}" action="{{ route('user.resetPassword', $item->id) }}" method="POST" style="display:none;">
                                    @csrf
                                    @method('PUT')
                                </form>

                                <form action="{{ route('users.destroy', $item->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun user ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Hapus User">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">Tidak ada data user.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $user->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>

<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    function confirmReset(userId, userName) {
        Swal.fire({
            title: 'Reset Password?',
            text: 'Password untuk user "' + userName + '" akan direset menjadi password default (password123).',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7367f0',
            cancelButtonColor: '#8592a3',
            confirmButtonText: 'Ya, Reset Password!',
            cancelButtonText: 'Batal'
        }).then(function(result) {
            if (result.isConfirmed) {
                document.getElementById('resetPasswordForm-' + userId).submit();
            }
        });
    }
</script>
@endsection
