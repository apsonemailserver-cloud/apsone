@extends('layout.admin')

@section('title', 'Edit Data Karyawan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">

        {{-- Card Form Edit Employee --}}
        <div class="card mb-4">
            <div class="card-header border-bottom">
                <h5 class="mb-0 fw-bold">Edit Data Karyawan: {{ $employee->fullname }}</h5>
            </div>
            <div class="card-body pt-4">

                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <h6 class="alert-heading fw-bold mb-1">Terjadi kesalahan pada input data:</h6>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('employees.update', $employee->id) }}" method="POST" id="editEmployeeForm">
                    @csrf
                    @method('PUT')

                    {{-- Hubungkan dengan Akun User (Opsional) --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold"><i class="ti ti-link me-1"></i> Hubungkan dengan Akun User (Opsional)</label>
                        <select name="user_id" id="user_select" class="form-select">
                            <option value="">-- Tidak Terhubung / Lepaskan Akun User --</option>
                            @if(isset($users))
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" 
                                        data-fullname="{{ $u->fullname }}"
                                        data-gender="{{ $u->gender }}"
                                        data-station="{{ $u->station }}"
                                        data-job_title_id="{{ $u->job_title_id }}"
                                        data-unit_id="{{ $u->unit_id }}"
                                        data-sub_unit_id="{{ $u->sub_unit_id }}"
                                        data-cluster_id="{{ $u->cluster_id }}"
                                        data-manager="{{ $u->manager }}"
                                        data-senior_manager="{{ $u->senior_manager }}"
                                        data-is_qantas="{{ $u->is_qantas ? '1' : '0' }}"
                                        data-join_date="{{ $u->join_date ? \Carbon\Carbon::parse($u->join_date)->format('Y-m-d') : '' }}"
                                        data-salary="{{ $u->salary }}"
                                        {{ (old('user_id', optional($employee->user)->id) == $u->id || $u->employee_id == $employee->id) ? 'selected' : '' }}>
                                        {{ $u->id }} - {{ $u->fullname }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="row">
                        {{-- Kolom Kiri: Biodata & Data Pribadi --}}
                        <div class="col-md-6 border-end-md">
                            <h6 class="fw-bold text-primary mb-3"><i class="ti ti-user me-1"></i> Biodata & Identitas Pribadi</h6>

                            <div class="mb-3">
                                <label class="form-label">NIP</label>
                                <input type="text" class="form-control bg-light" name="id"
                                    value="{{ $employee->id }}" readonly>
                                <small class="text-muted">NIP tidak dapat diubah</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="fullname" id="fullname"
                                    placeholder="Masukkan nama lengkap" value="{{ old('fullname', $employee->fullname) }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" id="gender" class="form-select" required>
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="Male" {{ old('gender', $employee->gender) == 'Male' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Female" {{ old('gender', $employee->gender) == 'Female' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">No NIK (KTP)</label>
                                <input type="text" class="form-control" name="no_nik" id="no_nik"
                                    placeholder="Masukkan nomor NIK KTP" value="{{ old('no_nik', $employee->no_nik) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">No KK</label>
                                <input type="text" class="form-control" name="no_kk" id="no_kk"
                                    placeholder="Masukkan nomor Kartu Keluarga" value="{{ old('no_kk', $employee->no_kk) }}">
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Tempat Lahir</label>
                                    <input type="text" class="form-control" name="tempat_lahir" id="tempat_lahir"
                                        placeholder="Kota lahir" value="{{ old('tempat_lahir', $employee->tempat_lahir) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Tanggal Lahir</label>
                                    <input type="date" class="form-control" name="tanggal_lahir" id="tanggal_lahir"
                                        max="{{ date('Y-m-d') }}"
                                        value="{{ old('tanggal_lahir', $employee->tanggal_lahir ? $employee->tanggal_lahir->format('Y-m-d') : '') }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pendidikan Terakhir</label>
                                <input type="text" class="form-control" name="pendidikan" id="pendidikan"
                                    placeholder="Contoh: SMA / SMK / D3 / S1" value="{{ old('pendidikan', $employee->pendidikan) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">No HP / Telepon</label>
                                <input type="text" class="form-control" name="no_hp" id="no_hp"
                                    placeholder="Contoh: 08123456789" value="{{ old('no_hp', $employee->no_hp ?? $employee->phone) }}">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat Sesuai KTP</label>
                                <textarea class="form-control" name="alamat" id="alamat" rows="2"
                                    placeholder="Masukkan alamat lengkap sesuai KTP">{{ old('alamat', $employee->alamat) }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Domisili Saat Ini</label>
                                <input type="text" class="form-control" name="domisili" id="domisili"
                                    placeholder="Masukkan domisili tempat tinggal saat ini" value="{{ old('domisili', $employee->domisili) }}">
                            </div>
                        </div>

                        {{-- Kolom Kanan: Jabatan, Kontrak & Dokumen --}}
                        <div class="col-md-6 ps-md-4">
                            <h6 class="fw-bold text-primary mb-3"><i class="ti ti-briefcase me-1"></i> Kepegawaian & Penugasan</h6>

                            <div class="mb-3">
                                <label class="form-label">Station <span class="text-danger">*</span></label>
                                <select name="station_id" id="station_id" class="form-select" required>
                                    <option value="">-- Pilih Station --</option>
                                    @foreach ($stations as $station)
                                        <option value="{{ $station->code }}"
                                            {{ old('station_id', old('station', $employee->station_id ?? $employee->station)) == $station->code ? 'selected' : '' }}>
                                            {{ $station->code }} - {{ $station->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Job Title <span class="text-danger">*</span></label>
                                <select name="job_title_id" id="job_title_id" class="form-select" required>
                                    <option value="">-- Pilih Job Title --</option>
                                    @foreach ($jobTitles as $jt)
                                        <option value="{{ $jt->id }}" {{ old('job_title_id', $employee->job_title_id) == $jt->id ? 'selected' : '' }}>
                                            {{ $jt->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select name="unit_id" id="unit_id" class="form-select" required>
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach ($units as $u)
                                        <option value="{{ $u->id }}" {{ old('unit_id', $employee->unit_id) == $u->id ? 'selected' : '' }}>
                                            {{ $u->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sub Unit <span class="text-danger">*</span></label>
                                <select name="sub_unit_id" id="sub_unit_id" class="form-select" required>
                                    <option value="">-- Pilih Sub Unit --</option>
                                    @foreach ($subUnits as $su)
                                        <option value="{{ $su->id }}" data-unit-id="{{ $su->unit_id }}" {{ old('sub_unit_id', $employee->sub_unit_id) == $su->id ? 'selected' : '' }}>
                                            {{ $su->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cluster</label>
                                <select name="cluster_id" id="cluster_id" class="form-select">
                                    <option value="">-- Tidak Ada Cluster --</option>
                                    @foreach ($clusters as $c)
                                        <option value="{{ $c->id }}" {{ old('cluster_id', $employee->cluster_id) == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="join_date" id="join_date"
                                        value="{{ old('join_date', $employee->join_date ? $employee->join_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Gaji Pokok</label>
                                    <input type="number" class="form-control" name="salary" id="salary"
                                        placeholder="Contoh: 5000000" value="{{ old('salary', $employee->salary) }}">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Status Karyawan</label>
                                    <select name="status" class="form-select">
                                        <option value="Employed" {{ old('status', $employee->status ?? 'Employed') == 'Employed' ? 'selected' : '' }}>Employed</option>
                                        <option value="Active" {{ old('status', $employee->status) == 'Active' ? 'selected' : '' }}>Active</option>
                                        <option value="Resigned" {{ old('status', $employee->status) == 'Resigned' ? 'selected' : '' }}>Resigned</option>
                                        <option value="Terminated" {{ old('status', $employee->status) == 'Terminated' ? 'selected' : '' }}>Terminated</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Staf Qantas <span class="text-danger">*</span></label>
                                    <select name="is_qantas" id="is_qantas" class="form-select" required>
                                        <option value="0" {{ old('is_qantas', $employee->is_qantas) == '0' ? 'selected' : '' }}>Tidak</option>
                                        <option value="1" {{ old('is_qantas', $employee->is_qantas) == '1' ? 'selected' : '' }}>Ya</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mt-4 mb-3"><i class="ti ti-file-certificate me-1"></i> Kontrak & Dokumen Bandara</h6>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Awal Kontrak</label>
                                    <input type="date" class="form-control" name="contract_start" id="contract_start"
                                        value="{{ old('contract_start', $employee->contract_start ? $employee->contract_start->format('Y-m-d') : '') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Akhir Kontrak</label>
                                    <input type="date" class="form-control" name="contract_end" id="contract_end"
                                        value="{{ old('contract_end', $employee->contract_end ? $employee->contract_end->format('Y-m-d') : '') }}">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12 mb-2">
                                    <label class="form-label">Nomor PAS Bandara</label>
                                    <input type="text" class="form-control" name="no_pas" id="no_pas"
                                        placeholder="Nomor PAS Bandara" value="{{ old('no_pas', $employee->no_pas) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">PAS Registered</label>
                                    <input type="date" class="form-control" name="pas_registered" id="pas_registered"
                                        value="{{ old('pas_registered', $employee->pas_registered ? $employee->pas_registered->format('Y-m-d') : '') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">PAS Expired</label>
                                    <input type="date" class="form-control" name="pas_expired" id="pas_expired"
                                        value="{{ old('pas_expired', $employee->pas_expired ? $employee->pas_expired->format('Y-m-d') : '') }}">
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">BPJS Ketenagakerjaan</label>
                                    <input type="text" class="form-control" name="bpjs_tk" id="bpjs_tk"
                                        placeholder="Nomor BPJS TK" value="{{ old('bpjs_tk', $employee->bpjs_tk) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">BPJS Kesehatan</label>
                                    <input type="text" class="form-control" name="bpjs_kesehatan" id="bpjs_kesehatan"
                                        placeholder="Nomor BPJS Kesehatan" value="{{ old('bpjs_kesehatan', $employee->bpjs_kesehatan) }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-top mt-3">
                        <x-form-actions :cancelHref="route('employees.index')" submitText="Perbarui Data Karyawan" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const userSelect = document.getElementById('user_select');
    const unitSelect = document.getElementById('unit_id');
    const subUnitSelect = document.getElementById('sub_unit_id');

    // Dynamic Sub Unit based on Unit
    function filterSubUnits() {
        if (!unitSelect || !subUnitSelect) return;
        const selectedUnitId = unitSelect.value;
        const options = subUnitSelect.querySelectorAll('option');
        
        let hasActive = false;
        options.forEach(opt => {
            if (!opt.value) {
                opt.style.display = '';
                return;
            }
            const unitId = opt.getAttribute('data-unit-id');
            if (!selectedUnitId || unitId == selectedUnitId) {
                opt.style.display = '';
                if (opt.selected) hasActive = true;
            } else {
                opt.style.display = 'none';
                if (opt.selected) opt.selected = false;
            }
        });
    }

    if (unitSelect) {
        unitSelect.addEventListener('change', filterSubUnits);
        filterSubUnits();
    }

    // Autofill when user is selected
    if (userSelect) {
        userSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) return;

            const fullname = selectedOpt.getAttribute('data-fullname');
            const gender = selectedOpt.getAttribute('data-gender');
            const station = selectedOpt.getAttribute('data-station');
            const jobTitleId = selectedOpt.getAttribute('data-job_title_id');
            const unitId = selectedOpt.getAttribute('data-unit_id');
            const subUnitId = selectedOpt.getAttribute('data-sub_unit_id');
            const clusterId = selectedOpt.getAttribute('data-cluster_id');
            const isQantas = selectedOpt.getAttribute('data-is_qantas');
            const joinDate = selectedOpt.getAttribute('data-join_date');
            const salary = selectedOpt.getAttribute('data-salary');

            if (fullname) document.getElementById('fullname').value = fullname;
            if (gender) document.getElementById('gender').value = gender;
            if (station) document.getElementById('station_id').value = station;
            if (jobTitleId) document.getElementById('job_title_id').value = jobTitleId;
            if (unitId) {
                document.getElementById('unit_id').value = unitId;
                filterSubUnits();
            }
            if (subUnitId) document.getElementById('sub_unit_id').value = subUnitId;
            if (clusterId) document.getElementById('cluster_id').value = clusterId;
            if (isQantas !== null) document.getElementById('is_qantas').value = isQantas;
            if (joinDate) document.getElementById('join_date').value = joinDate;
            if (salary) document.getElementById('salary').value = salary;
        });
    }
});
</script>
@endsection
