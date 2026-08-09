@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Data Staff</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Kelola seluruh data karyawan di semua station.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if(Auth::user()->canAccess('user', 'export') || Auth::user()->role === 'Admin')
                <a href="{{ route('staff.export', ['station' => request('station')]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-download"></i>Export CSV
                </a>
                @endif
                @if(Auth::user()->canAccess('user', 'create') || Auth::user()->role === 'Admin')
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="ti ti-upload"></i>Import Staff
                </button>
                <a href="{{ route('stations.create') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-building-airport"></i>Station Baru
                </a>
                <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">
                    <i class="ti ti-user-plus"></i>Tambah Staff
                </a>
                @endif
            </div>
        </div>

        <div class="card">
            {{-- Tab Station --}}
            <div class="card-header" style="padding-bottom:0 !important;">
                <div class="nav-scroller">
                    <div class="nav nav-tabs">
                        @if ($isFullAccess)
                        <a class="nav-link {{ request('station') == null ? 'active' : '' }}" href="{{ route('staff.index') }}">
                            <i class="ti ti-world me-1"></i> Global
                        </a>
                        @endif
                        @foreach($stations as $st)
                        <a class="nav-link {{ (request('station') == $st->code || (!$isFullAccess && request('station') == null)) ? 'active' : '' }}" href="{{ route('staff.index', $isFullAccess ? ['station' => $st->code] : []) }}">
                            {{ $st->code }}
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if(request('station') || !$isFullAccess)
                <div class="d-flex align-items-center gap-2 mb-3 pb-3" style="border-bottom: 1px solid #f3f4f6;">
                    <i class="ti ti-info-circle text-muted"></i>
                    <small class="text-muted">Menampilkan data staff area: <strong>{{ request('station', Auth::user()->station) }}</strong></small>
                </div>
                @endif

                <x-dt-toolbar :searchFormAction="route('staff.index')" searchPlaceholder="Cari NIP / Nama..." />

                {{-- Tabel --}}
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Lengkap</th>
                                <th>NIP / ID</th>
                                <th>Jabatan</th>
                                <th>Station</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffs as $staff)
                            @php
                                $staffKey = trim((string)($staff->no_nik ?: $staff->id));
                                $isBlacklisted = in_array($staffKey, $blacklistedNiks ?? []);
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2">
                                            @if($staff->profile_picture)
                                            <img src="{{ asset('storage/photo/'.$staff->profile_picture) }}" alt="Avatar" class="rounded-circle" style="object-fit: cover; width:100%; height:100%;">
                                            @else
                                            <img src="{{ asset('storage/photo/user.jpg') }}" alt="Avatar" class="rounded-circle">
                                            @endif
                                        </div>
                                        <strong>{{ $staff->fullname }}</strong>
                                    </div>
                                </td>
                                <td>{{ $staff->id }}</td>
                                <td><span class="badge bg-label-primary">{{ $staff->role }}</span></td>
                                <td>
                                    <span class="badge bg-label-info">{{ $staff->station }}</span>
                                </td>
                                <td>
                                    @if($isBlacklisted)
                                    <span class="badge bg-danger" title="Di-blacklist"><i class="ti ti-ban me-1"></i>Blacklisted</span>
                                    @elseif(Auth::user()->role == 'Admin')
                                    <form action="{{ route('staff.toggle', $staff->id) }}" method="POST">
                                        @csrf
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" onchange="this.form.submit()" style="cursor: pointer;" {{ $staff->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label ms-1">
                                                @if($staff->is_active)
                                                <span class="badge bg-label-success">ON</span>
                                                @else
                                                <span class="badge bg-label-danger">OFF</span>
                                                @endif
                                            </label>
                                        </div>
                                    </form>
                                    @else
                                    @if($staff->is_active)
                                    <span class="badge bg-label-success">Active</span>
                                    @else
                                    <span class="badge bg-label-danger">Inactive</span>
                                    @endif
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <x-action-button action="view" :href="route('users.userProfile', $staff->id)" title="Detail" />
                                        @if(Auth::user()->canAccess('user', 'edit') || Auth::user()->role === 'Admin')
                                        <x-action-button action="edit" :href="route('users.edit', ['user' => $staff->id, 'redirect_to' => url()->full()])" title="Edit Staff" />
                                        @endif
                                        @if(!$isBlacklisted && (Auth::user()->canAccess('blacklist', 'create') || Auth::user()->role === 'Admin'))
                                        <x-action-button type="button" action="blacklist" onclick="openBanModal('{{ $staff->id }}', '{{ addslashes($staff->fullname) }}')" title="Blacklist" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="ti ti-user-x d-block"></i>
                                        <p>Belum ada data staff di station ini.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="dt-pagination-wrapper">
                    {{ $staffs->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL IMPORT STAFF --}}
@if(Auth::user()->role == 'Admin')
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Data Staff (Bulk)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('staff.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning text-small" style="font-size: 0.85rem;">
                        <i class="bx bx-info-circle me-1"></i>
                        Pastikan file CSV sesuai format. Password user baru otomatis: <b>password123</b>
                    </div>

                    <div class="mb-3 border-bottom pb-3">
                        <label class="form-label fw-bold">Langkah 1: Download Template</label>
                        <a href="{{ route('staff.template') }}" class="btn btn-outline-secondary w-100 btn-sm">
                            <i class="bx bx-download me-1"></i> Download Template CSV
                        </a>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-bold">Langkah 2: Upload File CSV</label>
                        <input type="file" name="file" class="form-control" required accept=".csv, .xlsx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="banModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3.5 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center p-2" style="width:2.2rem;height:2.2rem;">
                        <i class="ti ti-alert-triangle-filled text-danger fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-body mb-0" style="font-size: 1.05rem;">
                        Blacklist Staff (PHK & Ban)
                    </h5>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-label-secondary rounded-circle" data-bs-dismiss="modal" aria-label="Close" title="Tutup">
                    <i class="ti ti-x fs-5"></i>
                </button>
            </div>
            <form action="{{ route('blacklist.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 text-start">
                    <input type="hidden" name="user_id" id="ban_user_id">
                    
                    {{-- Warning Banner --}}
                    <div class="alert alert-danger bg-danger-subtle text-danger border-danger border-opacity-25 rounded-3 p-3 mb-3.5" role="alert">
                        <div class="d-flex gap-2.5 align-items-start">
                            <i class="ti ti-shield-x fs-4 flex-shrink-0 mt-0.5 text-danger"></i>
                            <div style="font-size:0.83rem; line-height:1.45;">
                                <strong>PERINGATAN:</strong> Tindakan ini akan <strong>mematikan akun</strong> staff dan mencatat namanya ke dalam daftar hitam (blacklist) perusahaan selamanya.
                            </div>
                        </div>
                    </div>

                    {{-- Staff Info --}}
                    <div class="mb-3.5">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Nama Staff</label>
                        <input type="text" class="form-control fw-bold" id="ban_user_name" readonly style="background-color: var(--bs-tertiary-bg, #f8fafc);">
                    </div>

                    {{-- Alasan Pelanggaran --}}
                    <div class="mb-3.5">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Alasan Pelanggaran <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Contoh: Terbukti mencuri aset perusahaan pada tanggal..."></textarea>
                    </div>

                    {{-- Lampiran PDF Wajib --}}
                    <div class="mb-1">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Dokumen Surat / SK Blacklist (Wajib PDF) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-body-tertiary"><i class="ti ti-file-text text-danger"></i></span>
                            <input type="file" name="attachment_file" class="form-control" accept=".pdf" required>
                        </div>
                        <div class="form-text mt-1.5 text-muted" style="font-size:0.75rem;">Unggah berkas Surat Keputusan (SK) atau bukti pelanggaran berformat PDF (Maksimal 2MB).</div>
                    </div>
                </div>
                <div class="modal-footer bg-body-tertiary px-4 py-3 border-top">
                    <button type="button" class="btn btn-label-secondary px-3.5" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">
                        <i class="ti ti-user-x me-1.5"></i> Ya, Blacklist Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openBanModal(id, name) {
        document.getElementById('ban_user_id').value = id;
        document.getElementById('ban_user_name').value = name;
        var myModal = new bootstrap.Modal(document.getElementById('banModal'));
        myModal.show();
    }
</script>
@endif

@endsection
