@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Alert --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="ti ti-check me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Clusters</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Kelola data master cluster organisasi.</p>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="card">
            <div class="card-body">
                <x-dt-toolbar :searchFormAction="route('master_data.clusters.index')" searchPlaceholder="Cari cluster...">
                    <x-slot:actions>
                        <a href="{{ route('master_data.clusters.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Tambah Cluster
                        </a>
                    </x-slot:actions>
                </x-dt-toolbar>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">NO</th>
                                <th>NAMA CLUSTER</th>
                                <th class="text-center" style="width: 120px;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clusters as $index => $item)
                            <tr>
                                <td>{{ $clusters->firstItem() + $index }}</td>
                                <td><span class="fw-semibold">{{ $item->name }}</span></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <x-action-button action="edit" :href="route('master_data.clusters.edit', $item->id)" title="Edit Cluster" />
                                        <form id="delete-form-{{ $item->id }}" action="{{ route('master_data.clusters.destroy', $item->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <x-action-button type="button" action="delete" onclick="confirmDeleteCluster('{{ $item->id }}', '{{ addslashes($item->name) }}')" title="Hapus Cluster" />
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">Belum ada data Cluster.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($clusters->isNotEmpty())
                    <div class="mt-4">
                        {{ $clusters->links('vendor.pagination.custom') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDeleteCluster(id, name) {
        apsConfirmDelete({
            title: 'Hapus Cluster?',
            text: `Cluster ${name} akan dihapus dari sistem.`,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            formId: 'delete-form-' + id
        });
    }
</script>
@endpush
