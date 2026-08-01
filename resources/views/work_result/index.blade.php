@extends('layout.admin')

@section('title', 'Assignments')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">

        {{-- Header dengan Breadcrumb & Action --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">Assignments</h4>
                <p class="text-secondary mb-0 small">Monitoring aircraft deep cleaning assignment results (DCI & DCE)</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if(auth()->user()->hasPermission('assignment.create') || auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES))
                    <a href="{{ route('work_results.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus-circle me-1"></i> Create Assignment
                    </a>
                @endif
                @if(auth()->user()->hasPermission('assignment.export') || auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES))
                    <a href="{{ route('work_results.export.pdf', request()->query()) }}" class="btn btn-outline-secondary">
                        <i class="bx bx-download me-1"></i> Export Bulk PDF
                    </a>
                @endif
            </div>
        </div>

        {{-- Alert Success --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bx bx-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Filter Bar Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('work_results.index') }}" method="GET" class="row g-3 align-items-end">
                    @if(auth()->user()->hasRole('Admin'))
                        <div class="col-md-2">
                            <label class="form-label fw-semibold text-dark">STATION</label>
                            <select name="station" class="form-select">
                                <option value="All">-- All Stations --</option>
                                @foreach($stations as $st)
                                    <option value="{{ $st->code }}" {{ request('station') == $st->code ? 'selected' : '' }}>
                                        {{ $st->code }} - {{ $st->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-dark">TYPE</label>
                        <select name="type" class="form-select">
                            <option value="">-- All Types --</option>
                            <option value="DCI" {{ request('type') == 'DCI' ? 'selected' : '' }}>DCI (Interior)</option>
                            <option value="DCE" {{ request('type') == 'DCE' ? 'selected' : '' }}>DCE (Exterior)</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-dark">START DATE</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', $dateFrom ?? \Carbon\Carbon::now()->startOfMonth()->toDateString()) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-dark">END DATE</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', $dateTo ?? \Carbon\Carbon::now()->endOfMonth()->toDateString()) }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold text-dark">SEARCH DATA</label>
                        <input type="text" name="search" class="form-control" placeholder="Reg / WO / Flight..." value="{{ request('search') }}">
                    </div>

                    <div class="col-md-2 text-end">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-filter-alt me-1"></i> Filter
                            </button>
                            <a href="{{ route('work_results.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-refresh me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Main Table Card --}}
        <div class="card shadow-sm">
            <div class="card-header border-bottom bg-white d-flex align-items-center justify-content-between py-3">
                <h5 class="card-title text-dark fw-bold mb-0">
                    <i class="bx bx-list-ul text-primary me-2"></i>Assignment List
                </h5>
                <span class="badge bg-label-primary rounded-pill px-3 py-2 font-monospace">TOTAL: {{ $workResults->total() }} DATA</span>
            </div>

            <div class="card-body mt-3">
                @if($workResults->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bx bx-folder-open fs-1 mb-2 text-secondary d-block"></i>
                        <p class="mb-1 fw-bold text-dark">No Assignments Found</p>
                        <small class="text-secondary">Click "Create Assignment" to add a new record.</small>
                    </div>
                @else
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>DATE & STATION</th>
                                    <th>CATEGORY</th>
                                    <th>REGISTRATION & ASSIGNMENT</th>
                                    <th>EX / TO FLIGHT</th>
                                    <th>STAND & TIME</th>
                                    <th>EVIDENCE PHOTO</th>
                                    <th>STATUS</th>
                                    <th>LEADER</th>
                                    <th>STAFF ON DUTY</th>
                                    <th class="text-center" width="10%">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workResults as $index => $item)
                                    <tr>
                                        <td class="fw-semibold text-secondary">{{ $workResults->firstItem() + $index }}</td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($item->date)->format('d M Y') }}</div>
                                            <span class="badge bg-label-secondary mt-1 font-monospace">{{ $item->station }}</span>
                                        </td>
                                        <td>
                                            @if($item->type === 'DCI')
                                                <span class="badge bg-label-primary px-3 py-1.5 font-monospace fw-bold">DCI (INTERIOR)</span>
                                            @else
                                                <span class="badge bg-label-success px-3 py-1.5 font-monospace fw-bold">DCE (EXTERIOR)</span>
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="text-dark fs-6">{{ $item->aircraft_reg }}</strong>
                                            <div class="small text-muted font-monospace">WO: {{ $item->wo_number }}</div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold text-dark">Ex: {{ $item->ex_flight ?: '-' }}</div>
                                            <div class="small text-muted">To: {{ $item->to_flight ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark"><i class="bx bx-parking me-1 text-primary"></i>Stand {{ $item->parking_stand }}</div>
                                            <div class="small text-muted"><i class="bx bx-time me-1"></i>{{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }} ({{ $item->duration_minutes }} min)</div>
                                        </td>
                                        <td>
                                            @if($item->photo_path)
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-xs btn-label-primary py-1 px-2.5 rounded-pill btn-preview-photo" data-photo-url="{{ asset('storage/' . $item->photo_path) }}" data-wo="{{ $item->wo_number }}" title="Lihat Foto Bukti">
                                                        <i class="bx bx-image-alt me-1"></i> Lihat Foto
                                                    </button>
                                                    @if(auth()->user()->hasRole('Admin') || (auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES) && $item->submitted_by === auth()->id()))
                                                        <button type="button" class="btn btn-xs btn-icon btn-outline-secondary rounded-circle btn-upload-photo" data-id="{{ $item->id }}" data-wo="{{ $item->wo_number }}" title="Ganti Foto Bukti">
                                                            <i class="bx bx-upload"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                @if(auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES))
                                                    <button type="button" class="btn btn-xs btn-label-warning py-1 px-2.5 rounded-pill btn-upload-photo" data-id="{{ $item->id }}" data-wo="{{ $item->wo_number }}" title="Upload Foto Bukti Pekerjaan">
                                                        <i class="bx bx-upload me-1"></i> Upload Foto
                                                    </button>
                                                @else
                                                    <span class="badge bg-label-secondary"><i class="bx bx-image me-1"></i>Belum Ada Foto</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->photo_path)
                                                <span class="badge bg-label-success px-3 py-1.5 rounded-pill fw-semibold"><i class="bx bx-check me-1"></i>Selesai</span>
                                            @else
                                                <span class="badge bg-label-warning px-3 py-1.5 rounded-pill fw-semibold"><i class="bx bx-loader-alt bx-spin me-1"></i>Proses</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $item->submittedBy ? $item->submittedBy->fullname : '-' }}</div>
                                            <div class="small text-muted font-monospace">Leader</div>
                                        </td>
                                        <td>
                                            @if($item->users && $item->users->count() > 0)
                                                @foreach($item->users->take(2) as $st)
                                                    <span class="badge bg-label-primary me-1 mb-1 font-monospace" style="font-size: 0.75rem;">{{ $st->fullname }}</span>
                                                @endforeach
                                                @if($item->users->count() > 2)
                                                    <span class="badge bg-label-secondary me-1 mb-1 font-monospace" style="font-size: 0.75rem;">+{{ $item->users->count() - 2 }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <a href="{{ route('work_results.show', $item->id) }}" class="action-btn" title="Detail Pekerjaan">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                                @if(auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES))
                                                    @if($item->photo_path)
                                                        <a href="{{ route('work_results.export_single_pdf', $item->id) }}" class="action-btn action-edit" title="Cetak Hardcopy WO PDF" target="_blank">
                                                            <i class="bx bx-printer"></i>
                                                        </a>
                                                    @else
                                                        <button type="button" class="action-btn action-edit opacity-50 btn-no-photo-pdf" data-wo="{{ $item->wo_number }}" title="Belum Ada Foto (Tidak Bisa Dicetak)">
                                                            <i class="bx bx-printer"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                                @if(empty($item->photo_path) && (auth()->user()->hasRole('Admin') || (auth()->user()->hasRole(\App\Models\WorkResult::LEADER_ROLES) && $item->submitted_by === auth()->id())))
                                                    <form action="{{ route('work_results.destroy', $item->id) }}" method="POST" class="d-inline delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" class="action-btn action-delete btn-delete" title="Hapus Data Pekerjaan">
                                                            <i class="bx bx-trash"></i>
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
                    <div class="d-flex justify-content-end mt-4">
                        {{ $workResults->links() }}
                    </div>
                @endif
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
        --brand-soft-blue: #eef5ff;
    }

    .btn-primary {
        background-color: var(--brand-primary) !important;
        border-color: var(--brand-primary) !important;
    }

    .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
        background-color: var(--brand-primary-hover) !important;
        border-color: var(--brand-primary-hover) !important;
    }

    .btn-outline-primary {
        color: var(--brand-primary) !important;
        border-color: var(--brand-primary) !important;
    }

    .text-primary {
        color: var(--brand-primary) !important;
    }

    .bg-primary {
        background-color: var(--brand-primary) !important;
    }

    .bg-label-primary {
        background-color: var(--brand-soft-blue) !important;
        color: var(--brand-primary) !important;
    }

    .card-header .card-title {
        color: #1e293b !important;
    }
    
    .text-secondary {
        color: #64748b !important;
    }

    /* Dark Mode Support (html.aps-dark / body.aps-camera-dark) */
    html.aps-dark .card-header .card-title,
    body.aps-camera-dark .card-header .card-title {
        color: #eaf1fb !important;
    }

    html.aps-dark .text-secondary,
    body.aps-camera-dark .text-secondary {
        color: #94a3b8 !important;
    }

    html.aps-dark .card,
    body.aps-camera-dark .card {
        background-color: #111c31 !important;
        border-color: #24324a !important;
        color: #dbe7f6 !important;
    }

    html.aps-dark .form-control,
    html.aps-dark .form-select,
    body.aps-camera-dark .form-control,
    body.aps-camera-dark .form-select {
        background-color: #17233a !important;
        border-color: #2a3a55 !important;
        color: #eaf1fb !important;
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Photo Preview Popup
        $(document).on('click', '.btn-preview-photo', function(e) {
            e.preventDefault();
            const photoUrl = $(this).data('photo-url');
            const woNumber = $(this).data('wo');
            Swal.fire({
                title: 'Foto Bukti Pekerjaan',
                html: '<div class="text-muted mb-2 font-monospace">WO: <strong>' + woNumber + '</strong></div><img src="' + photoUrl + '" class="img-fluid rounded border shadow-sm" style="max-height: 380px; object-fit: contain;">',
                showCloseButton: true,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#2f80ed'
            });
        });

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

        // Delete confirmation
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            Swal.fire({
                title: 'Hapus Data Pekerjaan?',
                text: 'Data pekerjaan yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-outline-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
