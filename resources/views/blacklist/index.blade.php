@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Blacklist</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Daftar karyawan yang telah di-blacklist dari perusahaan.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <x-dt-toolbar :searchFormAction="route('blacklist.index')" searchPlaceholder="Cari data blacklist..." />

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NIK / ID</th>
                                <th>Nama</th>
                                <th>Alasan Blacklist</th>
                                <th>Station</th>
                                <th>Di Blacklist oleh</th>
                                <th>Tanggal</th>
                                <th>Lampiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($blacklists as $blacklist)
                            <tr>
                                <td><strong>{{ $blacklist->nik }}</strong></td>
                                <td>{{ $blacklist->fullname }}</td>
                                <td>{{ $blacklist->reason }}</td>
                                <td><span class="badge bg-label-info">{{ $blacklist->station }}</span></td>
                                <td>{{ $blacklist->banned_by }}</td>
                                <td>{{ \Carbon\Carbon::parse($blacklist->created_at)->translatedFormat('d F Y') }}</td>
                                <td>
                                    @if($blacklist->attachment_file)
                                        <a href="{{ asset('storage/'.$blacklist->attachment_file) }}" target="_blank" class="btn btn-xs btn-label-primary d-inline-flex align-items-center gap-1 py-1 px-2.5 rounded-pill" title="Lihat / Unduh Dokumen PDF">
                                            <i class="ti ti-file-text text-danger fs-6"></i>
                                            <span class="fw-semibold">Lihat PDF</span>
                                        </a>
                                    @else
                                        <span class="badge bg-label-secondary" style="font-size:0.7rem;">Tidak ada file</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state text-center py-4">
                                        <i class="bx bx-check-shield d-block fs-1 mb-2 text-muted"></i>
                                        <p class="mb-0 text-muted">Tidak ada data blacklist.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="dt-pagination-wrapper">
                    {{ $blacklists->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL IMPORT & BAN --}}
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
            <div class="modal-header bg-danger text-white py-3 px-4">
                <h5 class="modal-title text-white fw-bold d-flex align-items-center gap-2" style="font-size: 1.05rem;">
                    <i class="ti ti-alert-triangle-filled fs-4"></i> Blacklist Staff (PHK & Ban)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('blacklist.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 text-start">
                    <input type="hidden" name="user_id" id="ban_user_id">
                    
                    {{-- Warning Banner --}}
                    <div class="alert alert-danger bg-danger-subtle text-danger border-danger border-opacity-25 rounded-3 p-3 mb-3" role="alert">
                        <div class="d-flex gap-2">
                            <i class="ti ti-shield-x fs-4 flex-shrink-0 mt-0.5"></i>
                            <div style="font-size:0.83rem; line-height:1.4;">
                                <strong>PERINGATAN:</strong> Tindakan ini akan <strong>mematikan akun</strong> staff dan mencatat namanya ke dalam daftar hitam (blacklist) perusahaan selamanya.
                            </div>
                        </div>
                    </div>

                    {{-- Staff Info --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Nama Staff</label>
                        <input type="text" class="form-control fw-bold" id="ban_user_name" readonly style="background-color: var(--bs-tertiary-bg, #f8fafc);">
                    </div>

                    {{-- Alasan Pelanggaran --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Alasan Pelanggaran <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Contoh: Terbukti mencuri aset perusahaan pada tanggal..."></textarea>
                    </div>

                    {{-- Lampiran PDF Wajib --}}
                    <div class="mb-1">
                        <label class="form-label fw-semibold text-body" style="font-size:0.82rem;">Dokumen Surat / SK Blacklist (Wajib PDF) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-file-text text-danger"></i></span>
                            <input type="file" name="attachment_file" class="form-control" accept=".pdf" required>
                        </div>
                        <div class="form-text mt-1" style="font-size:0.75rem;">Unggah berkas Surat Keputusan (SK) atau bukti pelanggaran berformat PDF (Maksimal 2MB).</div>
                    </div>
                </div>
                <div class="modal-footer bg-body-tertiary px-4 py-3 border-top">
                    <button type="button" class="btn btn-label-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">
                        <i class="ti ti-user-x me-1"></i> Ya, Blacklist Staff
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
