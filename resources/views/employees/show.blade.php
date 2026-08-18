@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1"><i class="ti ti-user me-2"></i>Detail Karyawan</h4>
            <p class="text-muted mb-0">Informasi detail biodata, jabatan, kontrak, dan dokumen karyawan.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-warning">
                <i class="ti ti-edit me-1"></i> Edit Data
            </a>
            <a href="{{ route('employees.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Header Info Karyawan -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-xl bg-label-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 24px;">
                                <i class="ti ti-user"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold">{{ $employee->fullname }}</h4>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <span class="badge bg-label-primary">{{ $employee->jobTitle->name ?? '-' }}</span>
                                    <span class="badge bg-label-info">Station: {{ $employee->station ?? '-' }}</span>
                                    <span class="badge bg-label-success">{{ $employee->status ?? 'Employed' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Biodata Utama -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom bg-light py-3">
                    <h5 class="mb-0 text-primary"><i class="ti ti-id me-2"></i>1. Biodata Utama</h5>
                </div>
                <div class="card-body pt-3">
                    <table class="table table-borderless table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 40%;">Nama Lengkap</th>
                                <td>: {{ $employee->fullname }}</td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin</th>
                                <td>: {{ $employee->gender == 'Male' ? 'Laki-laki' : ($employee->gender == 'Female' ? 'Perempuan' : ($employee->gender ?? '-')) }}</td>
                            </tr>
                            <tr>
                                <th>Station</th>
                                <td>: {{ $employee->station ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>No NIK (KTP)</th>
                                <td>: {{ $employee->no_nik ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>No KK</th>
                                <td>: {{ $employee->no_kk ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>No HP / Telepon</th>
                                <td>: {{ $employee->no_hp ?? $employee->phone ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Tempat, Tgl Lahir</th>
                                <td>: {{ $employee->tempat_lahir ?? '-' }}, {{ $employee->tanggal_lahir ? $employee->tanggal_lahir->format('d M Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Pendidikan</th>
                                <td>: {{ $employee->pendidikan ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Alamat</th>
                                <td>: {{ $employee->alamat ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Kota Domisili</th>
                                <td>: {{ $employee->kota_domisili ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. Jabatan & Unit Kerja -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom bg-light py-3">
                    <h5 class="mb-0 text-primary"><i class="ti ti-briefcase me-2"></i>2. Jabatan & Unit Kerja</h5>
                </div>
                <div class="card-body pt-3">
                    <table class="table table-borderless table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 40%;">Job Title</th>
                                <td>: {{ $employee->jobTitle->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Unit</th>
                                <td>: {{ $employee->unit->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Sub Unit</th>
                                <td>: {{ $employee->subUnit->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Cluster</th>
                                <td>: {{ $employee->cluster->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Manager</th>
                                <td>: {{ $employee->manager ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Senior Manager</th>
                                <td>: {{ $employee->senior_manager ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. Kontrak, BPJS & Dokumen -->
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header border-bottom bg-light py-3">
                    <h5 class="mb-0 text-primary"><i class="ti ti-file-certificate me-2"></i>3. Kontrak & Legality</h5>
                </div>
                <div class="card-body pt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%;">Tanggal Join</th>
                                        <td>: {{ $employee->join_date ? $employee->join_date->format('d M Y') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Mulai Kontrak</th>
                                        <td>: {{ $employee->contract_start ? $employee->contract_start->format('d M Y') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Akhir Kontrak</th>
                                        <td>: {{ $employee->contract_end ? $employee->contract_end->format('d M Y') : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Salary (Gaji)</th>
                                        <td>: 
                                            @if($employee->salary)
                                                {{ is_numeric($employee->salary) ? 'Rp ' . number_format((float)$employee->salary, 0, ',', '.') : $employee->salary }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless table-sm">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%;">BPJS Ketenagakerjaan</th>
                                        <td>: {{ $employee->bpjs_tk ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>BPJS Kesehatan</th>
                                        <td>: {{ $employee->bpjs_kesehatan ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>No PAS</th>
                                        <td>: {{ $employee->no_pas ?? '-' }} (Expired: {{ $employee->pas_expired ? $employee->pas_expired->format('d M Y') : '-' }})</td>
                                    </tr>
                                    <tr>
                                        <th>No TIM</th>
                                        <td>: {{ $employee->tim_number ?? '-' }} (Expired: {{ $employee->tim_expired ? $employee->tim_expired->format('d M Y') : '-' }})</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
