@extends('layout.admin')

@section('title', 'Manajemen Training & Sertifikat')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Manajemen Training & Sertifikat</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Monitoring validitas sertifikat dan riwayat training karyawan.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Training</li>
                </ol>
            </nav>
        </div>

        <div class="card">
            <div class="card-body">
                <x-dt-toolbar :searchFormAction="route('admin.training.certificates.index')" searchPlaceholder="Cari NIP, Nama, atau Sertifikat...">
                    <x-slot name="actions">
                        <a href="{{ route('admin.training.certificates.create') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-plus me-1"></i>Tambah Sertifikat
                        </a>
                    </x-slot>
                </x-dt-toolbar>

                {{-- Tabel --}}
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>NIP</th>
                                <th>Nama Staff</th>
                                <th>Nama Sertifikat</th>
                                <th>Tipe Sertifikat</th>
                                <th>Masa Mulai</th>
                                <th>Masa Berlaku</th>
                                <th>Status</th>
                                <th>Sisa Hari</th>
                                <th class="text-center">File</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($certificates as $certificate)
                                @php
                                    $rowClass = '';
                                    if ($certificate->is_expired) {
                                        $rowClass = 'row-critical';
                                    } elseif ($certificate->is_expiring_soon) {
                                        $rowClass = 'row-warning';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td><strong>{{ $certificate->user_id ?? 'N/A' }}</strong></td>
                                    <td>{{ $certificate->fullname ?? 'N/A' }}</td>
                                    <td><strong>{{ $certificate->certificate_name }}</strong></td>
                                    <td>
                                        @if ($certificate->certificate_type)
                                            <span class="badge bg-label-info font-monospace" style="font-size: 0.68rem;">{{ $certificate->certificate_type }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $certificate->start_date->format('d M Y') }}</td>
                                    <td class="fw-bold">{{ $certificate->end_date->format('d M Y') }}</td>
                                    <td>
                                        @if ($certificate->is_expired)
                                            <span class="badge bg-danger">Kadaluarsa</span>
                                        @elseif ($certificate->is_expiring_soon)
                                            <span class="badge bg-warning text-dark">Mendekati Expired</span>
                                        @else
                                            <span class="badge bg-success">Aktif</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($certificate->is_expired)
                                            <span class="text-danger small fw-semibold">{{ $certificate->end_date->diffForHumans(now(), true) }} lalu</span>
                                        @elseif ($certificate->is_expiring_soon)
                                            <span class="text-warning small fw-semibold">Sisa {{ $certificate->remaining_days }} hari</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($certificate->certificate_file)
                                            <x-action-button action="view" icon="ti ti-file-text" :href="Storage::url($certificate->certificate_file)" target="_blank" title="Lihat File" />
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <x-action-button action="edit" :href="route('admin.training.certificates.edit', $certificate->id)" title="Edit" />
                                            <form action="{{ route('admin.training.certificates.destroy', $certificate->id) }}" method="POST" class="d-inline" id="delete-form-{{ $certificate->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <x-action-button type="button" action="delete" title="Hapus" onclick="confirmDeleteCertificate('{{ $certificate->id }}')" />
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="ti ti-certificate d-block"></i>
                                            <p>Tidak ada data sertifikat training.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $certificates->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function confirmDeleteCertificate(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data sertifikat yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            iconColor: '#dc2626',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: { confirmButton: 'btn-danger' },
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }

    @if (session('success'))
        Swal.fire({
            title: 'Berhasil!',
            text: "{{ session('success') }}",
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    @endif
</script>
@endsection
