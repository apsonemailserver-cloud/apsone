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
                <a href="{{ route('roles.create') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus me-1"></i> Tambah Role
                </a>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <x-dt-toolbar searchPlaceholder="Cari ID / Nama Role..." onkeyup="filterRolesList()" />

                {{-- Tabel --}}
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Role</th>
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
                                        <span class="font-monospace text-dark role-id-text">{{ $role->id }}</span>
                                    </td>
                                    <td>
                                        <strong class="role-name-text">{{ $role->name }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-muted role-desc-text" style="font-size:0.875rem;">{{ $role->description ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            {{-- 1. Eye Button (Matriks Hak Akses) --}}
                                            @if(Auth::user()->canAccess('role', 'edit') || Auth::user()->role === 'Admin')
                                                <x-action-button action="view" :href="route('roles.permissions', $role->id)" title="Matriks Hak Akses" />
                                            @endif

                                            {{-- 2. Pencil Button (Edit Data Role) --}}
                                            @if(Auth::user()->canAccess('role', 'edit') || Auth::user()->role === 'Admin')
                                                <x-action-button action="edit" :href="route('roles.edit', $role->id)" title="Edit Data Role" />
                                            @endif

                                            {{-- 3. Delete Trash Button (SweetAlert2 Confirm) --}}
                                            @if(!$isSystem && (Auth::user()->canAccess('role', 'delete') || Auth::user()->role === 'Admin'))
                                                <form id="delete-form-{{ $role->id }}" action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-action-button type="button" action="delete" onclick="confirmDeleteRole('{{ $role->id }}', '{{ addslashes($role->name) }}')" title="Hapus Role" />
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
                @if($roles->isNotEmpty())
                    <div class="mt-4">
                        {{ $roles->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    function filterRolesList() {
        const input = document.getElementById('typeToSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.role-master-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.querySelector('.role-name-text')?.textContent.toLowerCase() || '';
            const id = row.querySelector('.role-id-text')?.textContent.toLowerCase() || '';
            const desc = row.querySelector('.role-desc-text')?.textContent.toLowerCase() || '';

            if (name.includes(input) || id.includes(input) || desc.includes(input)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('rowsCounterText').textContent = `Menampilkan 1-${visibleCount} dari ${visibleCount} data`;
    }

    function exportRolesCSV() {
        const rows = [["ID", "Nama Role", "Description"]];
        document.querySelectorAll('.role-master-row').forEach(row => {
            if (row.style.display !== 'none') {
                const id = row.querySelector('.role-id-text')?.textContent.trim() || '';
                const name = row.querySelector('.role-name-text')?.textContent.trim() || '';
                const desc = row.querySelector('.role-desc-text')?.textContent.trim() || '';
                rows.push([`"${id}"`, `"${name}"`, `"${desc}"`]);
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
        apsConfirmDelete({
            title: 'Hapus Role?',
            text: `Role ${roleName} akan dihapus dari sistem.`,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            formId: 'delete-form-' + id
        });
    }
</script>
@endsection
