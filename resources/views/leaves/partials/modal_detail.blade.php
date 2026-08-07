<div class="modal fade" id="leaveDetailModal{{ $leave->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p><strong>Nama:</strong> {{ $leave->user->fullname ?? 'N/A' }}</p>
                <p><strong>Alasan:</strong> {{ $leave->reason }}</p>
                @if($leave->manager_comment)
                <p><strong>Catatan:</strong> {{ $leave->manager_comment }}</p>
                @endif

                @php
                    $attachment = $leave->attachment_path ?: $leave->document;
                    $attachmentUrl = $attachment ? asset('storage/' . $attachment) : null;
                    $ext = $attachment ? strtolower(pathinfo($attachment, PATHINFO_EXTENSION)) : '';
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                @endphp

                @if($attachmentUrl)
                <div class="mt-3 mb-3 p-3 border rounded bg-light">
                    <strong class="d-block mb-2 text-dark">
                        <i class="ti ti-paperclip me-1"></i>Lampiran Dokumen:
                    </strong>
                    @if($isImage)
                        <div class="text-center p-2 bg-white border rounded mb-2">
                            <a href="{{ $attachmentUrl }}" target="_blank" title="Klik untuk memperbesar">
                                <img src="{{ $attachmentUrl }}" alt="Lampiran {{ $leave->user->fullname ?? '' }}" class="img-fluid rounded" style="max-height: 250px; width: 100%; object-fit: contain;">
                            </a>
                        </div>
                        <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="ti ti-external-link me-1"></i> Buka Gambar Ukuran Penuh
                        </a>
                    @else
                        <div class="d-flex align-items-center justify-content-between p-2 bg-white border rounded">
                            <div class="d-flex align-items-center overflow-hidden me-2">
                                <i class="ti ti-file-text fs-3 text-primary me-2 flex-shrink-0"></i>
                                <div class="text-truncate">
                                    <div class="fw-semibold text-truncate">{{ basename($attachment) }}</div>
                                    <small class="text-muted">Dokumen Lampiran ({{ strtoupper($ext ?: 'FILE') }})</small>
                                </div>
                            </div>
                            <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-sm btn-primary flex-shrink-0">
                                <i class="ti ti-download me-1"></i> Lihat / Unduh
                            </a>
                        </div>
                    @endif
                </div>
                @endif

                <hr>

                <p>
                    <strong>Status Saat Ini:</strong>
                    <span class="status-badge {{ $statusConfig['class'] }}">
                        {{ $statusConfig['text'] }}
                    </span>
                </p>

                @if($leave->pic_approved_at)
                <p>
                    <strong>Persetujuan PIC:</strong>
                    Disetujui oleh {{ $leave->picApprover->fullname ?? 'N/A' }}
                    ({{ \Carbon\Carbon::parse($leave->pic_approved_at)->format('d M Y H:i') }})
                </p>
                @endif

                @if($leave->status == 'approved')
                @php
                    $isSelfApprovedFallback = empty($leave->approved_by);
                    $hoApproverName = $leave->hoApprover->fullname
                        ?? ($isSelfApprovedFallback ? ($leave->user->fullname ?? 'N/A') : 'N/A');
                    $hoApprovedAt = $leave->approved_at
                        ?: ($isSelfApprovedFallback ? ($leave->updated_at ?: $leave->created_at) : null);
                @endphp
                <p>
                    <strong>Persetujuan HO:</strong>
                    Disetujui oleh {{ $hoApproverName }}
                    @if($hoApprovedAt)
                        ({{ \Carbon\Carbon::parse($hoApprovedAt)->format('d M Y H:i') }})
                    @endif
                </p>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>
