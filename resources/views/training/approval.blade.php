@extends('layout.admin')
@section('title', 'Persetujuan Training Staff')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Persetujuan Sertifikat Training</h4>
            <p class="text-muted mb-0" style="font-size:0.875rem;">Verifikasi dan setujui berkas sertifikasi kompetensi staff.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <x-dt-toolbar :searchFormAction="route('training.approval')" searchPlaceholder="Cari nama staff / sertifikat..." />

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>NIP</th>
                            <th>Nama Staff</th>
                            <th>Nama Sertifikat</th>
                            <th>Tipe</th>
                            <th>Masa Mulai</th>
                            <th>Masa Berlaku</th>
                            <th>File</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingCertificates as $cert)
                        <tr>
                            <td><strong>{{ $cert->user->id ?? '-' }}</strong></td>
                            <td>{{ $cert->user->fullname ?? '-' }}</td>
                            <td>{{ $cert->certificate_name }}</td>
                            <td><span class="badge bg-label-info">{{ $cert->certificate_type ?? 'Umum' }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($cert->start_date)->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($cert->end_date)->format('d/m/Y') }}</td>
                            <td>
                                @if($cert->certificate_file)
                                    <x-action-button action="download" :href="asset('storage/'.$cert->certificate_file)" target="_blank" title="Unduh Sertifikat" />
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td><span class="badge bg-label-warning">Pending</span></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <form action="{{ route('training.approve', $cert->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <x-action-button type="button" action="edit" onclick="this.form.submit()" title="Setujui (Approve)" />
                                    </form>

                                    <form id="reject-form-{{ $cert->id }}" action="{{ route('training.reject', $cert->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <x-action-button type="button" action="delete" onclick="confirmReject('{{ $cert->id }}')" title="Tolak (Reject)" />
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="bx bx-check-circle fs-1 text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">Tidak ada pengajuan sertifikat training yang perlu diproses.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="dt-pagination-wrapper">
                {{ $pendingCertificates->links('vendor.pagination.custom') }}
            </div>
        </div>
    </div>
</div>

<script>
    function confirmReject(id) {
        Swal.fire({
            title: 'Tolak Pengajuan?',
            text: "Masukkan alasan penolakan sertifikat training:",
            input: 'text',
            inputPlaceholder: 'Contoh: Masa berlaku dokumen sudah expired...',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6e7881',
            confirmButtonText: 'Tolak',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan penolakan wajib diisi!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.getElementById('reject-form-' + id);
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reason';
                input.value = result.value;
                form.appendChild(input);
                form.submit();
            }
        });
    }
</script>
@endsection
