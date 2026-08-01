@extends('layout.admin')

@section('title', 'Permission ' . ($role->label ?: $role->name))

@section('content')
<div class="container-xxl flex-grow-1 container-p-y pb-5 mb-5">
    <div class="py-3">
        {{-- Top Header Nav & Toolbar --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('roles.index') }}" class="btn btn-icon btn-outline-secondary me-3 rounded-circle shadow-xs" title="Kembali ke Daftar Role">
                    <i class="ti ti-arrow-left fs-4"></i>
                </a>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h4 class="fw-bold mb-0 text-dark">Matriks Hak Akses: {{ $role->label ?: $role->name }}</h4>
                        <span class="badge bg-label-primary font-monospace">{{ $role->name }}</span>
                        @if($role->name === 'Admin')
                            <span class="badge bg-success font-monospace"><i class="ti ti-shield-check me-1"></i>FULL ACCESS</span>
                        @endif
                    </div>
                    <p class="text-muted mb-0 small">Atur atribusi izin modul dan alokasi karyawan terdaftar untuk role ini.</p>
                </div>
            </div>

            {{-- Header Action Buttons --}}
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="dt-search style-search" style="min-width: 200px; max-width: 260px;">
                    <i class="ti ti-search search-icon"></i>
                    <input type="text" id="permissionSearchInput" class="form-control form-control-sm" placeholder="Cari modul / izin..." onkeyup="filterPermissionsList()">
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnSelectAll">
                    <i class="ti ti-check-all me-1"></i>Pilih Semua
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnUnselectAll">
                    <i class="ti ti-x me-1"></i>Batal Semua
                </button>
            </div>
        </div>

        <form action="{{ route('roles.update', $role->id) }}" method="POST" id="permissionForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="label" value="{{ $role->label ?: $role->name }}">
            <input type="hidden" name="description" value="{{ $role->description }}">

            <div class="row g-4">
                {{-- Main Left Column: Permission Categories --}}
                <div class="col-lg-8">
                    @php
                        $categories = [
                            'OPERATIONAL' => [
                                'dashboard' => 'Dashboard Utama',
                                'profile' => 'Profile Karyawan',
                                'assignment' => 'Assignment (Work Order)',
                                'attendance' => 'Attendance & Absensi Hari Ini',
                                'overtime' => 'Lembur / Overtime',
                                'schedule' => 'Schedule & Jadwal Kerja',
                                'shift' => 'Shift Management',
                            ],
                            'ADMINISTRATION' => [
                                'station' => 'Manajemen Station',
                                'user' => 'User Management & Staff',
                                'blacklist' => 'Blacklist Karyawan',
                                'role' => 'Hak Akses Role & Wewenang',
                            ],
                            'GENERAL & COMPLIANCE' => [
                                'document' => 'Dokumen Management',
                                'training' => 'Training & Sertifikat',
                                'leave' => 'Apply Leave / Pengajuan Cuti',
                                'announcement' => 'Pengumuman / Announcement',
                            ]
                        ];

                        $moduleActionsMap = \App\Models\Permission::moduleActionsMap();
                    @endphp

                    @foreach($categories as $catName => $modList)
                        <div class="card border-0 shadow-sm mb-4 category-card">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-folder text-primary me-2 fs-5"></i>
                                    <h6 class="fw-bold mb-0 text-primary text-uppercase letter-spacing-1" style="font-size:0.85rem;">{{ $catName }}</h6>
                                </div>
                                <button type="button" class="btn btn-xs btn-label-secondary font-monospace rounded-2 btn-select-cat-all" data-category="{{ Str::slug($catName) }}">
                                    Pilih Kategori Ini
                                </button>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="32%" class="ps-3 py-2.5 text-uppercase small font-monospace">Modul / Fitur</th>
                                                <th width="53%" class="py-2.5 text-uppercase small font-monospace">Hak Akses Modul</th>
                                                <th width="15%" class="pe-3 py-2.5 text-end text-uppercase small font-monospace">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($modList as $modKey => $modLabel)
                                                @php
                                                    $modPerms = ($permissionsByModule[$modKey] ?? collect())->keyBy('action');
                                                    $validActions = $moduleActionsMap[$modKey] ?? ['view', 'create', 'edit', 'delete', 'approve', 'export'];
                                                @endphp
                                                <tr class="module-perm-group" data-module="{{ $modKey }}" data-category-slug="{{ Str::slug($catName) }}">
                                                    <td class="ps-3 py-3">
                                                        <div class="fw-bold text-dark" style="font-size:0.88rem;">{{ $modLabel }}</div>
                                                        <span class="badge bg-label-secondary font-monospace" style="font-size:0.68rem;">{{ $modKey }}</span>
                                                    </td>
                                                    <td class="py-3">
                                                        <div class="d-flex flex-wrap gap-2">
                                                            @foreach($validActions as $actKey)
                                                                @php
                                                                    $perm = $modPerms[$actKey] ?? null;
                                                                    $isChecked = $perm ? in_array($perm->id, $assignedPermissionIds) : false;
                                                                    if ($role->name === 'Admin') {
                                                                        $isChecked = true;
                                                                    }
                                                                    $actName = ucfirst($actKey);
                                                                @endphp

                                                                @if($perm)
                                                                    <div class="perm-pill-container" data-perm-name="{{ strtolower($modLabel . ' ' . $actName) }}">
                                                                        <input type="checkbox" 
                                                                               name="permissions[]" 
                                                                               value="{{ $perm->id }}" 
                                                                               id="perm_input_{{ $perm->id }}" 
                                                                               class="d-none perm-checkbox-hidden perm-mod-cb-{{ $modKey }} perm-cat-cb-{{ Str::slug($catName) }}" 
                                                                               {{ $isChecked ? 'checked' : '' }}>
                                                                        
                                                                        <label for="perm_input_{{ $perm->id }}" 
                                                                               class="perm-pill-btn btn btn-sm rounded-pill d-inline-flex align-items-center px-3 py-1.5 cursor-pointer user-select-none transition-all {{ $isChecked ? 'active-pill' : 'inactive-pill' }}">
                                                                            <i class="ti ti-check me-1 fs-6 perm-check-icon {{ $isChecked ? '' : 'd-none' }}"></i>
                                                                            <span>{{ $actName }}</span>
                                                                        </label>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="pe-3 py-3 text-end">
                                                        <a href="javascript:void(0)" class="text-primary font-monospace small btn-select-module-all fw-semibold" data-module="{{ $modKey }}">
                                                            Pilih Semua
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Right Sidebar Column: Employee on Role (ACTIVE SORTED FIRST) --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-top" style="top: 85px;">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-users text-primary me-2 fs-5"></i>
                                <h6 class="fw-bold mb-0 text-dark">Employee on {{ $role->label ?: $role->name }}</h6>
                            </div>
                            <span class="badge bg-primary rounded-pill font-monospace" id="activeEmpBadge">
                                {{ $activeEmployeeCount }} Aktif
                            </span>
                        </div>

                        <div class="card-body p-3">
                            <div class="mb-3">
                                <div class="dt-search w-100">
                                    <i class="ti ti-search search-icon"></i>
                                    <input type="text" id="employeeSearchInput" class="form-control" placeholder="Cari nama / ID / station..." onkeyup="filterEmployeeList()">
                                </div>
                            </div>

                            <div class="employee-list-container pe-1" style="max-height: 520px; overflow-y: auto;">
                                @forelse($employees as $emp)
                                    <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded-3 employee-item-row transition-all {{ $emp->has_role ? 'bg-label-primary border-start border-3 border-primary active-emp-row' : 'bg-light inactive-emp-row' }}"
                                         data-has-role="{{ $emp->has_role ? '1' : '0' }}">
                                        <div class="d-flex align-items-center me-2 overflow-hidden">
                                            <div class="avatar avatar-sm me-2 flex-shrink-0">
                                                <span class="avatar-initial rounded-circle {{ $emp->has_role ? 'bg-primary text-white' : 'bg-label-secondary' }}" style="font-size:0.75rem;">
                                                    {{ strtoupper(substr($emp->fullname ?: 'U', 0, 2)) }}
                                                </span>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="fw-bold text-dark text-truncate employee-name" style="font-size: 0.85rem;">{{ $emp->fullname ?: 'User #'.$emp->id }}</div>
                                                <div class="d-flex align-items-center gap-1.5 text-muted font-monospace" style="font-size: 0.72rem;">
                                                    <span class="employee-nip">ID: {{ $emp->id }}</span>
                                                    @if($emp->station)
                                                        <span>•</span>
                                                        <span class="badge bg-label-info p-0 px-1 font-monospace" style="font-size:0.65rem;">{{ $emp->station }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input class="form-check-input user-role-toggle cursor-pointer" 
                                                   type="checkbox" 
                                                   data-user-id="{{ $emp->id }}" 
                                                   {{ $emp->has_role ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-4 small">
                                        Tidak ada karyawan terdaftar.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Floating Save Button Widget --}}
            <div class="floating-save-pill d-flex align-items-center gap-2 p-2 rounded-pill shadow-lg bg-white border">
                <span class="badge bg-label-primary rounded-pill font-monospace px-3 py-2" id="floatingPermCounter" style="font-size:0.8rem;">
                    <i class="ti ti-shield-check me-1"></i>0 Izin Terpilih
                </span>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">Batal</a>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold rounded-pill shadow-sm">
                    <i class="ti ti-device-floppy me-1.5 fs-5"></i> Simpan Hak Akses Role
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Clean Permission Pill Buttons */
    .perm-pill-btn {
        border: 1px solid #e2e8f0;
        font-size: 0.8rem;
        font-weight: 500;
        background-color: #f8fafc;
        color: #475569;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .perm-pill-btn:hover {
        border-color: #3b82f6;
        color: #2563eb;
        background-color: #eff6ff;
    }
    
    /* Active Pill: Crisp Light Blue Theme (#eff6ff) */
    .perm-pill-btn.active-pill {
        background-color: #eff6ff !important;
        border-color: #3b82f6 !important;
        color: #1d4ed8 !important;
        font-weight: 600;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12);
    }
    .perm-pill-btn.inactive-pill {
        background-color: #f8fafc;
        border-color: #e2e8f0;
        color: #64748b;
    }

    /* Floating Save Widget in Bottom-Right Corner */
    .floating-save-pill {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 1050;
        background-color: rgba(255, 255, 255, 0.96) !important;
        backdrop-filter: blur(12px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        border: 1px solid rgba(226, 232, 240, 0.9) !important;
    }

    /* Dark Mode Support */
    .dark-style .perm-pill-btn {
        background-color: #2b2c40;
        border-color: #3b3c54;
        color: #a6a8c0;
    }
    .dark-style .perm-pill-btn.active-pill {
        background-color: rgba(37, 99, 235, 0.22) !important;
        border-color: #3b82f6 !important;
        color: #60a5fa !important;
    }
    .dark-style .floating-save-pill {
        background-color: rgba(43, 44, 64, 0.96) !important;
        border-color: #3b3c54 !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
    }
    
    .letter-spacing-1 {
        letter-spacing: 0.5px;
    }
</style>
@endsection

@section('scripts')
<script>
    function filterPermissionsList() {
        const input = document.getElementById('permissionSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.module-perm-group');

        rows.forEach(row => {
            const text = row.dataset.module || '';
            const labelText = row.querySelector('.fw-bold')?.textContent.toLowerCase() || '';
            if (text.includes(input) || labelText.includes(input)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function filterEmployeeList() {
        const input = document.getElementById('employeeSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.employee-item-row');

        rows.forEach(row => {
            const name = row.querySelector('.employee-name')?.textContent.toLowerCase() || '';
            const nip = row.querySelector('.employee-nip')?.textContent.toLowerCase() || '';

            if (name.includes(input) || nip.includes(input)) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function updateActiveEmpCounter() {
        const activeCount = document.querySelectorAll('.active-emp-row').length;
        const badge = document.getElementById('activeEmpBadge');
        if (badge) {
            badge.textContent = `${activeCount} Aktif`;
        }
    }

    function updateFloatingPermCounter() {
        const selectedCount = document.querySelectorAll('.perm-checkbox-hidden:checked').length;
        const counter = document.getElementById('floatingPermCounter');
        if (counter) {
            counter.innerHTML = `<i class="ti ti-shield-check me-1"></i>${selectedCount} Izin Terpilih`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateFloatingPermCounter();

        // Toggle Select All global
        document.getElementById('btnSelectAll')?.addEventListener('click', function() {
            document.querySelectorAll('.perm-checkbox-hidden').forEach(cb => {
                cb.checked = true;
                cb.dispatchEvent(new Event('change'));
            });
        });

        // Unselect All global
        document.getElementById('btnUnselectAll')?.addEventListener('click', function() {
            document.querySelectorAll('.perm-checkbox-hidden').forEach(cb => {
                cb.checked = false;
                cb.dispatchEvent(new Event('change'));
            });
        });

        // Toggle Category All
        document.querySelectorAll('.btn-select-cat-all').forEach(btn => {
            btn.addEventListener('click', function() {
                const catSlug = this.dataset.category;
                const checkboxes = document.querySelectorAll(`.perm-cat-cb-${catSlug}`);
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);

                checkboxes.forEach(cb => {
                    cb.checked = !allChecked;
                    cb.dispatchEvent(new Event('change'));
                });
            });
        });

        // Toggle pill visual state when hidden checkbox changes
        document.querySelectorAll('.perm-checkbox-hidden').forEach(cb => {
            cb.addEventListener('change', function() {
                const label = this.nextElementSibling;
                const icon = label.querySelector('.perm-check-icon');

                if (this.checked) {
                    label.classList.remove('inactive-pill');
                    label.classList.add('active-pill');
                    if (icon) icon.classList.remove('d-none');
                } else {
                    label.classList.remove('active-pill');
                    label.classList.add('inactive-pill');
                    if (icon) icon.classList.add('d-none');
                }
                updateFloatingPermCounter();
            });
        });

        // Select All in Module Button
        document.querySelectorAll('.btn-select-module-all').forEach(btn => {
            btn.addEventListener('click', function() {
                const modKey = this.dataset.module;
                const checkboxes = document.querySelectorAll(`.perm-mod-cb-${modKey}`);
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);

                checkboxes.forEach(cb => {
                    cb.checked = !allChecked;
                    cb.dispatchEvent(new Event('change'));
                });
            });
        });

        // AJAX Toggle Employee Role Switch on Right Sidebar
        document.querySelectorAll('.user-role-toggle').forEach(sw => {
            sw.addEventListener('change', function() {
                const userId = this.dataset.userId;
                const isChecked = this.checked;
                const row = this.closest('.employee-item-row');
                const avatar = row.querySelector('.avatar-initial');

                fetch(`/roles/{{ $role->id }}/toggle-user`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.has_role) {
                            row.classList.add('bg-label-primary', 'border-start', 'border-3', 'border-primary', 'active-emp-row');
                            row.classList.remove('bg-light', 'inactive-emp-row');
                            if (avatar) {
                                avatar.classList.add('bg-primary', 'text-white');
                                avatar.classList.remove('bg-label-secondary');
                            }
                        } else {
                            row.classList.remove('bg-label-primary', 'border-start', 'border-3', 'border-primary', 'active-emp-row');
                            row.classList.add('bg-light', 'inactive-emp-row');
                            if (avatar) {
                                avatar.classList.remove('bg-primary', 'text-white');
                                avatar.classList.add('bg-label-secondary');
                            }
                        }
                        updateActiveEmpCounter();
                    } else {
                        alert('Gagal memperbarui role karyawan.');
                        sw.checked = !isChecked;
                    }
                })
                .catch(err => {
                    console.error(err);
                    sw.checked = !isChecked;
                });
            });
        });
    });
</script>
@endsection
