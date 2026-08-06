@extends('layout.admin')

@section('title', 'Detail Pekerjaan - ' . $assignment->wo_number)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">Detail Pekerjaan</h4>
                <p class="text-secondary mb-0 small">Informasi lengkap Assignment pembersihan pesawat</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($assignment->photo_path)
                    <a href="{{ route('assignments.export_single_pdf', $assignment->id) }}" class="btn btn-danger me-1" target="_blank">
                        <i class="bx bxs-file-pdf me-1"></i> Cetak Hardcopy PDF
                    </a>
                @else
                    <button type="button" class="btn btn-secondary me-1 opacity-75 btn-no-photo-pdf" data-wo="{{ $assignment->wo_number }}">
                        <i class="bx bxs-file-pdf me-1"></i> Cetak Hardcopy PDF
                    </button>
                @endif
                <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <div class="row">
            {{-- Main Details Card --}}
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-bottom bg-white d-flex align-items-center justify-content-between py-3">
                        <h5 class="card-title text-dark fw-bold mb-0">
                            <i class="bx bx-detail text-primary me-2"></i>Informasi Assignment
                        </h5>
                        <span class="badge bg-label-primary px-3 py-2 font-monospace">{{ $assignment->wo_number }}</span>
                    </div>
                    <div class="card-body mt-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="text-secondary small fw-bold text-uppercase d-block mb-1">Stasiun & Tanggal Kerja</label>
                                <div class="p-3 bg-light rounded border border-dashed">
                                    <div class="fw-bold text-dark fs-5 mb-1">{{ \Carbon\Carbon::parse($assignment->date)->translatedFormat('d F Y') }}</div>
                                    <span class="badge bg-primary font-monospace">{{ $assignment->station }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-secondary small fw-bold text-uppercase d-block mb-1">Kategori Pekerjaan</label>
                                <div class="p-3 bg-light rounded border border-dashed">
                                    @if($assignment->type === 'DCI')
                                        <div class="fw-bold text-primary fs-5 mb-1"><i class="bx bx-home-alt me-1"></i>DCI</div>
                                        <span class="text-secondary small">Deep Cleaning Interior</span>
                                    @else
                                        <div class="fw-bold text-success fs-5 mb-1"><i class="bx bx-world me-1"></i>DCE</div>
                                        <span class="text-secondary small">Deep Cleaning Exterior</span>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12"><hr class="my-2"></div>

                            {{-- Aircraft Info --}}
                            <div class="col-md-4">
                                <label class="text-secondary small fw-bold text-uppercase d-block mb-1">Aircraft Reg</label>
                                <div class="fw-bold text-dark font-monospace fs-5">{{ $assignment->aircraft_reg }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-secondary small fw-bold text-uppercase d-block mb-1">Ex / To Flight</label>
                                <div class="fw-bold text-dark font-monospace">Ex: {{ $assignment->ex_flight ?: '-' }}</div>
                                <div class="small text-muted font-monospace">To: {{ $assignment->to_flight ?: '-' }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-secondary small fw-bold text-uppercase d-block mb-1">Stand & Jam Kerja</label>
                                <div class="fw-bold text-dark">Stand {{ $assignment->parking_stand }}</div>
                                <div class="small text-muted font-monospace">{{ substr($assignment->start_time, 0, 5) }} - {{ substr($assignment->end_time, 0, 5) }} ({{ $assignment->duration_minutes }} mnt)</div>
                            </div>

                            <div class="col-12"><hr class="my-2"></div>

                            {{-- Team / Workers Info --}}
                            <div class="col-md-6">
                                <label class="text-secondary small fw-bold text-uppercase mb-2">Leader Pengawas</label>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2 bg-primary rounded-circle text-white d-flex align-items-center justify-content-center fw-bold">
                                        {{ substr($assignment->submittedBy ? $assignment->submittedBy->fullname : 'L', 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $assignment->submittedBy ? $assignment->submittedBy->fullname : '-' }}</div>
                                        <div class="small text-muted font-monospace">ID: {{ $assignment->submitted_by ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="text-secondary small fw-bold text-uppercase mb-2">Jumlah Staff Terlibat</label>
                                <div class="fs-4 fw-bold text-primary"><i class="bx bx-group me-2"></i>{{ $assignment->users->count() }} Orang</div>
                            </div>
                        </div>

                        {{-- Workers List --}}
                        <div class="mt-4">
                            <label class="text-secondary small fw-bold text-uppercase mb-2 d-block">Daftar Anggota Tim yang Mengerjakan</label>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Nama Lengkap</th>
                                            <th>NIP / ID Staff</th>
                                            <th>Station</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($assignment->users as $idx => $st)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold text-dark">{{ $st->fullname }}</div>
                                                </td>
                                                <td class="font-monospace">{{ $st->id }}</td>
                                                <td><span class="badge bg-label-secondary font-monospace">{{ $st->station }}</span></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-3 text-muted">Tidak ada staff terdaftar.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Evidence Card --}}
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-bottom bg-white py-3 d-flex align-items-center justify-content-between">
                        <h5 class="card-title text-dark fw-bold mb-0">
                            <i class="bx bx-camera text-primary me-2"></i>Foto Bukti Kerja
                        </h5>
                        @if($assignment->photo_path && (auth()->user()->hasRole('Admin') || (auth()->user()->hasRole(\App\Models\Assignment::LEADER_ROLES) && $assignment->submitted_by === auth()->id())))
                            <button type="button" class="btn btn-xs btn-outline-secondary btn-upload-photo" data-id="{{ $assignment->id }}" data-wo="{{ $assignment->wo_number }}">
                                <i class="bx bx-upload me-1"></i> Ganti
                            </button>
                        @endif
                    </div>
                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center mt-3">
                        @if($assignment->photo_path)
                            <div class="border rounded p-2 bg-light w-100 mb-3 shadow-inner">
                                <img src="{{ asset('storage/' . $assignment->photo_path) }}" alt="Bukti Kerja" class="img-fluid rounded" style="max-height: 280px; width: 100%; object-fit: contain;">
                            </div>
                            <a href="{{ asset('storage/' . $assignment->photo_path) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="bx bx-expand-alt me-1"></i> Buka Gambar Penuh
                            </a>
                        @else
                            <div class="py-4 text-center w-100">
                                <div class="alert alert-warning border-warning border-opacity-50 py-3 mb-3">
                                    <i class="bx bx-error-circle fs-3 text-warning mb-1 d-block"></i>
                                    <div class="fw-bold text-dark mb-1">Foto Bukti Belum Ada</div>
                                    <div class="small text-muted">Upload foto bukti pekerjaan terlebih dahulu agar dokumen Laporan PDF dapat dicetak.</div>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm btn-upload-photo" data-id="{{ $assignment->id }}" data-wo="{{ $assignment->wo_number }}">
                                    <i class="bx bx-upload me-1"></i> Upload Foto Bukti Sekarang
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@section('styles')
<style>
    :root {
        --brand-primary: #2f80ed;
        --brand-primary-hover: #1d4ed8;
    }

    .btn-primary {
        background-color: var(--brand-primary) !important;
        border-color: var(--brand-primary) !important;
    }

    .btn-primary:hover {
        background-color: var(--brand-primary-hover) !important;
        border-color: var(--brand-primary-hover) !important;
    }

    .text-primary {
        color: var(--brand-primary) !important;
    }

    .bg-light {
        background-color: #f8fafc !important;
    }

    .border-dashed {
        border-style: dashed !important;
    }

    /* Dark Mode support */
    html.aps-dark .card,
    body.aps-camera-dark .card {
        background-color: #111c31 !important;
        border-color: #24324a !important;
        color: #dbe7f6 !important;
    }

    html.aps-dark .bg-light,
    body.aps-camera-dark .bg-light {
        background-color: #17233a !important;
        border-color: #2a3a55 !important;
    }

    html.aps-dark .table-light,
    body.aps-camera-dark .table-light {
        background-color: #1a263e !important;
        color: #94a3b8 !important;
    }
</style>

<!-- Modal Upload Foto Bukti Pekerjaan -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="uploadPhotoModalTitle">Upload Foto Bukti Pekerjaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadPhotoForm" action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-primary d-flex align-items-center py-2 px-3 mb-3">
                        <i class="bx bx-info-circle me-2 fs-5"></i>
                        <div class="small">Laporan PDF baru dapat dicetak setelah foto bukti pekerjaan diunggah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File Foto Bukti <span class="text-danger">*</span></label>
                        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/jpg" required>
                        <small class="text-muted d-block mt-1">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-upload me-1"></i> Simpan & Unggah Foto</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Trigger Upload Photo Modal
        $(document).on('click', '.btn-upload-photo', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const wo = $(this).data('wo');
            $('#uploadPhotoModalTitle').text('Upload Foto Bukti WO: ' + wo);
            $('#uploadPhotoForm').attr('action', '/work-results/' + id + '/upload-photo');
            const modal = new bootstrap.Modal(document.getElementById('uploadPhotoModal'));
            modal.show();
        });

        // Block Print PDF when no photo
        $(document).on('click', '.btn-no-photo-pdf', function(e) {
            e.preventDefault();
            const wo = $(this).data('wo');
            Swal.fire({
                icon: 'warning',
                iconColor: '#f59e0b',
                title: 'Foto Bukti Belum Ada',
                html: 'WO <strong>' + wo + '</strong> belum memiliki foto bukti pekerjaan.<br>Silakan unggah foto bukti terlebih dahulu agar dapat mencetak Laporan PDF.',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#2f80ed'
            });
        });
    });
</script>
@endsection
