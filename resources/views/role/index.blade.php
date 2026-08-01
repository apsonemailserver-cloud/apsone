@extends('layout.admin')

@section('title', 'Hak Akses Role')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Hak Akses Role</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Kelola seluruh atribusi hak akses dan wewenang role di semua station.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportRolesCSV()">
                    <i class="ti ti-download me-1"></i> Export CSV
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                    <i class="ti ti-refresh me-1"></i> Refresh
                </button>
                @if(Auth::user()->canAccess('role', 'create') || Auth::user()->role === 'Admin')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                    <i class="ti ti-plus me-1"></i> Tambah Role
                </button>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                {{-- Toolbar --}}
                <div class="dt-toolbar">
                    <div class="dt-search">
                        <i class="ti ti-search search-icon"></i>
                        <input type="text" id="typeToSearchInput" class="form-control" placeholder="Cari Kode / Nama Role..." onkeyup="filterRolesList()">
                    </div>
                </div>

                {{-- Tabel --}}
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Role</th>
                                <th>Kode</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                                @php
                                    $isSystem = $role->is_system || in_array($role->name, ['Admin', 'Manager', 'Staff']);
                                @endphp
                                <tr class="role-master-row">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2 flex-shrink-0">
                                                <span class="avatar-initial rounded-circle bg-label-primary font-monospace fw-bold" style="font-size:0.75rem;">
                                                    {{ strtoupper(substr($role->name, 0, 2)) }}
                                                </span>
                                            </div>
                                            <strong class="role-name-text">{{ $role->label ?: $role->name }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-monospace text-dark role-code-text">{{ $role->name }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted role-desc-text" style="font-size:0.875rem;">{{ $role->description ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            {{-- 1. Eye Button (Matriks Hak Akses) --}}
                                            @if(Auth::user()->canAccess('role', 'edit') || Auth::user()->role === 'Admin')
                                                <a href="{{ route('roles.edit', $role->id) }}" class="action-btn" title="Matriks Hak Akses">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                            @endif

                                            {{-- 2. Pencil Button (Edit Detail) --}}
                                            @if(Auth::user()->canAccess('role', 'edit') || Auth::user()->role === 'Admin')
                                                <button type="button" class="action-btn action-edit btn-edit-detail border-0" 
                                                        data-id="{{ $role->id }}" 
                                                        data-name="{{ $role->name }}" 
                                                        data-label="{{ $role->label }}" 
                                                        data-description="{{ $role->description }}" 
                                                        title="Edit Detail">
                                                    <i class="ti ti-pencil"></i>
                                                </button>
                                            @endif

                                            {{-- 3. Delete Trash Button (SweetAlert2 Confirm) --}}
                                            @if(!$isSystem && (Auth::user()->canAccess('role', 'delete') || Auth::user()->role === 'Admin'))
                                                <form id="delete-form-{{ $role->id }}" action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="action-btn action-delete border-0" onclick="confirmDeleteRole('{{ $role->id }}', '{{ addslashes($role->label ?: $role->name) }}')" title="Hapus Role">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="dt-pagination-wrapper">
                    <div class="text-muted small" id="rowsCounterText">
                        Menampilkan 1-{{ count($roles) }} dari {{ count($roles) }} data
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL CREATE ROLE --}}
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Role Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kode Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Work Order CS" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Label Tampilan</label>
                        <input type="text" name="label" class="form-control" placeholder="Contoh: CS Work Order Staff">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi peranan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EDIT ROLE DETAILS --}}
<div class="modal fade" id="editDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Detail Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDetailForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Label Tampilan <span class="text-danger">*</span></label>
                        <input type="text" name="label" id="editModalLabel" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Deskripsi</label>
                        <textarea name="description" id="editModalDesc" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Detail</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function filterRolesList() {
        const input = document.getElementById('typeToSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.role-master-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.querySelector('.role-name-text')?.textContent.toLowerCase() || '';
            const code = row.querySelector('.role-code-text')?.textContent.toLowerCase() || '';
            const desc = row.querySelector('.role-desc-text')?.textContent.toLowerCase() || '';

            if (name.includes(input) || code.includes(input) || desc.includes(input)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('rowsCounterText').textContent = `Menampilkan 1-${visibleCount} dari ${visibleCount} data`;
    }

    function exportRolesCSV() {
        const rows = [["Kode", "Nama Role", "Description"]];
        document.querySelectorAll('.role-master-row').forEach(row => {
            if (row.style.display !== 'none') {
                const code = row.querySelector('.role-code-text')?.textContent.trim() || '';
                const name = row.querySelector('.role-name-text')?.textContent.trim() || '';
                const desc = row.querySelector('.role-desc-text')?.textContent.trim() || '';
                rows.push([`"${code}"`, `"${name}"`, `"${desc}"`]);
            }
        });
        const csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "roles_export.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function confirmDeleteRole(id, roleName) {
        Swal.fire({
            title: 'Hapus Role?',
            html: `
                <p class="mb-1">Role <b>${roleName}</b> akan dihapus dari sistem.</p>
                <p class="text-muted small mb-0">Tindakan ini tidak dapat dibatalkan.</p>
            `,
            icon: 'warning',
            iconColor: '#dc2626',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            customClass: { confirmButton: 'btn-danger' },
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Edit Detail Modal
        document.querySelectorAll('.btn-edit-detail').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const label = this.dataset.label || this.dataset.name;
                const desc = this.dataset.description;

                const form = document.getElementById('editDetailForm');
                form.action = `/roles/${id}`;

                document.getElementById('editModalLabel').value = label;
                document.getElementById('editModalDesc').value = desc;

                const modal = new bootstrap.Modal(document.getElementById('editDetailModal'));
                modal.show();
            });
        });
    });
</script>
@endsection
