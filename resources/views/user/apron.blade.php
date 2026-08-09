@extends('layout.admin')

@section('title', 'Manajemen Porter Apron')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="py-4">
            {{-- Header --}}
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Manajemen Porter Apron</h4>
                    <p class="text-muted mb-0" style="font-size:0.875rem;">Informasi pegawai porter apron PT. Angkasa Pratama Sejahtera</p>
                </div>
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i>Tambah User
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <x-dt-toolbar :searchFormAction="route('users.apron')" searchPlaceholder="Cari NIP atau Nama..." />

                    {{-- Tabel --}}
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>NIP</th>
                                    <th>Nama Lengkap</th>
                                    <th>Role</th>
                                    <th>Dibuat Pada</th>
                                    <th>Diupdate Pada</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($user as $users)
                                    <tr>
                                        <td><strong>{{ $users->id }}</strong></td>
                                        <td>{{ $users->fullname }}</td>
                                        <td><span class="badge bg-label-primary">{{ $users->roleRelation->name ?? '-' }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($users->created_at)->translatedFormat('d M Y H:i') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($users->updated_at)->translatedFormat('d M Y H:i') }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <x-action-button action="view" :href="route('users.show', ['user' => $users->id, 'page' => request('page')])" title="Lihat Detail" />
                                                @if(Auth::user()->canAccess('user', 'edit') || Auth::user()->role === 'Admin')
                                                <x-action-button action="edit" :href="route('users.edit', ['user' => $users->id, 'page' => request('page'), 'redirect_to' => url()->full()])" title="Edit Data" />
                                                <x-action-button type="button" action="reset" onclick="confirmReset({{ $users->id }}, '{{ $users->fullname }}')" title="Reset Password" />
                                                @endif
                                                @if(Auth::user()->canAccess('user', 'delete') || Auth::user()->role === 'Admin')
                                                <x-action-button type="button" action="delete" onclick="confirmDelete({{ $users->id }}, '{{ $users->fullname }}')" title="Hapus User" />
                                                @endif
                                            </div>

                                            <form id="resetPasswordForm-{{ $users->id }}" action="{{ route('user.resetPassword', $users->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                            <form id="deleteForm-{{ $users->id }}" action="{{ route('users.destroy', $users->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-3 gap-2" style="border-top: 1px solid #f3f4f6;">
                        <div class="text-muted" style="font-size: 0.8125rem;">
                            Menampilkan {{ $user->firstItem() }} - {{ $user->lastItem() }} dari {{ $user->total() }} data
                        </div>
                        <nav>
                            {{ $user->links('vendor.pagination.custom') }}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function confirmReset(userId, userName) {
            Swal.fire({
                title: 'Reset Password?',
                html: `Apakah Anda yakin ingin mereset password untuk <strong>${userName}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#111827',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`resetPasswordForm-${userId}`).submit();
                }
            });
        }

        function confirmDelete(userId, userName) {
            Swal.fire({
                title: 'Hapus User?',
                html: `Apakah Anda yakin ingin menghapus user <strong>${userName}</strong>?<br><small>Data yang dihapus tidak dapat dikembalikan</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`deleteForm-${userId}`).submit();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if (session('success'))
                Swal.fire({ icon: 'success', title: 'Berhasil', text: '{{ session('success') }}', timer: 3000, showConfirmButton: false });
            @endif
            @if (session('error'))
                Swal.fire({ icon: 'error', title: 'Gagal', text: '{{ session('error') }}', timer: 3000, showConfirmButton: false });
            @endif
        });
    </script>
@endsection
