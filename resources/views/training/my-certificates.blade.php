@extends('layout.admin')

@section('title', 'Sertifikat Training Saya')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Sertifikat Training Saya</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Pantau masa berlaku sertifikat dan riwayat training Anda.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Sertifikat Saya</li>
                </ol>
            </nav>
        </div>

        <div class="card">
            <div class="card-body">
                <x-dt-toolbar searchPlaceholder="Cari sertifikat...">
                    <x-slot:actions>
                        <a href="{{ route('training.certificates.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Tambah Sertifikat
                        </a>
                    </x-slot:actions>
                </x-dt-toolbar>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Sertifikat</th>
                                <th>Masa Berlaku Awal</th>
                                <th>Masa Berlaku Akhir</th>
                                <th>Status</th>
                                <th class="text-center">File</th>
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
                                    <td>
                                        <div class="fw-bold text-primary">{{ $certificate->certificate_name }}</div>
                                        @if ($certificate->certificate_type)
                                            <span class="badge bg-label-info font-monospace mt-1" style="font-size: 0.68rem;">{{ $certificate->certificate_type }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $certificate->start_date->format('d M Y') }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $certificate->end_date->format('d M Y') }}</div>
                                    </td>
                                    <td>
                                        @if ($certificate->is_expired)
                                            <span class="badge bg-danger">Kadaluarsa</span>
                                            <div class="small text-danger mt-1">
                                                {{ $certificate->end_date->diffForHumans(now(), true) }} lalu
                                            </div>
                                        @elseif ($certificate->is_expiring_soon)
                                            <span class="badge bg-warning text-dark">Mendekati Expired</span>
                                            <div class="small text-warning mt-1">
                                                Sisa {{ $certificate->remaining_days }} hari
                                            </div>
                                        @else
                                            <span class="badge bg-success">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($certificate->certificate_file)
                                            <a href="{{ Storage::url($certificate->certificate_file) }}" target="_blank" 
                                               class="btn btn-sm btn-outline-primary" title="Lihat File">
                                                <i class='bx bx-file me-1'></i>Lihat
                                            </a>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bx bx-certification d-block fs-1 mb-2 opacity-50"></i>
                                        <h6 class="fw-bold mb-1 text-secondary">Belum Ada Sertifikat</h6>
                                        <p class="mb-0 text-muted small">Anda belum memiliki data sertifikat training yang tercatat.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($certificates->isNotEmpty())
                    <div class="mt-4">
                        {{ $certificates->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection