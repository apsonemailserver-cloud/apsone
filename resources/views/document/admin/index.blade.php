@extends('layout.admin')

@section('title', 'Manajemen Dokumen')

@section('styles')
    @include('document.admin.styles')
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y document-admin-page">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Manajemen Dokumen</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Kelola berkas, panduan, dan dokumen operasional perusahaan.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item active">Dokumen</li>
                </ol>
            </nav>
        </div>

        <div class="card">
            <div class="card-body">
                <x-dt-toolbar :searchFormAction="route('admin.documents.index')" searchPlaceholder="Cari nama dokumen, deskripsi, file, atau role...">
                    <x-slot name="actions">
                        <a href="{{ route('document') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-eye me-1"></i>Lihat Halaman
                        </a>
                        <a href="{{ route('admin.documents.create') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-plus me-1"></i>Tambah Dokumen
                        </a>
                    </x-slot>
                </x-dt-toolbar>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Dokumen</th>
                                <th>Role Akses</th>
                                <th>Nama File</th>
                                <th>Dibuat</th>
                                <th>Diupdate</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                @php
                                    $fileExists = $document->file_path
                                        && \Illuminate\Support\Facades\Storage::disk('public')->exists($document->file_path);
                                @endphp
                                <tr>
                                    <td style="min-width: 260px;">
                                        <strong>{{ $document->nama_dokumen }}</strong>
                                        <div class="small text-muted">{{ $document->deskripsi_dokumen }}</div>
                                    </td>
                                    <td style="min-width: 160px;">
                                        @php
                                            $roles = $document->role_access_values;
                                            $isAll = $document->isAllRoleAccess();
                                            $accessClass = $document->access_class;
                                            $visibleRoles = $isAll ? [] : array_slice($roles, 0, 2);
                                            $extraCount = $isAll ? 0 : max(0, count($roles) - 2);
                                        @endphp

                                        @if ($isAll)
                                            <span class="role-chip role-chip--all">
                                                <i class="ti ti-users-group"></i> Semua Role
                                            </span>
                                        @else
                                            <div class="role-chips-wrap">
                                                @foreach ($visibleRoles as $role)
                                                    <span class="role-chip role-chip--{{ $accessClass }}">
                                                        {{ $role }}
                                                    </span>
                                                @endforeach

                                                @if ($extraCount > 0)
                                                    <span class="role-chip role-chip--more"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        data-bs-html="true"
                                                        title="<div class='role-tooltip-list'>{{ implode('<br>', array_map('htmlspecialchars', $roles)) }}</div>">
                                                        +{{ $extraCount }} lainnya
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td style="min-width: 180px;">
                                        <div class="fw-semibold text-truncate" style="max-width: 220px;">
                                            {{ $document->nama_file }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $document->ukuran_file ?? '-' }}
                                            @unless ($fileExists)
                                                <span class="text-danger ms-1">File belum ada</span>
                                            @endunless
                                        </div>
                                    </td>
                                    <td style="min-width: 160px;">
                                        <div class="fw-semibold">{{ optional($document->created_at)->format('d M Y H:i') }}</div>
                                        <div class="small text-muted">{{ $document->creator->fullname ?? $document->created_by ?? '-' }}</div>
                                    </td>
                                    <td style="min-width: 160px;">
                                        <div class="fw-semibold">{{ optional($document->updated_at)->format('d M Y H:i') }}</div>
                                        <div class="small text-muted">{{ $document->updater->fullname ?? $document->updated_by ?? '-' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            @if ($fileExists)
                                                <x-action-button action="download" :href="route('document.download', $document)" title="Unduh" />
                                            @endif
                                            @if (Auth::user()?->role === 'Admin' || Auth::user()->hasPermission('document.edit'))
                                                <x-action-button action="edit" :href="route('admin.documents.edit', $document)" title="Edit" />
                                            @endif
                                            @if (Auth::user()?->role === 'Admin' || Auth::user()->hasPermission('document.delete'))
                                                <form action="{{ route('admin.documents.destroy', $document) }}" method="POST"
                                                    id="delete-document-{{ $document->id }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-action-button type="button" action="delete" onclick="confirmDeleteDocument('{{ $document->id }}')" title="Hapus" />
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="ti ti-file-text d-block"></i>
                                            <p>Tidak ada data dokumen.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $documents->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function confirmDeleteDocument(id) {
            Swal.fire({
                title: 'Hapus dokumen?',
                text: 'Data dan file dokumen akan dihapus dari sistem.',
                icon: 'warning',
                iconColor: '#dc2626',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                customClass: { confirmButton: 'btn-danger' },
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-document-' + id).submit();
                }
            });
        }
    </script>
@endsection
