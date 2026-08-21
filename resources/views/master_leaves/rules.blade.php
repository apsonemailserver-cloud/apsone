@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Skala Masa Kerja: {{ $leaveType->name }}</h4>
            <p class="text-muted mb-0" style="font-size:0.875rem;">
                Kelola batasan kuota cuti berdasarkan masa kerja karyawan. Kuota Default: <strong>{{ $leaveType->is_unlimited ? 'Tidak Terbatas' : $leaveType->default_quota . ' hari' }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('master_leaves.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke Master Cuti
            </a>
            @if(Auth::user()->isAdmin() || Auth::user()->canAccess('master_leave', 'edit'))
            <a href="{{ route('master_leaves.rules.create', $leaveType->id) }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i> Tambah Aturan Skala
            </a>
            @endif
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Masa Kerja (Tahun)</th>
                            <th>Kuota Cuti (Hari)</th>
                            <th>Keterangan</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($rules->sortBy('min_tenure_years') as $rule)
                        <tr>
                            <td>
                                <strong>
                                    @if(is_null($rule->max_tenure_years))
                                        &ge; {{ $rule->min_tenure_years }} tahun
                                    @else
                                        {{ $rule->min_tenure_years }} - {{ $rule->max_tenure_years }} tahun
                                    @endif
                                </strong>
                            </td>
                            <td>
                                <span class="badge bg-label-primary fs-6">{{ $rule->quota_days }} hari</span>
                            </td>
                            <td>{{ $rule->description ?: '-' }}</td>
                            <td class="text-center">
                                @if(Auth::user()->isAdmin() || Auth::user()->canAccess('master_leave', 'edit'))
                                <div class="d-flex justify-content-center gap-1">
                                    <x-action-button action="edit" :href="route('master_leaves.rules.edit', $rule->id)" title="Edit Aturan" />
                                    <form id="delete-form-{{ $rule->id }}" action="{{ route('master_leaves.rules.destroy', $rule->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <x-action-button type="button" action="delete" onclick="confirmDeleteRule('{{ $rule->id }}')" title="Hapus Aturan" />
                                    </form>
                                </div>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                Belum ada aturan skala masa kerja khusus. Kuota default ({{ $leaveType->default_quota }} hari) berlaku untuk seluruh karyawan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.confirmDeleteRule = function(id) {
        apsConfirmDelete({
            title: 'Hapus Aturan Masa Kerja?',
            text: 'Aturan masa kerja ini akan dihapus dari sistem.',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            formId: 'delete-form-' + id
        });
    };
</script>
@endsection
