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
                <h4 class="fw-bold mb-1">Pengaturan Master Data</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Kelola data master Job Title, Unit, dan Sub Unit karyawan.</p>
            </div>
        </div>

        {{-- Nav Tabs --}}
        <div class="nav-align-top mb-4">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $tab == 'job-titles' ? 'active' : '' }}" href="{{ route('master_data.index', ['tab' => 'job-titles']) }}">
                        <i class="ti ti-briefcase me-1"></i> Job Titles ({{ count($jobTitles) }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab == 'units' ? 'active' : '' }}" href="{{ route('master_data.index', ['tab' => 'units']) }}">
                        <i class="ti ti-building me-1"></i> Units ({{ count($units) }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab == 'sub-units' ? 'active' : '' }}" href="{{ route('master_data.index', ['tab' => 'sub-units']) }}">
                        <i class="ti ti-hierarchy-2 me-1"></i> Sub Units ({{ count($subUnits) }})
                    </a>
                </li>
            </ul>

            <div class="tab-content border-0 p-0 pt-3">
                {{-- TAB 1: JOB TITLES --}}
                @if($tab == 'job-titles')
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">Daftar Job Titles</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addJobTitleModal">
                            <i class="ti ti-plus me-1"></i> Tambah Job Title
                        </button>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">NO</th>
                                    <th>NAMA JOB TITLE</th>
                                    <th class="text-center" style="width: 120px;">STATUS</th>
                                    <th class="text-center" style="width: 150px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($jobTitles as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="fw-bold text-dark">{{ $item->name }}</span></td>
                                    <td class="text-center">
                                        @if($item->is_active)
                                            <span class="badge bg-label-success">AKTIFF</span>
                                        @else
                                            <span class="badge bg-label-secondary">NONAKTIF</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-icon btn-sm btn-outline-warning me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editJobTitleModal{{ $item->id }}">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <form action="{{ route('master_data.job_titles.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus Job Title ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-outline-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="editJobTitleModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('master_data.job_titles.update', $item->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Job Title</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Nama Job Title <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $item->name }}" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada data Job Title.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Add Modal Job Title --}}
                <div class="modal fade" id="addJobTitleModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('master_data.job_titles.store') }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Tambah Job Title</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Job Title <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Contoh: PASSENGER HANDLING" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                {{-- TAB 2: UNITS --}}
                @if($tab == 'units')
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">Daftar Units</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUnitModal">
                            <i class="ti ti-plus me-1"></i> Tambah Unit
                        </button>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">NO</th>
                                    <th>NAMA UNIT</th>
                                    <th class="text-center" style="width: 120px;">STATUS</th>
                                    <th class="text-center" style="width: 150px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($units as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="fw-bold text-dark">{{ $item->name }}</span></td>
                                    <td class="text-center">
                                        @if($item->is_active)
                                            <span class="badge bg-label-success">AKTIF</span>
                                        @else
                                            <span class="badge bg-label-secondary">NONAKTIF</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-icon btn-sm btn-outline-warning me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editUnitModal{{ $item->id }}">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <form action="{{ route('master_data.units.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus Unit ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-outline-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="editUnitModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('master_data.units.update', $item->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Unit</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Nama Unit <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $item->name }}" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada data Unit.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Add Modal Unit --}}
                <div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('master_data.units.store') }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Tambah Unit</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Unit <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Contoh: FLIGHT OPERATION" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif

                {{-- TAB 3: SUB UNITS --}}
                @if($tab == 'sub-units')
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">Daftar Sub Units</h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubUnitModal">
                            <i class="ti ti-plus me-1"></i> Tambah Sub Unit
                        </button>
                    </div>
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;">NO</th>
                                    <th>NAMA SUB UNIT</th>
                                    <th class="text-center" style="width: 120px;">STATUS</th>
                                    <th class="text-center" style="width: 150px;">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subUnits as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="fw-bold text-dark">{{ $item->name }}</span></td>
                                    <td class="text-center">
                                        @if($item->is_active)
                                            <span class="badge bg-label-success">AKTIF</span>
                                        @else
                                            <span class="badge bg-label-secondary">NONAKTIF</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-icon btn-sm btn-outline-warning me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editSubUnitModal{{ $item->id }}">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <form action="{{ route('master_data.sub_units.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus Sub Unit ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-sm btn-outline-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="editSubUnitModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('master_data.sub_units.update', $item->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Sub Unit</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Nama Sub Unit <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $item->name }}" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada data Sub Unit.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Add Modal Sub Unit --}}
                <div class="modal fade" id="addSubUnitModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('master_data.sub_units.store') }}" method="POST">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Tambah Sub Unit</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nama Sub Unit <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="Contoh: PORTER APRON" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
