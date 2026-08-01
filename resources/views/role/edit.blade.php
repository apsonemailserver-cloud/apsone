@extends('layout.admin')

@section('title', 'Hak Akses ' . ($role->label ?: $role->name))

@section('content')
@php
    $categories = [
        'Operasional' => [
            'dashboard' => 'Dashboard Utama',
            'profile' => 'Profil Karyawan',
            'assignment' => 'Assignment (Work Order)',
            'attendance' => 'Attendance & Absensi',
            'overtime' => 'Lembur / Overtime',
            'schedule' => 'Schedule & Jadwal Kerja',
            'shift' => 'Shift Management',
        ],
        'Administrasi' => [
            'station' => 'Manajemen Station',
            'user' => 'User Management & Staff',
            'blacklist' => 'Blacklist Karyawan',
            'role' => 'Hak Akses & Wewenang',
        ],
        'Umum & Kepatuhan' => [
            'document' => 'Manajemen Dokumen',
            'training' => 'Training & Sertifikat',
            'leave' => 'Pengajuan Cuti',
            'announcement' => 'Pengumuman',
        ],
    ];
    $moduleActionsMap = \App\Models\Permission::moduleActionsMap();
    $actionLabels = [
        'view' => 'Lihat',
        'create' => 'Tambah',
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'approve' => 'Setujui',
        'export' => 'Ekspor',
    ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y role-access-page">
    <div class="role-access-header">
        <div class="role-access-heading">
            <a href="{{ route('roles.index') }}" class="role-back-button" aria-label="Kembali ke daftar role" title="Kembali">
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </a>
            <div>
                <div class="role-eyebrow">Manajemen akses</div>
                <div class="role-title-line">
                    <h4>{{ $role->label ?: $role->name }}</h4>
                    <span class="role-code">{{ $role->name }}</span>
                </div>
                <p>Atur akses modul dan karyawan yang menggunakan role ini.</p>
            </div>
        </div>

        @if($role->name === 'Admin')
            <div class="full-access-note">
                <i class="ti ti-shield-check" aria-hidden="true"></i>
                <div>
                    <strong>Akses penuh</strong>
                    <span>Role Admin selalu memiliki seluruh izin.</span>
                </div>
            </div>
        @endif
    </div>

    <form action="{{ route('roles.update', $role->id) }}" method="POST" id="permissionForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="label" value="{{ $role->label ?: $role->name }}">
        <input type="hidden" name="description" value="{{ $role->description }}">

        <div class="role-access-layout">
            <main class="permission-workspace">
                <div class="matrix-toolbar">
                    <label class="matrix-search" for="permissionSearchInput">
                        <i class="ti ti-search" aria-hidden="true"></i>
                        <input type="search" id="permissionSearchInput" placeholder="Cari modul atau izin" aria-label="Cari modul atau izin">
                    </label>
                    <div class="matrix-toolbar__actions" aria-label="Aksi seluruh izin">
                        <button type="button" class="matrix-btn matrix-btn--secondary" id="btnUnselectAll">
                            <i class="ti ti-square-off" aria-hidden="true"></i>
                            Bersihkan
                        </button>
                        <button type="button" class="matrix-btn matrix-btn--primary-soft" id="btnSelectAll">
                            <i class="ti ti-checks" aria-hidden="true"></i>
                            Pilih semua
                        </button>
                    </div>
                </div>

                <div class="permission-sections" id="permissionSections">
                    @foreach($categories as $catName => $modList)
                        @php
                            $categorySlug = Str::slug($catName);
                        @endphp
                        <section class="permission-section" data-category-card="{{ $categorySlug }}">
                            <header class="permission-section__header">
                                <div class="permission-section__identity">
                                    <span class="permission-section__icon"><i class="ti ti-folder" aria-hidden="true"></i></span>
                                    <div>
                                        <h5>{{ $catName }}</h5>
                                        <span><strong data-category-count="{{ $categorySlug }}">0</strong> izin aktif</span>
                                    </div>
                                </div>
                                <button type="button" class="section-action btn-select-cat-all" data-category="{{ $categorySlug }}" aria-pressed="false">
                                    <i class="ti ti-checks" aria-hidden="true"></i>
                                    <span>Pilih kategori</span>
                                </button>
                            </header>

                            <div class="permission-section__columns" aria-hidden="true">
                                <span>Modul</span>
                                <span>Hak akses</span>
                                <span>Aksi</span>
                            </div>

                            <div class="permission-section__body">
                                @foreach($modList as $modKey => $modLabel)
                                    @php
                                        $modPerms = ($permissionsByModule[$modKey] ?? collect())->keyBy('action');
                                        $validActions = $moduleActionsMap[$modKey] ?? ['view', 'create', 'edit', 'delete', 'approve', 'export'];
                                    @endphp
                                    <div class="permission-row module-perm-group" data-module="{{ $modKey }}" data-category-slug="{{ $categorySlug }}">
                                        <div class="permission-module">
                                            <strong>{{ $modLabel }}</strong>
                                            <span>{{ strtoupper($modKey) }}</span>
                                        </div>

                                        <div class="permission-options">
                                            @foreach($validActions as $actKey)
                                                @php
                                                    $perm = $modPerms[$actKey] ?? null;
                                                    $isChecked = $perm ? in_array($perm->id, $assignedPermissionIds) : false;
                                                    if ($role->name === 'Admin') {
                                                        $isChecked = true;
                                                    }
                                                    $actName = $actionLabels[$actKey] ?? ucfirst($actKey);
                                                @endphp

                                                @if($perm)
                                                    <span class="permission-control" data-perm-name="{{ strtolower($modLabel . ' ' . $actName . ' ' . $actKey) }}">
                                                        <input
                                                            type="checkbox"
                                                            name="permissions[]"
                                                            value="{{ $perm->id }}"
                                                            id="perm_input_{{ $perm->id }}"
                                                            class="permission-checkbox perm-checkbox-hidden perm-mod-cb-{{ $modKey }} perm-cat-cb-{{ $categorySlug }}"
                                                            {{ $isChecked ? 'checked' : '' }}
                                                        >
                                                        <label for="perm_input_{{ $perm->id }}" class="permission-toggle">
                                                            <i class="ti ti-check" aria-hidden="true"></i>
                                                            <span>{{ $actName }}</span>
                                                        </label>
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>

                                        <button type="button" class="row-action btn-select-module-all" data-module="{{ $modKey }}" aria-pressed="false">
                                            Pilih
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="permission-empty-state" id="permissionEmptyState" hidden>
                    <i class="ti ti-search-off" aria-hidden="true"></i>
                    <strong>Modul tidak ditemukan</strong>
                    <span>Coba gunakan kata kunci lain.</span>
                </div>
            </main>

            <aside class="employee-panel" aria-labelledby="employeePanelTitle">
                <header class="employee-panel__header">
                    <div>
                        <span class="employee-panel__eyebrow">Anggota role</span>
                        <h5 id="employeePanelTitle">{{ $role->label ?: $role->name }}</h5>
                    </div>
                    <span class="employee-count" id="activeEmpBadge">{{ $activeEmployeeCount }}</span>
                </header>

                <div class="employee-panel__tools">
                    <div class="employee-filter" role="group" aria-label="Filter karyawan">
                        <button type="button" class="employee-filter__button is-active" data-employee-filter="selected" aria-pressed="true">Terpilih</button>
                        <button type="button" class="employee-filter__button" data-employee-filter="all" aria-pressed="false">Semua</button>
                    </div>

                    <label class="employee-search" for="employeeSearchInput">
                        <i class="ti ti-search" aria-hidden="true"></i>
                        <input type="search" id="employeeSearchInput" placeholder="Cari nama, ID, atau station" aria-label="Cari karyawan">
                    </label>
                </div>

                <div class="employee-list-container" id="employeeList">
                    @forelse($employees as $emp)
                        <div class="employee-item-row {{ $emp->has_role ? 'is-selected active-emp-row' : 'inactive-emp-row' }}"
                             data-has-role="{{ $emp->has_role ? '1' : '0' }}"
                             data-search="{{ strtolower(($emp->fullname ?: '') . ' ' . $emp->id . ' ' . ($emp->station ?: '')) }}">
                            <div class="employee-identity">
                                <span class="employee-avatar avatar-initial">{{ strtoupper(substr($emp->fullname ?: 'U', 0, 2)) }}</span>
                                <div>
                                    <strong class="employee-name">{{ $emp->fullname ?: 'User #'.$emp->id }}</strong>
                                    <span class="employee-meta">
                                        <span class="employee-nip">ID {{ $emp->id }}</span>
                                        @if($emp->station)
                                            <span aria-hidden="true">·</span>
                                            <span>{{ $emp->station }}</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <label class="employee-switch" aria-label="Atur role untuk {{ $emp->fullname ?: 'User #'.$emp->id }}">
                                <input class="user-role-toggle" type="checkbox" data-user-id="{{ $emp->id }}" {{ $emp->has_role ? 'checked' : '' }}>
                                <span aria-hidden="true"></span>
                            </label>
                        </div>
                    @empty
                        <div class="employee-empty">Tidak ada karyawan terdaftar.</div>
                    @endforelse
                </div>

                <div class="employee-no-results" id="employeeNoResults" hidden>
                    Tidak ada karyawan yang cocok.
                </div>
            </aside>
        </div>

        <div class="role-savebar">
            <div class="role-savebar__status">
                <span id="floatingPermCounter">0</span>
                <span>izin aktif untuk role ini</span>
            </div>
            <div class="role-savebar__actions">
                <a href="{{ route('roles.index') }}" class="savebar-cancel">Batal</a>
                <button type="submit" class="savebar-submit">
                    <i class="ti ti-device-floppy" aria-hidden="true"></i>
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

<style>
    .role-access-page {
        --ra-surface: var(--bs-card-bg, #fff);
        --ra-surface-muted: #f6f8fc;
        --ra-surface-hover: #f0f4fa;
        --ra-border: #e1e7f0;
        --ra-border-strong: #cfd8e7;
        --ra-text: #26334d;
        --ra-muted: #75829a;
        --ra-primary: #2f7df4;
        --ra-primary-strong: #2268d2;
        --ra-primary-soft: #eaf3ff;
        --ra-danger: #dc4c64;
        --ra-shadow: 0 10px 30px rgba(35, 51, 80, 0.06);
        --ra-radius: 14px;
        color: var(--ra-text);
        padding-bottom: 2rem;
    }

    .dark-style .role-access-page {
        --ra-surface: #152137;
        --ra-surface-muted: #101a2c;
        --ra-surface-hover: #1b2942;
        --ra-border: #293852;
        --ra-border-strong: #374864;
        --ra-text: #e5ebf5;
        --ra-muted: #93a2b9;
        --ra-primary: #5b9cff;
        --ra-primary-strong: #7aafff;
        --ra-primary-soft: rgba(64, 137, 255, 0.14);
        --ra-danger: #ff6f83;
        --ra-shadow: 0 12px 32px rgba(0, 0, 0, 0.22);
    }

    .role-access-header,
    .role-access-heading,
    .role-title-line,
    .matrix-toolbar,
    .matrix-toolbar__actions,
    .permission-section__header,
    .permission-section__identity,
    .employee-panel__header,
    .role-savebar,
    .role-savebar__actions {
        display: flex;
        align-items: center;
    }

    .role-access-header {
        justify-content: space-between;
        gap: 1.25rem;
        margin-bottom: 1.25rem;
    }

    .role-access-heading { gap: .9rem; min-width: 0; }
    .role-access-heading > div { min-width: 0; }
    .role-access-heading p { margin: .25rem 0 0; color: var(--ra-muted); font-size: .84rem; }
    .role-eyebrow,
    .employee-panel__eyebrow {
        color: var(--ra-primary);
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .role-title-line { gap: .65rem; flex-wrap: wrap; margin-top: .1rem; }
    .role-title-line h4 { color: var(--ra-text); font-size: 1.32rem; line-height: 1.25; margin: 0; font-weight: 700; }
    .role-code {
        border-left: 1px solid var(--ra-border-strong);
        color: var(--ra-muted);
        font-family: var(--bs-font-monospace);
        font-size: .72rem;
        padding-left: .65rem;
    }

    .role-back-button {
        align-items: center;
        background: var(--ra-surface);
        border: 1px solid var(--ra-border);
        border-radius: 10px;
        color: var(--ra-text);
        display: inline-flex;
        flex: 0 0 38px;
        height: 38px;
        justify-content: center;
        transition: border-color .16s ease, color .16s ease, background-color .16s ease;
    }
    .role-back-button:hover { background: var(--ra-surface-hover); border-color: var(--ra-border-strong); color: var(--ra-primary); }

    .full-access-note {
        align-items: center;
        background: var(--ra-primary-soft);
        border: 1px solid color-mix(in srgb, var(--ra-primary) 28%, transparent);
        border-radius: 12px;
        color: var(--ra-primary-strong);
        display: flex;
        flex: 0 0 auto;
        gap: .65rem;
        padding: .65rem .8rem;
    }
    .full-access-note > i { font-size: 1.2rem; }
    .full-access-note strong,
    .full-access-note span { display: block; }
    .full-access-note strong { font-size: .78rem; }
    .full-access-note span { color: var(--ra-muted); font-size: .7rem; margin-top: .05rem; }

    .role-access-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 350px;
        gap: 1rem;
        align-items: start;
    }
    .permission-workspace { min-width: 0; }

    .matrix-toolbar {
        background: var(--ra-surface);
        border: 1px solid var(--ra-border);
        border-radius: var(--ra-radius);
        gap: .75rem;
        justify-content: space-between;
        margin-bottom: .85rem;
        padding: .7rem;
    }

    .matrix-search,
    .employee-search {
        align-items: center;
        background: var(--ra-surface-muted);
        border: 1px solid transparent;
        border-radius: 10px;
        display: flex;
        gap: .5rem;
        min-height: 38px;
        padding: 0 .7rem;
        transition: border-color .16s ease, background-color .16s ease;
    }
    .matrix-search { flex: 1 1 260px; max-width: 390px; }
    .matrix-search:focus-within,
    .employee-search:focus-within { background: var(--ra-surface); border-color: var(--ra-primary); }
    .matrix-search i,
    .employee-search i { color: var(--ra-muted); font-size: 1rem; }
    .matrix-search input,
    .employee-search input {
        background: transparent;
        border: 0;
        color: var(--ra-text);
        font-size: .8rem;
        min-width: 0;
        outline: 0;
        padding: 0;
        width: 100%;
    }
    .matrix-search input::placeholder,
    .employee-search input::placeholder { color: var(--ra-muted); opacity: .8; }

    .matrix-toolbar__actions { gap: .45rem; }
    .matrix-btn,
    .section-action,
    .row-action {
        align-items: center;
        border-radius: 9px;
        cursor: pointer;
        display: inline-flex;
        font-size: .76rem;
        font-weight: 600;
        gap: .35rem;
        justify-content: center;
        min-height: 36px;
        transition: background-color .16s ease, border-color .16s ease, color .16s ease;
    }
    .matrix-btn { border: 1px solid var(--ra-border); padding: 0 .75rem; }
    .matrix-btn--secondary { background: var(--ra-surface); color: var(--ra-muted); }
    .matrix-btn--secondary:hover { background: var(--ra-surface-hover); color: var(--ra-text); }
    .matrix-btn--primary-soft { background: var(--ra-primary-soft); border-color: transparent; color: var(--ra-primary-strong); }
    .matrix-btn--primary-soft:hover { border-color: var(--ra-primary); }

    .permission-sections { display: grid; gap: .85rem; }
    .permission-section,
    .employee-panel {
        background: var(--ra-surface);
        border: 1px solid var(--ra-border);
        border-radius: var(--ra-radius);
        box-shadow: var(--ra-shadow);
        overflow: hidden;
    }
    .permission-section__header {
        border-bottom: 1px solid var(--ra-border);
        justify-content: space-between;
        gap: 1rem;
        min-height: 64px;
        padding: .75rem 1rem;
    }
    .permission-section__identity { gap: .65rem; }
    .permission-section__icon {
        align-items: center;
        background: var(--ra-primary-soft);
        border-radius: 9px;
        color: var(--ra-primary);
        display: inline-flex;
        flex: 0 0 34px;
        height: 34px;
        justify-content: center;
    }
    .permission-section__identity h5 { color: var(--ra-text); font-size: .86rem; font-weight: 700; margin: 0; }
    .permission-section__identity span:not(.permission-section__icon) { color: var(--ra-muted); display: block; font-size: .68rem; margin-top: .12rem; }
    .permission-section__identity span strong { color: var(--ra-text); }
    .section-action { background: transparent; border: 0; color: var(--ra-muted); padding: 0 .4rem; }
    .section-action:hover,
    .section-action[aria-pressed="true"] { color: var(--ra-primary); }

    .permission-section__columns,
    .permission-row {
        display: grid;
        grid-template-columns: minmax(180px, 1.05fr) minmax(320px, 1.7fr) 64px;
        gap: 1rem;
    }
    .permission-section__columns {
        background: var(--ra-surface-muted);
        color: var(--ra-muted);
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .07em;
        padding: .48rem 1rem;
        text-transform: uppercase;
    }
    .permission-section__columns span:last-child { text-align: right; }
    .permission-row {
        align-items: center;
        border-top: 1px solid var(--ra-border);
        min-height: 68px;
        padding: .65rem 1rem;
        transition: background-color .16s ease;
    }
    .permission-row:first-child { border-top: 0; }
    .permission-row:hover { background: var(--ra-surface-muted); }
    .permission-module { min-width: 0; }
    .permission-module strong { color: var(--ra-text); display: block; font-size: .8rem; font-weight: 650; line-height: 1.35; }
    .permission-module span { color: var(--ra-muted); display: block; font-family: var(--bs-font-monospace); font-size: .62rem; letter-spacing: .04em; margin-top: .18rem; }
    .permission-options { display: flex; flex-wrap: wrap; gap: .4rem; }

    .permission-checkbox {
        height: 1px;
        margin: -1px;
        opacity: 0;
        overflow: hidden;
        position: absolute;
        width: 1px;
    }
    .permission-toggle {
        align-items: center;
        background: var(--ra-surface);
        border: 1px solid var(--ra-border-strong);
        border-radius: 8px;
        color: var(--ra-muted);
        cursor: pointer;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 600;
        gap: .3rem;
        min-height: 34px;
        padding: 0 .62rem;
        transition: background-color .14s ease, border-color .14s ease, color .14s ease, transform .14s ease;
    }
    .permission-toggle i { display: none; font-size: .86rem; }
    .permission-toggle:hover { border-color: var(--ra-primary); color: var(--ra-primary); }
    .permission-checkbox:focus-visible + .permission-toggle,
    .employee-switch input:focus-visible + span,
    .matrix-btn:focus-visible,
    .section-action:focus-visible,
    .row-action:focus-visible,
    .employee-filter__button:focus-visible,
    .savebar-cancel:focus-visible,
    .savebar-submit:focus-visible,
    .role-back-button:focus-visible {
        outline: 3px solid color-mix(in srgb, var(--ra-primary) 35%, transparent);
        outline-offset: 2px;
    }
    .permission-checkbox:checked + .permission-toggle {
        background: var(--ra-primary-soft);
        border-color: color-mix(in srgb, var(--ra-primary) 62%, var(--ra-border));
        color: var(--ra-primary-strong);
    }
    .permission-checkbox:checked + .permission-toggle i { display: inline-block; }
    .row-action { background: transparent; border: 0; color: var(--ra-muted); justify-self: end; padding: 0; }
    .row-action:hover,
    .row-action[aria-pressed="true"] { color: var(--ra-primary); }

    .permission-empty-state {
        align-items: center;
        background: var(--ra-surface);
        border: 1px dashed var(--ra-border-strong);
        border-radius: var(--ra-radius);
        color: var(--ra-muted);
        display: flex;
        flex-direction: column;
        gap: .25rem;
        justify-content: center;
        min-height: 180px;
        text-align: center;
    }
    .permission-empty-state[hidden] { display: none; }
    .permission-empty-state i { color: var(--ra-primary); font-size: 1.5rem; }
    .permission-empty-state strong { color: var(--ra-text); font-size: .82rem; }
    .permission-empty-state span { font-size: .72rem; }

    .employee-panel { position: sticky; top: 84px; }
    .employee-panel__header {
        border-bottom: 1px solid var(--ra-border);
        justify-content: space-between;
        min-height: 70px;
        padding: .85rem 1rem;
    }
    .employee-panel__header h5 { color: var(--ra-text); font-size: .9rem; font-weight: 700; margin: .15rem 0 0; }
    .employee-count {
        align-items: center;
        background: var(--ra-primary-soft);
        border-radius: 8px;
        color: var(--ra-primary-strong);
        display: inline-flex;
        font-size: .75rem;
        font-weight: 700;
        height: 30px;
        justify-content: center;
        min-width: 34px;
        padding: 0 .5rem;
    }
    .employee-panel__tools { border-bottom: 1px solid var(--ra-border); padding: .75rem; }
    .employee-filter { background: var(--ra-surface-muted); border-radius: 9px; display: grid; grid-template-columns: 1fr 1fr; margin-bottom: .6rem; padding: 3px; }
    .employee-filter__button {
        background: transparent;
        border: 0;
        border-radius: 7px;
        color: var(--ra-muted);
        font-size: .72rem;
        font-weight: 650;
        min-height: 31px;
    }
    .employee-filter__button.is-active { background: var(--ra-surface); box-shadow: 0 1px 4px rgba(35, 51, 80, .1); color: var(--ra-text); }
    .dark-style .employee-filter__button.is-active { box-shadow: 0 1px 5px rgba(0, 0, 0, .28); }
    .employee-search { width: 100%; }
    .employee-list-container { max-height: 520px; overflow-y: auto; padding: .45rem .55rem; scrollbar-width: thin; scrollbar-color: var(--ra-border-strong) transparent; }
    .employee-item-row {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 10px;
        display: flex;
        gap: .65rem;
        justify-content: space-between;
        min-height: 57px;
        padding: .48rem .55rem;
        transition: background-color .14s ease, border-color .14s ease, opacity .14s ease;
    }
    .employee-item-row + .employee-item-row { margin-top: .2rem; }
    .employee-item-row:hover { background: var(--ra-surface-muted); }
    .employee-item-row.is-selected { background: var(--ra-primary-soft); border-color: color-mix(in srgb, var(--ra-primary) 18%, transparent); }
    .employee-item-row.is-loading { opacity: .58; }
    .employee-identity { align-items: center; display: flex; gap: .55rem; min-width: 0; }
    .employee-identity > div { min-width: 0; }
    .employee-avatar {
        align-items: center;
        background: var(--ra-surface-muted);
        border: 1px solid var(--ra-border);
        border-radius: 9px;
        color: var(--ra-muted);
        display: inline-flex;
        flex: 0 0 34px;
        font-size: .68rem;
        font-weight: 700;
        height: 34px;
        justify-content: center;
    }
    .employee-item-row.is-selected .employee-avatar { background: var(--ra-primary); border-color: var(--ra-primary); color: #fff; }
    .employee-name { color: var(--ra-text); display: block; font-size: .75rem; font-weight: 650; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .employee-meta { align-items: center; color: var(--ra-muted); display: flex; font-family: var(--bs-font-monospace); font-size: .62rem; gap: .3rem; margin-top: .15rem; }

    .employee-switch { cursor: pointer; flex: 0 0 auto; margin: 0; }
    .employee-switch input { height: 1px; opacity: 0; position: absolute; width: 1px; }
    .employee-switch span { background: var(--ra-border-strong); border-radius: 999px; display: block; height: 22px; position: relative; transition: background-color .16s ease; width: 38px; }
    .employee-switch span::after { background: #fff; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,.2); content: ''; height: 16px; left: 3px; position: absolute; top: 3px; transition: transform .16s ease; width: 16px; }
    .employee-switch input:checked + span { background: var(--ra-primary); }
    .employee-switch input:checked + span::after { transform: translateX(16px); }
    .employee-switch input:disabled + span { cursor: wait; opacity: .65; }
    .employee-empty,
    .employee-no-results { color: var(--ra-muted); font-size: .73rem; padding: 1.5rem .75rem; text-align: center; }
    .employee-no-results[hidden] { display: none; }

    .role-savebar {
        background: color-mix(in srgb, var(--ra-surface) 94%, transparent);
        backdrop-filter: blur(14px);
        border: 1px solid var(--ra-border);
        border-radius: 12px;
        bottom: .75rem;
        box-shadow: var(--ra-shadow);
        justify-content: space-between;
        margin-top: 1rem;
        padding: .65rem .7rem .65rem .9rem;
        position: sticky;
        z-index: 20;
    }
    .role-savebar__status { color: var(--ra-muted); font-size: .74rem; }
    .role-savebar__status #floatingPermCounter { color: var(--ra-text); font-size: .88rem; font-weight: 750; margin-right: .2rem; }
    .role-savebar__actions { gap: .5rem; }
    .savebar-cancel,
    .savebar-submit { align-items: center; border-radius: 9px; display: inline-flex; font-size: .77rem; font-weight: 650; justify-content: center; min-height: 38px; padding: 0 .9rem; }
    .savebar-cancel { color: var(--ra-muted); }
    .savebar-cancel:hover { color: var(--ra-text); }
    .savebar-submit { background: var(--ra-primary); border: 1px solid var(--ra-primary); color: #fff; gap: .4rem; }
    .savebar-submit:hover { background: var(--ra-primary-strong); border-color: var(--ra-primary-strong); }

    @media (max-width: 1199.98px) {
        .role-access-layout { grid-template-columns: 1fr; }
        .employee-panel { position: static; }
        .employee-list-container { max-height: 400px; }
    }

    @media (max-width: 767.98px) {
        .role-access-page { padding-left: .85rem; padding-right: .85rem; }
        .role-access-header { align-items: flex-start; flex-direction: column; }
        .full-access-note { width: 100%; }
        .matrix-toolbar { align-items: stretch; flex-direction: column; }
        .matrix-search { max-width: none; width: 100%; }
        .matrix-toolbar__actions { display: grid; grid-template-columns: 1fr 1fr; }
        .permission-section__columns { display: none; }
        .permission-section__header { align-items: flex-start; }
        .section-action span { display: none; }
        .permission-row { gap: .55rem; grid-template-columns: 1fr auto; padding: .8rem; }
        .permission-module { grid-column: 1; }
        .row-action { grid-column: 2; grid-row: 1; }
        .permission-options { grid-column: 1 / -1; }
        .permission-toggle { min-height: 36px; }
        .role-savebar { align-items: stretch; flex-direction: column; gap: .65rem; }
        .role-savebar__status { padding: 0 .15rem; }
        .role-savebar__actions { display: grid; grid-template-columns: .7fr 1.3fr; }
    }

    @media (prefers-reduced-motion: reduce) {
        .role-access-page *,
        .role-access-page *::before,
        .role-access-page *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const permissionCheckboxes = Array.from(document.querySelectorAll('.permission-checkbox'));
        const employeeRows = Array.from(document.querySelectorAll('.employee-item-row'));
        let currentEmployeeFilter = 'selected';

        function setCheckboxes(checkboxes, checked) {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = checked;
                checkbox.dispatchEvent(new Event('change'));
            });
        }

        function updatePermissionUi() {
            const selectedCount = permissionCheckboxes.filter(function (checkbox) { return checkbox.checked; }).length;
            const counter = document.getElementById('floatingPermCounter');
            if (counter) counter.textContent = selectedCount;

            document.querySelectorAll('[data-category-card]').forEach(function (card) {
                const category = card.dataset.categoryCard;
                const categoryCheckboxes = Array.from(card.querySelectorAll('.permission-checkbox'));
                const categorySelected = categoryCheckboxes.filter(function (checkbox) { return checkbox.checked; }).length;
                const categoryCounter = document.querySelector('[data-category-count="' + category + '"]');
                const categoryButton = card.querySelector('.btn-select-cat-all');

                if (categoryCounter) categoryCounter.textContent = categorySelected;
                if (categoryButton) {
                    const isAllSelected = categoryCheckboxes.length > 0 && categorySelected === categoryCheckboxes.length;
                    categoryButton.setAttribute('aria-pressed', isAllSelected ? 'true' : 'false');
                    const label = categoryButton.querySelector('span');
                    if (label) label.textContent = isAllSelected ? 'Bersihkan kategori' : 'Pilih kategori';
                }
            });

            document.querySelectorAll('.module-perm-group').forEach(function (row) {
                const moduleCheckboxes = Array.from(row.querySelectorAll('.permission-checkbox'));
                const allSelected = moduleCheckboxes.length > 0 && moduleCheckboxes.every(function (checkbox) { return checkbox.checked; });
                const button = row.querySelector('.btn-select-module-all');
                if (button) {
                    button.setAttribute('aria-pressed', allSelected ? 'true' : 'false');
                    button.textContent = allSelected ? 'Hapus' : 'Pilih';
                }
            });
        }

        function filterPermissionsList() {
            const query = (document.getElementById('permissionSearchInput')?.value || '').trim().toLowerCase();
            let visibleRows = 0;

            document.querySelectorAll('[data-category-card]').forEach(function (card) {
                let visibleInCategory = 0;
                card.querySelectorAll('.module-perm-group').forEach(function (row) {
                    const matches = !query || row.textContent.toLowerCase().includes(query) || (row.dataset.module || '').includes(query);
                    row.hidden = !matches;
                    if (matches) {
                        visibleRows += 1;
                        visibleInCategory += 1;
                    }
                });
                card.hidden = visibleInCategory === 0;
            });

            const emptyState = document.getElementById('permissionEmptyState');
            if (emptyState) emptyState.hidden = visibleRows !== 0;
        }

        function updateEmployeeRow(row, hasRole) {
            row.dataset.hasRole = hasRole ? '1' : '0';
            row.classList.toggle('is-selected', hasRole);
            row.classList.toggle('active-emp-row', hasRole);
            row.classList.toggle('inactive-emp-row', !hasRole);
        }

        function updateActiveEmpCounter() {
            const activeCount = employeeRows.filter(function (row) { return row.dataset.hasRole === '1'; }).length;
            const badge = document.getElementById('activeEmpBadge');
            if (badge) badge.textContent = activeCount;
        }

        function filterEmployeeList() {
            const query = (document.getElementById('employeeSearchInput')?.value || '').trim().toLowerCase();
            let visibleRows = 0;

            employeeRows.forEach(function (row) {
                const matchesFilter = currentEmployeeFilter === 'all' || row.dataset.hasRole === '1';
                const matchesQuery = !query || (row.dataset.search || row.textContent.toLowerCase()).includes(query);
                const visible = matchesFilter && matchesQuery;
                row.hidden = !visible;
                if (visible) visibleRows += 1;
            });

            const noResults = document.getElementById('employeeNoResults');
            if (noResults) noResults.hidden = visibleRows !== 0;
        }

        function showEmployeeError(message) {
            if (window.Swal) {
                window.Swal.fire({ icon: 'error', title: 'Perubahan gagal', text: message, confirmButtonText: 'Tutup' });
                return;
            }
            window.alert(message);
        }

        permissionCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updatePermissionUi);
        });

        document.getElementById('permissionSearchInput')?.addEventListener('input', filterPermissionsList);
        document.getElementById('btnSelectAll')?.addEventListener('click', function () { setCheckboxes(permissionCheckboxes, true); });
        document.getElementById('btnUnselectAll')?.addEventListener('click', function () { setCheckboxes(permissionCheckboxes, false); });

        document.querySelectorAll('.btn-select-cat-all').forEach(function (button) {
            button.addEventListener('click', function () {
                const checkboxes = Array.from(document.querySelectorAll('.perm-cat-cb-' + button.dataset.category));
                const allSelected = checkboxes.length > 0 && checkboxes.every(function (checkbox) { return checkbox.checked; });
                setCheckboxes(checkboxes, !allSelected);
            });
        });

        document.querySelectorAll('.btn-select-module-all').forEach(function (button) {
            button.addEventListener('click', function () {
                const checkboxes = Array.from(document.querySelectorAll('.perm-mod-cb-' + button.dataset.module));
                const allSelected = checkboxes.length > 0 && checkboxes.every(function (checkbox) { return checkbox.checked; });
                setCheckboxes(checkboxes, !allSelected);
            });
        });

        document.querySelectorAll('[data-employee-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                currentEmployeeFilter = button.dataset.employeeFilter;
                document.querySelectorAll('[data-employee-filter]').forEach(function (filterButton) {
                    const active = filterButton === button;
                    filterButton.classList.toggle('is-active', active);
                    filterButton.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                filterEmployeeList();
            });
        });

        document.getElementById('employeeSearchInput')?.addEventListener('input', filterEmployeeList);

        document.querySelectorAll('.user-role-toggle').forEach(function (toggle) {
            toggle.addEventListener('change', async function () {
                const originalChecked = !toggle.checked;
                const row = toggle.closest('.employee-item-row');
                toggle.disabled = true;
                row.classList.add('is-loading');

                try {
                    const response = await fetch('/roles/{{ $role->id }}/toggle-user', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ user_id: toggle.dataset.userId })
                    });

                    if (!response.ok) throw new Error('Server menolak perubahan role.');
                    const data = await response.json();
                    if (!data.success) throw new Error(data.message || 'Role karyawan tidak dapat diperbarui.');

                    toggle.checked = Boolean(data.has_role);
                    updateEmployeeRow(row, Boolean(data.has_role));
                    updateActiveEmpCounter();
                    filterEmployeeList();
                } catch (error) {
                    toggle.checked = originalChecked;
                    updateEmployeeRow(row, originalChecked);
                    showEmployeeError(error.message || 'Role karyawan tidak dapat diperbarui.');
                } finally {
                    row.classList.remove('is-loading');
                    toggle.disabled = false;
                }
            });
        });

        updatePermissionUi();
        updateActiveEmpCounter();
        filterPermissionsList();
        filterEmployeeList();
    });
</script>
@endsection
