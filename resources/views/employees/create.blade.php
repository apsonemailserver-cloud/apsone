@extends('layout.admin')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold py-1 mb-1"><i class="ti ti-user-plus me-2"></i>Tambah Karyawan Baru</h4>
            <p class="text-muted mb-0">Isi form lengkap data biodata, kontrak, jabatan, dan dokumen karyawan.</p>
        </div>
        <a href="{{ route('employees.index') }}" class="btn btn-label-secondary"><i class="ti ti-arrow-left me-1"></i> Kembali</a>
    </div>

    <form action="{{ route('employees.store') }}" method="POST">
        @csrf
        <div class="card mb-4">
            <div class="card-header border-bottom">
                <h5 class="mb-0">1. Biodata Utama</h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" class="form-control" required value="{{ old('fullname') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select name="gender" class="form-select" required>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Station <span class="text-danger">*</span></label>
                        <select name="station" class="form-select" required>
                            @foreach($stations as $st)
                            <option value="{{ $st->code }}" {{ old('station') == $st->code ? 'selected' : '' }}>{{ $st->code }} - {{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No NIK (KTP)</label>
                        <input type="text" name="no_nik" class="form-control" value="{{ old('no_nik') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No KK</label>
                        <input type="text" name="no_kk" class="form-control" value="{{ old('no_kk') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No HP / Telepon</label>
                        <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control" value="{{ old('tempat_lahir') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="{{ old('tanggal_lahir') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pendidikan</label>
                        <input type="text" name="pendidikan" class="form-control" value="{{ old('pendidikan') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2">{{ old('alamat') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <h5 class="mb-0">2. Jabatan & Unit Kerja</h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Job Title <span class="text-danger">*</span></label>
                        <select name="job_title_id" class="form-select" required>
                            @foreach($jobTitles as $jt)
                            <option value="{{ $jt->id }}" {{ old('job_title_id') == $jt->id ? 'selected' : '' }}>{{ $jt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                        <select name="unit_id" class="form-select" required>
                            @foreach($units as $u)
                            <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sub Unit <span class="text-danger">*</span></label>
                        <select name="sub_unit_id" class="form-select" required>
                            @foreach($subUnits as $su)
                            <option value="{{ $su->id }}" {{ old('sub_unit_id') == $su->id ? 'selected' : '' }}>{{ $su->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cluster</label>
                        <select name="cluster_id" class="form-select">
                            <option value="">-- Pilih Cluster --</option>
                            @foreach($clusters as $cl)
                            <option value="{{ $cl->id }}" {{ old('cluster_id') == $cl->id ? 'selected' : '' }}>{{ $cl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header border-bottom">
                <h5 class="mb-0">3. Kontrak & BPJS</h5>
            </div>
            <div class="card-body pt-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Join <span class="text-danger">*</span></label>
                        <input type="date" name="join_date" class="form-control" required value="{{ old('join_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Mulai Kontrak</label>
                        <input type="date" name="contract_start" class="form-control" value="{{ old('contract_start') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Akhir Kontrak</label>
                        <input type="date" name="contract_end" class="form-control" value="{{ old('contract_end') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gaji (Salary)</label>
                        <input type="number" step="0.01" name="salary" class="form-control" value="{{ old('salary') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BPJS Ketenagakerjaan</label>
                        <input type="text" name="bpjs_tk" class="form-control" value="{{ old('bpjs_tk') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BPJS Kesehatan</label>
                        <input type="text" name="bpjs_kesehatan" class="form-control" value="{{ old('bpjs_kesehatan') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Karyawan</label>
                        <input type="text" name="status" class="form-control" placeholder="Employed" value="{{ old('status', 'Employed') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('employees.index') }}" class="btn btn-label-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Simpan Data Karyawan</button>
        </div>
    </form>
</div>
@endsection
