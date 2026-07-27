@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">
                <i class="ti ti-speakerphone me-2 text-primary"></i>Detail Pengumuman
            </h4>
            <p class="text-muted small mb-0">Informasi lengkap pengumuman resmi operasional</p>
        </div>
        <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="ti ti-arrow-left me-1"></i>Kembali
        </a>
    </div>

    @php
        $targetStations = $announcement->target_stations ?? ['ALL'];
        $isAllStation = in_array('ALL', $targetStations, true);
    @endphp

    <div class="row">
        <div class="col-12 col-lg-10 mx-auto">
            <div class="card border-0 shadow-sm border-start border-4 border-primary">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 pb-3 mb-4 border-bottom">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h3 class="fw-bold text-dark mb-0">{{ $announcement->title }}</h3>
                                <span class="badge bg-label-info rounded-pill px-3 py-1">
                                    <i class="ti ti-map-pin me-1"></i>
                                    {{ $isAllStation ? 'Semua Station' : implode(', ', $targetStations) }}
                                </span>
                            </div>
                            <div class="text-muted small d-flex align-items-center gap-3">
                                <span><i class="ti ti-user me-1 text-primary"></i>{{ $announcement->author ? $announcement->author->fullname : 'Admin' }}</span>
                                <span><i class="ti ti-calendar me-1 text-primary"></i>{{ $announcement->created_at->format('d M Y H:i') }} ({{ $announcement->created_at->diffForHumans() }})</span>
                            </div>
                        </div>
                    </div>

                    <div class="announcement-content text-dark mb-4" style="font-size: 1rem; line-height: 1.8; white-space: pre-line;">
                        {!! nl2br(e($announcement->content)) !!}
                    </div>

                    <div class="pt-4 border-top d-flex justify-content-between align-items-center">
                        <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="ti ti-arrow-left me-1"></i>Kembali ke Daftar
                        </a>
                        @if(strtolower((string) Auth::user()->role) === 'admin')
                            <a href="{{ route('announcements.edit', $announcement->id) }}" class="btn btn-warning rounded-pill px-4">
                                <i class="ti ti-pencil me-1"></i>Edit Pengumuman
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
