@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Manajemen Pengumuman</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Daftar dan kontrol status operasional pengumuman.</p>
            </div>
            @if(strtolower((string) Auth::user()->role) === 'admin')
                <a href="{{ route('announcements.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus"></i> Buat Pengumuman Baru
                </a>
            @endif
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="ti ti-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>STATUS</th>
                                <th>JUDUL & ISI PENGUMUMAN</th>
                                <th>TARGET STATION</th>
                                <th>DIBUAT OLEH</th>
                                <th>TANGGAL</th>
                                @if(strtolower((string) Auth::user()->role) === 'admin')
                                    <th>AKSI</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($announcements as $announcement)
                                @php
                                    $isRead = in_array($announcement->id, $readIds);
                                    $targetStations = $announcement->target_stations ?? ['ALL'];
                                    $isAllStation = in_array('ALL', $targetStations, true);
                                @endphp
                                <tr class="cursor-pointer {{ !$isRead ? 'table-warning-subtle' : '' }}" 
                                    id="announcement-row-{{ $announcement->id }}"
                                    onclick="openAnnouncementModal(event, 'detailModal{{ $announcement->id }}')">
                                    <td>
                                        @if(!$isRead)
                                            <span class="badge bg-danger" id="row-status-{{ $announcement->id }}">BARU</span>
                                        @else
                                            <span class="badge bg-label-secondary" id="row-status-{{ $announcement->id }}">DIBACA</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold mb-1" style="font-size: 0.95rem;">
                                            {{ $announcement->title }}
                                        </div>
                                        <div class="text-muted small text-truncate" style="max-width: 450px;">
                                            {{ Str::limit(strip_tags($announcement->content), 90) }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">
                                            <i class="ti ti-map-pin me-1"></i>{{ $isAllStation ? 'SEMUA STATION' : implode(', ', $targetStations) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small">
                                            <i class="ti ti-user me-1 text-muted"></i>{{ $announcement->author ? $announcement->author->fullname : 'Admin' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="d-block">{{ $announcement->created_at->format('d M Y H:i') }}</small>
                                        <small class="text-muted opacity-75">{{ $announcement->created_at->diffForHumans() }}</small>
                                    </td>
                                    @if(strtolower((string) Auth::user()->role) === 'admin')
                                        <td onclick="event.stopPropagation();" class="no-modal-trigger">
                                            <div class="d-flex align-items-center gap-2" onclick="event.stopPropagation();">
                                                <a href="{{ route('announcements.edit', $announcement->id) }}"
                                                    class="btn btn-sm btn-warning"
                                                    onclick="event.stopPropagation();">
                                                    <i class="ti ti-pencil"></i> Edit
                                                </a>

                                                <form action="{{ route('announcements.destroy', $announcement->id) }}"
                                                    method="POST"
                                                    class="d-inline"
                                                    id="delete-form-{{ $announcement->id }}"
                                                    onclick="event.stopPropagation();">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button"
                                                        class="action-btn action-delete border-0"
                                                        title="Hapus Pengumuman"
                                                        onclick="event.stopPropagation(); confirmDeleteAnnouncement(event, '{{ $announcement->id }}', '{{ e($announcement->title) }}');">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>

                                {{-- Modal Detail Pengumuman --}}
                                <div class="modal fade announcement-detail-modal" 
                                     id="detailModal{{ $announcement->id }}" 
                                     data-read-url="{{ route('announcements.read', $announcement->id) }}"
                                     data-status-badge-id="row-status-{{ $announcement->id }}"
                                     data-row-id="announcement-row-{{ $announcement->id }}"
                                     tabindex="-1" 
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content border-0 shadow-lg">
                                            <div class="modal-header bg-primary text-white py-3 px-4">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="ti ti-speakerphone fs-4"></i>
                                                    <h5 class="modal-title text-white fw-bold mb-0">{{ $announcement->title }}</h5>
                                                </div>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom text-muted small">
                                                    <div>
                                                        <span class="me-3"><i class="ti ti-user me-1 text-primary"></i><strong>Dibuat oleh:</strong> {{ $announcement->author ? $announcement->author->fullname : 'Admin' }}</span>
                                                        <span><i class="ti ti-calendar me-1 text-primary"></i><strong>Tanggal:</strong> {{ $announcement->created_at->format('d M Y H:i') }} ({{ $announcement->created_at->diffForHumans() }})</span>
                                                    </div>
                                                    <span class="badge bg-label-info rounded-pill px-3 py-1">
                                                        <i class="ti ti-map-pin me-1"></i>
                                                        {{ $isAllStation ? 'Semua Station' : implode(', ', $targetStations) }}
                                                    </span>
                                                </div>
                                                <div class="announcement-full-content text-dark p-2" style="font-size: 0.96rem; line-height: 1.7; white-space: pre-line;">
                                                    {!! nl2br(e($announcement->content)) !!}
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light py-2 px-4">
                                                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="{{ strtolower((string) Auth::user()->role) === 'admin' ? 6 : 5 }}" class="text-center py-5 text-muted">
                                        <i class="ti ti-speakerphone-off fs-1 d-block mb-2 opacity-50"></i>
                                        <h6 class="fw-bold text-secondary mb-1">Belum ada pengumuman</h6>
                                        <span class="small">Tidak ada data pengumuman yang tersedia.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($announcements->hasPages())
                    <div class="dt-pagination-wrapper">
                        {{ $announcements->links('vendor.pagination.custom') }}
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
    function openAnnouncementModal(event, modalId) {
        if (event && event.target && event.target.closest('a, button, form, input, .action-btn, .btn, .no-modal-trigger, td:last-child')) {
            return;
        }
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            let bsModal = bootstrap.Modal.getInstance(modalEl);
            if (!bsModal) {
                bsModal = new bootstrap.Modal(modalEl);
            }
            bsModal.show();
        }
    }

    function confirmDeleteAnnouncement(event, id, title) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        Swal.fire({
            title: 'Yakin hapus?',
            html: `<p>Pengumuman <b>"${title}"</b> akan dihapus secara permanen.</p>`,
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

    document.addEventListener('DOMContentLoaded', function () {
        const detailModals = document.querySelectorAll('.announcement-detail-modal');

        detailModals.forEach(function (modalEl) {
            modalEl.addEventListener('show.bs.modal', function () {
                const readUrl = modalEl.dataset.readUrl;
                const statusBadgeId = modalEl.dataset.statusBadgeId;
                const rowId = modalEl.dataset.rowId;

                if (!readUrl) return;

                fetch(readUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        const statusBadge = document.getElementById(statusBadgeId);
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-label-secondary';
                            statusBadge.textContent = 'DIBACA';
                        }

                        const tableRow = document.getElementById(rowId);
                        if (tableRow) {
                            tableRow.classList.remove('table-warning-subtle');
                        }

                        const topbarBadge = document.getElementById('announcementUnreadBadge');
                        const topbarCount = document.getElementById('announcementUnreadCount');
                        const dropdownBadge = document.getElementById('announcementUnreadDropdownCount');

                        if (typeof data.unread_count !== 'undefined') {
                            const newCount = data.unread_count;
                            if (topbarBadge) {
                                if (newCount > 0) {
                                    topbarBadge.textContent = newCount > 99 ? '99+' : newCount;
                                    topbarBadge.classList.remove('d-none');
                                } else {
                                    topbarBadge.classList.add('d-none');
                                }
                            }
                            if (topbarCount) topbarCount.textContent = newCount;
                            if (dropdownBadge) dropdownBadge.textContent = newCount;
                        }
                    }
                })
                .catch(function (err) {
                    console.error('Gagal memperbarui status baca pengumuman:', err);
                });
            });
        });
    });
</script>
@endsection
