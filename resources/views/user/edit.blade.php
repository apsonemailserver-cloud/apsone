@extends('layout.admin')

@section('title', 'Edit Data User')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="py-4">

            {{-- Header dengan Breadcrumb --}}
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
                <h4 class="fw-bold mb-0">Edit Data User</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">User Management</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.apron') }}">Daftar User</a></li>
                        <li class="breadcrumb-item active">Edit User</li>
                    </ol>
                </nav>
            </div>

            {{-- Card Form Edit --}}
            <div class="row">
                <div class="col-md-10 offset-md-1">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-edit me-2"></i>Edit Data Staff
                            </h5>
                            <p class="mb-0 mt-1 small opacity-75">Perbarui informasi data user {{ $user->fullname }}</p>
                        </div>
                        <div class="card-body">

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading">Terjadi kesalahan:</h6>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if (session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <form action="{{ route('users.update', $user->id) }}" method="POST" id="editUserForm">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="page" value="{{ $page }}">

                                <div class="row">
                                    {{-- Kolom Kiri --}}
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">NIP</label>
                                            <input type="text" class="form-control" name="id"
                                                value="{{ $user->id }}" readonly>
                                            <small class="text-muted">NIP tidak dapat diubah</small>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Nama Lengkap <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="fullname"
                                                value="{{ old('fullname', $user->fullname) }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="email"
                                                value="{{ old('email', $user->email) }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Station <span class="text-danger">*</span></label>
                                            <select name="station" class="form-select" required>
                                                <option value="">-- Pilih Station --</option>
                                                @foreach ($stations as $station)
                                                    <option value="{{ $station->code }}"
                                                        {{ old('station', $user->station) == $station->code ? 'selected' : '' }}>
                                                        {{ $station->code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Job Title <span class="text-danger">*</span></label>
                                            <select name="job_title" class="form-select" required>
                                                <option value="PASSENGER HANDLING"
                                                    {{ old('job_title', $user->job_title) == 'PASSENGER HANDLING' ? 'selected' : '' }}>
                                                    PASSENGER HANDLING</option>
                                                <option value="BAGGAGE HANDLING"
                                                    {{ old('job_title', $user->job_title) == 'BAGGAGE HANDLING' ? 'selected' : '' }}>
                                                    BAGGAGE HANDLING</option>
                                                <option value="RAMP HANDLING"
                                                    {{ old('job_title', $user->job_title) == 'RAMP HANDLING' ? 'selected' : '' }}>
                                                    RAMP HANDLING</option>
                                                <option value="CARGO HANDLING"
                                                    {{ old('job_title', $user->job_title) == 'CARGO HANDLING' ? 'selected' : '' }}>
                                                    CARGO HANDLING</option>
                                                <option value="AIRCRAFT SERVICE"
                                                    {{ old('job_title', $user->job_title) == 'AIRCRAFT SERVICE' ? 'selected' : '' }}>
                                                    AIRCRAFT SERVICE</option>
                                                <option value="SUPPORTING UNIT"
                                                    {{ old('job_title', $user->job_title) == 'SUPPORTING UNIT' ? 'selected' : '' }}>
                                                    SUPPORTING UNIT</option>
                                                <option value="OFFICE / ADMINISTRATION"
                                                    {{ old('job_title', $user->job_title) == 'OFFICE / ADMINISTRATION' ? 'selected' : '' }}>
                                                    OFFICE / ADMINISTRATION</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Cluster <span class="text-danger">*</span></label>
                                            <select name="cluster" class="form-select" required>
                                                <option value="GROUND HANDLING"
                                                    {{ old('cluster', $user->cluster) == 'GROUND HANDLING' ? 'selected' : '' }}>
                                                    GROUND HANDLING</option>
                                                <option value="OFFICE"
                                                    {{ old('cluster', $user->cluster) == 'OFFICE' ? 'selected' : '' }}>
                                                    OFFICE</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Jenis Kelamin <span
                                                    class="text-danger">*</span></label>
                                            <select name="gender" class="form-select" required>
                                                <option value="Male"
                                                    {{ old('gender', $user->gender) == 'Male' ? 'selected' : '' }}>
                                                    Laki-laki</option>
                                                <option value="Female"
                                                    {{ old('gender', $user->gender) == 'Female' ? 'selected' : '' }}>
                                                    Perempuan</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Kolom Kanan --}}
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                                            <select name="unit" class="form-select" required>
                                                <option value="FLIGHT OPERATION"
                                                    {{ old('unit', $user->unit) == 'FLIGHT OPERATION' ? 'selected' : '' }}>
                                                    FLIGHT OPERATION</option>
                                                <option value="RAMP HANDLING"
                                                    {{ old('unit', $user->unit) == 'RAMP HANDLING' ? 'selected' : '' }}>
                                                    RAMP HANDLING</option>
                                                <option value="BAGGAGE HANDLING"
                                                    {{ old('unit', $user->unit) == 'BAGGAGE HANDLING' ? 'selected' : '' }}>
                                                    BAGGAGE HANDLING</option>
                                                <option value="HEAD OFFICE"
                                                    {{ old('unit', $user->unit) == 'HEAD OFFICE' ? 'selected' : '' }}>HEAD
                                                    OFFICE</option>
                                                <option value="PASSENGER HANDLING"
                                                    {{ old('unit', $user->unit) == 'PASSENGER HANDLING' ? 'selected' : '' }}>
                                                    PASSENGER HANDLING</option>
                                                <option value="SUPPORTING / MANAGEMENT"
                                                    {{ old('unit', $user->unit) == 'SUPPORTING / MANAGEMENT' ? 'selected' : '' }}>
                                                    SUPPORTING / MANAGEMENT</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Sub Unit <span class="text-danger">*</span></label>
                                            <select name="sub_unit" class="form-select" required>
                                                <option value="PORTER APRON"
                                                    {{ old('sub_unit', $user->sub_unit) == 'PORTER APRON' ? 'selected' : '' }}>
                                                    PORTER APRON</option>
                                                <option value="PORTER CARGO"
                                                    {{ old('sub_unit', $user->sub_unit) == 'PORTER CARGO' ? 'selected' : '' }}>
                                                    PORTER CARGO</option>
                                                <option value="PORTER MAKE-UP"
                                                    {{ old('sub_unit', $user->sub_unit) == 'PORTER MAKE-UP' ? 'selected' : '' }}>
                                                    PORTER MAKE-UP</option>
                                                <option value="AIRCRAFT INTERIOR CLEANING"
                                                    {{ old('sub_unit', $user->sub_unit) == 'AIRCRAFT INTERIOR CLEANING' ? 'selected' : '' }}>
                                                    AIRCRAFT INTERIOR CLEANING</option>
                                                <option value="DISPATCHER"
                                                    {{ old('sub_unit', $user->sub_unit) == 'DISPATCHER' ? 'selected' : '' }}>
                                                    DISPATCHER</option>
                                                <option value="CONTROLLER"
                                                    {{ old('sub_unit', $user->sub_unit) == 'CONTROLLER' ? 'selected' : '' }}>
                                                    CONTROLLER</option>
                                                <option value="DRIVER"
                                                    {{ old('sub_unit', $user->sub_unit) == 'DRIVER' ? 'selected' : '' }}>
                                                    DRIVER</option>
                                                <option value="AVSEC"
                                                    {{ old('sub_unit', $user->sub_unit) == 'AVSEC' ? 'selected' : '' }}>AVSEC
                                                </option>
                                                <option value="RAMP"
                                                    {{ old('sub_unit', $user->sub_unit) == 'RAMP' ? 'selected' : '' }}>RAMP
                                                </option>
                                                <option value="PASASI"
                                                    {{ old('sub_unit', $user->sub_unit) == 'PASASI' ? 'selected' : '' }}>
                                                    PASASI</option>
                                                <option value="QUALITY CONTROL"
                                                    {{ old('sub_unit', $user->sub_unit) == 'QUALITY CONTROL' ? 'selected' : '' }}>
                                                    QUALITY CONTROL</option>
                                                <option value="HEALTH, SAFETY, AND ENVIRONMENT"
                                                    {{ old('sub_unit', $user->sub_unit) == 'HEALTH, SAFETY, AND ENVIRONMENT' ? 'selected' : '' }}>
                                                    HEALTH, SAFETY, AND ENVIRONMENT (HSE)</option>
                                                <option value="HEAD OF AIRPORT SERVICES"
                                                    {{ old('sub_unit', $user->sub_unit) == 'HEAD OF AIRPORT SERVICES' ? 'selected' : '' }}>
                                                    HEAD OF AIRPORT SERVICES</option>
                                                <option value="HEAD STATION"
                                                    {{ old('sub_unit', $user->sub_unit) == 'HEAD STATION' ? 'selected' : '' }}>
                                                    HEAD STATION</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Role <span class="text-danger">*</span></label>
                                            <select name="role" class="form-select" required>
                                                <option value="">-- Pilih Role --</option>
                                                <option value="Admin" {{ old('role', $user->role) == 'Admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="Finance" {{ old('role', $user->role) == 'Finance' ? 'selected' : '' }}>Finance</option>
                                                <option value="Leader Bge" {{ old('role', $user->role) == 'Leader Bge' ? 'selected' : '' }}>Leader Bge</option>
                                                <option value="SPV Bge" {{ old('role', $user->role) == 'SPV Bge' ? 'selected' : '' }}>SPV Bge</option>
                                                <option value="SPV Apron" {{ old('role', $user->role) == 'SPV Apron' ? 'selected' : '' }}>SPV Apron</option>
                                                <option value="Leader Apron" {{ old('role', $user->role) == 'Leader Apron' ? 'selected' : '' }}>Leader Apron</option>
                                                <option value="Porter Bge" {{ old('role', $user->role) == 'Porter Bge' ? 'selected' : '' }}>Porter Bge</option>
                                                <option value="HSE" {{ old('role', $user->role) == 'HSE' ? 'selected' : '' }}>HSE</option>
                                                <option value="Head Of Airport Service" {{ old('role', $user->role) == 'Head Of Airport Service' ? 'selected' : '' }}>Head Of Airport Service</option>
                                                <option value="Porter Apron" {{ old('role', $user->role) == 'Porter Apron' ? 'selected' : '' }}>Porter Apron</option>
                                                <option value="Ass Leader Apron" {{ old('role', $user->role) == 'Ass Leader Apron' ? 'selected' : '' }}>Ass Leader Apron</option>
                                                <option value="Dispatcher" {{ old('role', $user->role) == 'Dispatcher' ? 'selected' : '' }}>Dispatcher</option>
                                                <option value="Ass Leader Bge" {{ old('role', $user->role) == 'Ass Leader Bge' ? 'selected' : '' }}>Ass Leader Bge</option>
                                                <option value="Driver" {{ old('role', $user->role) == 'Driver' ? 'selected' : '' }}>Driver</option>
                                                <option value="Aircraft Interior Exterior Cleaning" {{ old('role', $user->role) == 'Aircraft Interior Exterior Cleaning' ? 'selected' : '' }}>Aircraft Interior Exterior Cleaning</option>
                                                <option value="Leader Aircraft Interior Exterior Cleaning" {{ old('role', $user->role) == 'Leader Aircraft Interior Exterior Cleaning' ? 'selected' : '' }}>Leader Aircraft Interior Exterior Cleaning</option>
                                                <option value="Leader Porter Apron" {{ old('role', $user->role) == 'Leader Porter Apron' ? 'selected' : '' }}>Leader Porter Apron</option>
                                                <option value="Controller" {{ old('role', $user->role) == 'Controller' ? 'selected' : '' }}>Controller</option>
                                                <option value="Quality Control" {{ old('role', $user->role) == 'Quality Control' ? 'selected' : '' }}>Quality Control</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Supervisor<span class="text-danger">*</span></label>
                                            <select name="manager" class="form-select" required>
                                                <option value="">-- Pilih Supervisor --</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Manager</label>
                                            <select name="senior_manager" class="form-select">
                                                <option value="">-- Pilih Manager --</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Qantas</label>
                                            <select name="is_qantas" class="form-select">
                                                <option value="1"
                                                    {{ old('is_qantas', $user->is_qantas) == 1 ? 'selected' : '' }}>Ya
                                                </option>
                                                <option value="0"
                                                    {{ old('is_qantas', $user->is_qantas) == 0 ? 'selected' : '' }}>Tidak
                                                </option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Join Date</label>
                                            <input type="date" class="form-control" name="join_date"
                                                value="{{ old('join_date', $user->join_date) }}">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Gaji</label>
                                            <input type="number" class="form-control" name="salary"
                                                value="{{ old('salary', $user->salary) }}">
                                            <small class="text-muted">Masukkan angka tanpa format (contoh: 5000000)</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button type="submit" class="btn btn-info">
                                        <i class="bx bx-save me-1"></i>UPDATE DATA
                                    </button>
                                    <a href="{{ route('users.apron', ['page' => $page]) }}" class="btn btn-secondary">
                                        <i class="bx bx-arrow-back me-1"></i>KEMBALI
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $.fn.select2 !== 'undefined') {
                $('.select2').select2({
                    placeholder: '-- Pilih Role --',
                    allowClear: true,
                    width: '100%'
                });
            }

            // Dynamic Superiors Filter based on Station
            const stationSelect = document.querySelector('select[name="station"]');
            const managerSelect = document.querySelector('select[name="manager"]');
            const seniorManagerSelect = document.querySelector('select[name="senior_manager"]');

            const initialManager = "{{ old('manager', $user->manager) }}";
            const initialSeniorManager = "{{ old('senior_manager', $user->senior_manager) }}";

            function syncSelectUI(select, defaultPlaceholder) {
                if (select._apsCombobox) {
                    if (defaultPlaceholder) {
                        select._apsCombobox.placeholder = defaultPlaceholder;
                    }
                    const selected = select.options[select.selectedIndex];
                    const text = (selected && selected.value !== '') ? selected.text.trim() : '';
                    const hasValue = select.value !== '';
                    select._apsCombobox.value.textContent = text || defaultPlaceholder || select._apsCombobox.placeholder;
                    select._apsCombobox.value.classList.toggle('aps-combobox-placeholder', !hasValue);
                }
            }

            function updateSuperiors(station, selectedMgr = '', selectedSeniorMgr = '') {
                const url = "{{ route('users.superiors') }}?station=" + encodeURIComponent(station || '');

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        // Manager Options
                        let mgrHtml = '<option value="">-- Pilih Manager --</option>';
                        if (data.managers && data.managers.length > 0) {
                            data.managers.forEach(m => {
                                const isSelected = (selectedMgr && (m.fullname === selectedMgr || m.display === selectedMgr || selectedMgr.includes(m.fullname))) ? 'selected' : '';
                                mgrHtml += `<option value="${m.fullname}" ${isSelected}>${m.display}</option>`;
                            });
                        } else {
                            mgrHtml = '<option value="">-- Tidak ada Manager --</option>';
                        }
                        managerSelect.innerHTML = mgrHtml;

                        // Senior Manager Options
                        let smHtml = '<option value="">-- Pilih Senior Manager --</option>';
                        if (data.senior_managers && data.senior_managers.length > 0) {
                            data.senior_managers.forEach(sm => {
                                const isSelected = (selectedSeniorMgr && (sm.fullname === selectedSeniorMgr || sm.display === selectedSeniorMgr || selectedSeniorMgr.includes(sm.fullname))) ? 'selected' : '';
                                smHtml += `<option value="${sm.fullname}" ${isSelected}>${sm.display}</option>`;
                            });
                        } else {
                            smHtml = '<option value="">-- Tidak ada Senior Manager --</option>';
                        }
                        seniorManagerSelect.innerHTML = smHtml;

                        syncSelectUI(managerSelect, '-- Pilih Manager --');
                        syncSelectUI(seniorManagerSelect, '-- Pilih Senior Manager --');
                    })
                    .catch(err => {
                        console.error('Gagal mengambil data atasan:', err);
                    });
            }

            if (stationSelect) {
                stationSelect.addEventListener('change', function() {
                    updateSuperiors(this.value, managerSelect.value, seniorManagerSelect.value);
                });

                // Auto fetch on load
                updateSuperiors(stationSelect.value || '', initialManager, initialSeniorManager);
            }

            // Validasi form sebelum submit
            const form = document.getElementById('editUserForm');
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Data Belum Lengkap',
                        text: 'Harap isi semua field yang wajib diisi',
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    // Konfirmasi sebelum submit
                    e.preventDefault();
                    Swal.fire({
                        title: 'Update Data User?',
                        text: 'Apakah Anda yakin ingin memperbarui data user ini?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#4180c3',
                        cancelButtonColor: '#8592a3',
                        confirmButtonText: 'Ya, Update!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                }
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bootstrapAlert = new bootstrap.Alert(alert);
                    bootstrapAlert.close();
                });
            }, 5000);

            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: '{{ session('success') }}',
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif

            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: '{{ session('error') }}',
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif
        });
    </script>
@endsection

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple {
            background-color: #1e293b;
            border-color: #334155;
            border-radius: 6px;
            min-height: 38px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #3b82f6;
            border: none;
            color: #fff;
            border-radius: 4px;
            padding: 2px 8px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
            margin-right: 5px;
        }
        .select2-dropdown {
            background-color: #1e293b;
            border-color: #334155;
            color: #fff;
        }
        .select2-results__option {
            color: #e2e8f0;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #3b82f6;
            color: #fff;
        }

        .is-invalid {
            border-color: #dc3545;
        }

        .form-label {
            font-weight: 500;
        }

        .text-danger {
            color: #dc3545;
        }
    </style>
@endsection
