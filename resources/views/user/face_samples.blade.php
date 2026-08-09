@extends('layout.admin')

@section('title', 'Foto Wajah - ' . $user->fullname)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">

        {{-- Card Management Foto Wajah --}}
        <div class="card mb-4">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Foto Referensi Wajah (Face Recognition)</h5>
                    <p class="text-muted mb-0 small">Karyawan: <strong>{{ $user->fullname }}</strong> (NIP: {{ $user->id }})</p>
                </div>
                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-arrow-back me-1"></i> Kembali ke Edit User
                </a>
            </div>

            <div class="card-body pt-4">
                {{-- Status Banner --}}
                @if($isComplete)
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="bx bx-check-shield fs-3 me-2"></i>
                        <div>
                            <strong>Status: Terdaftar Lengkap (3 Posisi Foto Ready)</strong>
                            <div class="small">Verifikasi wajah (face recognition) sudah dapat berjalan untuk karyawan ini saat absensi.</div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                        <i class="bx bx-error-circle fs-3 me-2"></i>
                        <div>
                            <strong>Status: Belum Lengkap</strong>
                            <div class="small">Wajib mengupload/mengambil 3 foto posisi wajah (Depan, Samping Kanan, Samping Kiri) agar verifikasi presisi.</div>
                        </div>
                    </div>
                @endif

                {{-- Grid 3 Foto --}}
                <div class="row g-4 mb-4">
                    @php
                        $positions = [
                            'front' => ['title' => 'Foto Wajah Depan', 'icon' => 'bx-user', 'desc' => 'Tatap lurus ke kamera, wajah tegak lurus.'],
                            'right' => ['title' => 'Foto Samping Kanan', 'icon' => 'bx-right-arrow-alt', 'desc' => 'Kepala miring ke kanan ~30 - 45 derajat.'],
                            'left'  => ['title' => 'Foto Samping Kiri', 'icon' => 'bx-left-arrow-alt', 'desc' => 'Kepala miring ke kiri ~30 - 45 derajat.']
                        ];
                    @endphp

                    @foreach($positions as $posKey => $posInfo)
                        @php
                            $hasPhoto = $status[$posKey] ?? false;
                            $photoUrl = $photos[$posKey] ?? null;
                        @endphp

                        <div class="col-md-4">
                            <div class="card h-100 border {{ $hasPhoto ? 'border-success' : 'border-dashed' }}">
                                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold small text-uppercase">
                                        <i class="bx {{ $posInfo['icon'] }} me-1"></i> {{ $posInfo['title'] }}
                                    </span>
                                    @if($hasPhoto)
                                        <span class="badge bg-success">Terisi</span>
                                    @else
                                        <span class="badge bg-secondary">Kosong</span>
                                    @endif
                                </div>
                                <div class="card-body text-center p-3">
                                    <div class="photo-preview-box bg-dark rounded mb-3 overflow-hidden d-flex align-items-center justify-content-center" style="height: 220px; position: relative;">
                                        @if($hasPhoto)
                                            <img src="{{ $photoUrl }}" alt="{{ $posInfo['title'] }}" class="w-100 h-100" style="object-fit: cover;">
                                        @else
                                            <div class="text-muted">
                                                <i class="bx bx-camera fs-1 opacity-50 d-block mb-1"></i>
                                                <small>Belum ada foto</small>
                                            </div>
                                        @endif
                                    </div>

                                    <p class="small text-muted mb-3" style="min-height: 38px;">{{ $posInfo['desc'] }}</p>

                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="openCameraModal('{{ $posKey }}', '{{ $posInfo['title'] }}')">
                                            <i class="bx bx-camera me-1"></i> Ambil Foto
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="triggerFileUpload('{{ $posKey }}')" title="Upload dari File">
                                            <i class="bx bx-upload"></i>
                                        </button>
                                    </div>

                                    {{-- Hidden file upload form per position --}}
                                    <form id="fileForm-{{ $posKey }}" action="{{ route('users.face-samples.store-file', $user->id) }}" method="POST" enctype="multipart/form-data" class="d-none">
                                        @csrf
                                        <input type="hidden" name="position" value="{{ $posKey }}">
                                        <input type="file" name="photo" id="fileInput-{{ $posKey }}" accept="image/*" onchange="document.getElementById('fileForm-{{ $posKey }}').submit()">
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Action Delete All --}}
                @if($isComplete || array_filter($status))
                    <div class="border-top pt-3 text-end">
                        <form id="delete-all-faces-form" action="{{ route('users.face-samples.destroy', $user->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDeleteAllFaces()">
                                <i class="bx bx-trash me-1"></i> Hapus Semua Foto Referensi
                            </button>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

{{-- Modal Kamera --}}
<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="cameraModalTitle">Ambil Foto Wajah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="stopCamera()"></button>
            </div>
            <div class="modal-body text-center p-3">
                <div class="position-relative bg-dark rounded overflow-hidden mb-3" style="min-height: 280px;">
                    <video id="modalVideo" autoplay playsinline muted class="w-100 h-100" style="object-fit: cover; max-height: 350px; transform: scaleX(-1);"></video>
                    <canvas id="modalCanvas" class="d-none"></canvas>
                </div>
                <form id="cameraForm" action="{{ route('users.face-samples.store', $user->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="position" id="modalPosition">
                    <input type="hidden" name="photo" id="modalPhotoBase64">
                    <button type="button" class="btn btn-success btn-lg w-100" id="btnSnap" onclick="snapAndSubmit()">
                        <i class="bx bx-aperture me-1"></i> Ambil & Simpan Foto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let mediaStream = null;

    function triggerFileUpload(posKey) {
        document.getElementById('fileInput-' + posKey).click();
    }

    function openCameraModal(posKey, title) {
        document.getElementById('modalPosition').value = posKey;
        document.getElementById('cameraModalTitle').textContent = 'Ambil ' + title;

        const modal = new bootstrap.Modal(document.getElementById('cameraModal'));
        modal.show();

        navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' } })
            .then(stream => {
                mediaStream = stream;
                const video = document.getElementById('modalVideo');
                video.srcObject = stream;
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Akses Kamera',
                    text: 'Tidak dapat mengakses kamera perangkat: ' + err.message
                });
            });
    }

    function stopCamera() {
        if (mediaStream) {
            mediaStream.getTracks().forEach(track => track.stop());
            mediaStream = null;
        }
    }

    document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function () {
        stopCamera();
    });

    function snapAndSubmit() {
        const video = document.getElementById('modalVideo');
        const canvas = document.getElementById('modalCanvas');
        const context = canvas.getContext('2d');

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;

        // Flip horizontal agar tidak cermin saat simpan
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
        document.getElementById('modalPhotoBase64').value = dataUrl;

        stopCamera();
        document.getElementById('cameraForm').submit();
    }

    function confirmDeleteAllFaces() {
        if (typeof apsConfirmDelete === 'function') {
            apsConfirmDelete({
                title: 'Hapus Semua Foto Wajah?',
                text: 'Foto referensi untuk verifikasi wajah karyawan ini akan dihapus permanen.',
                confirmButtonText: 'Ya, Hapus Semua',
                cancelButtonText: 'Batal',
                formId: 'delete-all-faces-form'
            });
        } else {
            Swal.fire({
                title: 'Hapus Semua Foto Wajah?',
                text: 'Foto referensi untuk verifikasi wajah karyawan ini akan dihapus permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus Semua',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-all-faces-form').submit();
                }
            });
        }
    }
</script>
@endpush
