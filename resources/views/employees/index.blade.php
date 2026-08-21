@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Employees</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Kelola master data karyawan (biodata, BPJS, NIK, KK, data kontrak, dll).</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if(Auth::user()->canAccess('employee', 'create') || Auth::user()->canAccess('user', 'create'))
                <a href="{{ route('employees.create') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-user-plus me-1"></i>Tambah Karyawan
                </a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <x-dt-toolbar :searchFormAction="route('employees.index')" searchPlaceholder="Cari ID / Nama / NIK / No PAS..." />

                {{-- Tabel --}}
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>NIP / ID</th>
                                <th>Jabatan</th>
                                <th>Unit</th>
                                <th>Station</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $emp)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2 flex-shrink-0" style="width: 36px; height: 36px; min-width: 36px; min-height: 36px;">
                                            @if(optional($emp->user)->profile_picture)
                                             <img src="{{ asset('storage/photo/'.$emp->user->profile_picture) }}" alt="Avatar" class="rounded-circle" style="object-fit: cover; width:100%; height:100%;">
                                            @else
                                            <img src="{{ asset('storage/photo/user.jpg') }}" alt="Avatar" class="rounded-circle" style="object-fit: cover; width:100%; height:100%;">
                                            @endif
                                        </div>
                                        <strong class="text-truncate">{{ $emp->fullname }}</strong>
                                    </div>
                                </td>
                                <td>{{ $emp->id }}</td>
                                <td><span class="badge bg-label-primary">{{ $emp->jobTitle->name ?? '-' }}</span></td>
                                <td>{{ $emp->unit->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-label-info">{{ $emp->station_id ?? $emp->station ?? '-' }}</span>
                                </td>
                                <td>
                                    @if(in_array(strtolower($emp->status ?? 'employed'), ['employed', 'aktif', 'active']))
                                    <span class="badge bg-label-success">{{ $emp->status ?? 'Aktif' }}</span>
                                    @else
                                    <span class="badge bg-label-secondary">{{ $emp->status ?? 'Nonaktif' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if(Auth::user()->canAccess('employee', 'view') || Auth::user()->canAccess('user', 'view'))
                                        <x-action-button action="view" :href="route('employees.show', $emp->id)" title="Detail Karyawan" />
                                        @endif
                                        @if(Auth::user()->canAccess('employee', 'edit') || Auth::user()->canAccess('user', 'edit'))
                                        <x-action-button action="edit" :href="route('employees.edit', $emp->id)" title="Edit Karyawan" />
                                        @endif
                                        @if(Auth::user()->canAccess('employee', 'delete') || Auth::user()->canAccess('user', 'delete'))
                                        <form id="delete-form-{{ $emp->id }}" action="{{ route('employees.destroy', $emp->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <x-action-button type="button" action="delete" onclick="confirmDeleteEmployee('{{ $emp->id }}', '{{ addslashes($emp->fullname) }}')" title="Hapus Karyawan" />
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="ti ti-user-x d-block"></i>
                                        <p>Belum ada data karyawan.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="dt-pagination-wrapper">
                    {{ $employees->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.confirmDeleteEmployee = function(id, name) {
        if (typeof apsConfirmDelete === 'function') {
            apsConfirmDelete({
                title: 'Hapus Data Karyawan?',
                text: 'Data master karyawan "' + name + '" akan dihapus permanen dari sistem.',
                confirmButtonText: 'Ya, Hapus Data',
                cancelButtonText: 'Batal',
                formId: 'delete-form-' + id
            });
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Data Karyawan?',
                text: 'Data master karyawan "' + name + '" akan dihapus permanen dari sistem.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-trash me-1"></i> Ya, Hapus Data',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.getElementById('delete-form-' + id);
                    if (form) form.submit();
                }
            });
        } else {
            if (confirm('Apakah Anda yakin ingin menghapus data karyawan ' + name + '?')) {
                var form = document.getElementById('delete-form-' + id);
                if (form) form.submit();
            }
        }
    };
</script>
@endsection
