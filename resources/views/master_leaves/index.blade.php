@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Pengaturan Master Cuti & Izin</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Kelola tipe cuti, batasan gender, dan aturan kuota berdasarkan masa kerja karyawan.</p>
            </div>
            <div class="d-flex gap-2">
                @if(Auth::user()->isAdmin() || Auth::user()->canAccess('master_leave', 'create'))
                <a href="{{ route('master_leaves.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus"></i> Tambah Tipe Cuti
                </a>
                @endif
            </div>
        </div>

        {{-- Main Table --}}
        <div class="card">
            <div class="card-body">
                <x-dt-toolbar :searchFormAction="route('master_leaves.index')" searchPlaceholder="Cari tipe cuti..." />

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tipe Cuti</th>
                                <th class="text-center">Gender</th>
                                <th class="text-center">Kuota Default</th>
                                <th class="text-center">Skala Kuota Masa Kerja</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 180px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveTypes as $type)
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark d-block">{{ $type->name }}</span>
                                </td>
                                <td class="text-center">
                                    @if($type->gender_restriction === 'Male')
                                        <span class="badge bg-label-info">Pria (Male)</span>
                                    @elseif($type->gender_restriction === 'Female')
                                        <span class="badge bg-label-danger">Wanita (Female)</span>
                                    @else
                                        <span class="badge bg-label-secondary">Semua (All)</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($type->is_unlimited)
                                        <span class="badge bg-label-success">Tidak Terbatas</span>
                                    @else
                                        <span class="fw-bold text-dark">{{ $type->default_quota }}</span> <small class="text-muted">hari</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($type->rules->count() > 0)
                                        <a href="{{ route('master_leaves.rules.index', $type->id) }}" class="badge bg-label-info text-decoration-none">
                                            <i class="ti ti-list me-1"></i> {{ $type->rules->count() }} Aturan Skala
                                        </a>
                                    @else
                                        <span class="badge bg-label-secondary">Kuota Default</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($type->is_active)
                                        <span class="badge bg-label-success">Aktif</span>
                                    @else
                                        <span class="badge bg-label-secondary">Non-aktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <x-action-button action="view" icon="ti ti-list" :href="route('master_leaves.rules.index', $type->id)" title="Kelola List Skala Kuota Cuti" />

                                        @if(Auth::user()->isAdmin() || Auth::user()->canAccess('master_leave', 'edit'))
                                        <x-action-button action="edit" :href="route('master_leaves.edit', $type->id)" title="Edit Tipe Cuti" />
                                        <form id="delete-form-{{ $type->id }}" action="{{ route('master_leaves.destroy', $type->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <x-action-button type="button" action="delete" onclick="confirmDeleteLeaveType('{{ $type->id }}', '{{ addslashes($type->name) }}')" title="Hapus Tipe Cuti" />
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bx bx-folder-open d-block fs-1 mb-2 opacity-50"></i>
                                    <h6 class="fw-bold mb-1 text-secondary">Belum Ada Tipe Cuti</h6>
                                    <p class="mb-0 text-muted small">Belum ada tipe cuti yang dikonfigurasi.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($leaveTypes->isNotEmpty())
                    <div class="mt-4">
                        {{ $leaveTypes->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDeleteLeaveType(id, name) {
        apsConfirmDelete({
            title: 'Hapus Tipe Cuti?',
            text: `Tipe cuti ${name} akan dihapus dari sistem.`,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            formId: 'delete-form-' + id
        });
    }
</script>
@endpush
