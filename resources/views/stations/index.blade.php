@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Manajemen Station</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Daftar dan kontrol status operasional station.</p>
            </div>
            @if(Auth::user()->canAccess('station', 'create'))
            <a href="{{ route('stations.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus"></i> Buka Station Baru
            </a>
            @endif
        </div>

        <div class="card">
            <div class="card-body">
                <x-dt-toolbar :searchFormAction="route('stations.index')" searchPlaceholder="Cari station..." />
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Station</th>
                                <th>Status</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Radius (m)</th>
                                <th>Aksi Kontrol</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stations as $st)
                            <tr>
                                <td><strong>{{ $st->code }}</strong></td>
                                <td>{{ $st->name }}</td>
                                <td>
                                    @if($st->is_active)
                                    <span class="badge bg-label-success">Operasional</span>
                                    @else
                                    <span class="badge bg-label-danger">Dibekukan</span>
                                    @endif
                                </td>
                                <td>{{ $st->latitude }}</td>
                                <td>{{ $st->longitude }}</td>
                                <td>{{ $st->radius }} m</td>
                                <td>
                                    <form action="{{ route('stations.toggle', $st->code) }}" method="POST">
                                        @csrf

                                        <div class="form-check form-switch d-flex align-items-center">
                                            <input class="form-check-input"
                                                type="checkbox"
                                                role="switch"
                                                onchange="this.form.submit()"
                                                {{ $st->is_active ? 'checked' : '' }}>

                                            <label class="form-check-label ms-2">
                                                {{ $st->is_active ? 'Matikan' : 'Hidupkan' }}
                                            </label>
                                        </div>
                                    </form>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                    @if(Auth::user()->canAccess('station', 'edit'))
                                    <x-action-button action="edit" :href="route('stations.edit', $st->code)" title="Edit Station" />
                                    @endif

                                    @if(Auth::user()->canAccess('station', 'delete'))
                                    <form action="{{ route('stations.destroy', $st->code) }}"
                                        method="POST"
                                        class="d-inline"
                                        id="delete-form-{{ $st->code }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-action-button type="button" action="delete" onclick="confirmDeleteShift('{{ $st->code }}', '{{ $st->code }}')" title="Hapus Station" />
                                    </form>
                                    @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="dt-pagination-wrapper">
                    {{ $stations->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

<script>
    function confirmDeleteShift(id, code) {
        apsConfirmDelete({
            title: 'Hapus Station?',
            text: `Station dengan kode ${code} akan dihapus dari sistem.`,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            formId: 'delete-form-' + id
        });
    }
</script>
@endsection
