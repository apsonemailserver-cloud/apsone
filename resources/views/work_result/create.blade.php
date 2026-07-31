@extends('layout.admin')

@section('title', 'Create Work Order')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="py-4">

        {{-- Header dengan Breadcrumb --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">Create Work Order</h4>
                <p class="text-muted mb-0 small">Aircraft deep cleaning record form (Deep Cleaning Interior & Exterior)</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('work_results.index') }}">Work Order</a></li>
                    <li class="breadcrumb-item active">Create Work Order</li>
                </ol>
            </nav>
        </div>

        {{-- Alert Validasi Error --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <h6 class="alert-heading fw-bold mb-1"><i class="bx bx-error-circle me-1"></i>Validation Error:</h6>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bx bx-check-circle me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-10 offset-md-1">

                {{-- CARD FORM INPUT PEKERJAAN --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between bg-white">
                        <div>
                            <h5 class="card-title text-dark fw-bold mb-0">
                                <i class="bx bx-plus-circle text-primary me-2"></i>Work Order Form
                            </h5>
                            <p class="mb-0 mt-1 small text-muted">Input aircraft deep cleaning details (Deep Cleaning Interior & Exterior)</p>
                        </div>
                    </div>

                    <div class="card-body mt-3">
                        <form action="{{ route('work_results.store') }}" method="POST" enctype="multipart/form-data" id="workResultForm">
                            @csrf

                            {{-- No WO di-generate otomatis di background --}}
                            <input type="hidden" name="wo_number" value="{{ old('wo_number', $nextWoNumber) }}">

                            {{-- FORM GRID PEKERJAAN & AUTO-FILL FLIGHT --}}
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Tanggal Kerja <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Station <span class="text-danger">*</span></label>
                                    <select name="station" id="stationInput" class="form-select" required>
                                        <option value="">-- Pilih Station --</option>
                                        @foreach ($stations as $station)
                                            <option value="{{ $station->code }}" {{ old('station') == $station->code ? 'selected' : '' }}>
                                                {{ $station->code }} - {{ $station->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-primary">
                                        <i class="bx bx-plane-take-off me-1"></i> Pilih Jadwal Flight <small class="text-muted">(Auto-Fill Flightradar24)</small>
                                    </label>
                                    <select id="flightCombobox" class="form-select border-primary bg-white shadow-none">
                                        <option value="">-- Pilih Station Dulu --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Aircraft Registration <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-uppercase" id="aircraftRegInput" name="aircraft_reg" value="{{ old('aircraft_reg') }}" placeholder="e.g. PK-LGH" required>
                                        <button type="button" class="btn btn-outline-primary" id="btnFetchFlightData" title="Cari Data Flight">
                                            <i class="bx bx-search-alt"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1" id="flightSearchStatus"></small>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Parking Stand <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="parking_stand" value="{{ old('parking_stand') }}" placeholder="e.g. A12" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Ex Flight <small class="text-muted">(Default: -)</small></label>
                                    <input type="text" class="form-control" id="exFlightInput" name="ex_flight" value="{{ old('ex_flight') }}" placeholder="e.g. JT 371 atau -">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">To Flight <small class="text-muted">(Default: -)</small></label>
                                    <input type="text" class="form-control" id="toFlightInput" name="to_flight" value="{{ old('to_flight') }}" placeholder="e.g. JT 202 atau -">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="startTime" name="start_time" value="{{ old('start_time') }}" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="endTime" name="end_time" value="{{ old('end_time') }}" required>
                                    <small class="text-muted d-block mt-1">Otomatis +30 menit dari Start Time</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Evidence Photo <small class="text-muted">(Opsional, Maks. 2MB)</small></label>
                                    <input type="file" class="form-control" id="photoInput" name="photo" accept="image/jpeg,image/png,image/jpg">
                                    <div id="photoPreviewContainer" class="mt-2 d-none">
                                        <img id="photoPreview" src="" alt="Preview Foto Bukti" class="rounded shadow-sm border" style="max-height: 100px; object-fit: cover;">
                                    </div>
                                </div>
                            </div>

                            {{-- SECTION 5: TIM STAFF PELAKSANA --}}
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Staff Members <span class="text-danger">*</span> <small class="text-muted">(Pilih minimal 2 dan maksimal 10 staff)</small></label>
                                    <select name="staff_members[]" id="staffMembers" class="form-select select2-multiple" multiple="multiple" data-placeholder="-- Cari & Pilih Staff Berdasarkan Nama / NIK --" required>
                                        @php
                                            $scheduledStaffs = $staffs->filter(fn($s) => $s->is_scheduled);
                                            $otherStaffs = $staffs->filter(fn($s) => !$s->is_scheduled);
                                        @endphp

                                        @if($scheduledStaffs->isNotEmpty())
                                            <optgroup label="⚡ Staff Schedule Aktif (On Duty)">
                                                @foreach($scheduledStaffs as $staff)
                                                    <option value="{{ $staff->id }}" {{ (is_array(old('staff_members')) && in_array($staff->id, old('staff_members'))) ? 'selected' : '' }}>
                                                        {{ $staff->fullname }} (NIK: {{ $staff->id }})
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif

                                        @if($otherStaffs->isNotEmpty())
                                            <optgroup label="Staff Lainnya (Station {{ auth()->user()->station ?: 'All' }})">
                                                @foreach($otherStaffs as $staff)
                                                    <option value="{{ $staff->id }}" {{ (is_array(old('staff_members')) && in_array($staff->id, old('staff_members'))) ? 'selected' : '' }}>
                                                        {{ $staff->fullname }} (NIK: {{ $staff->id }})
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    </select>
                                </div>
                            </div>

                            {{-- SECTION 6: SUBMIT ACTION BUTTONS --}}
                            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4 pt-3 border-top">
                                <button type="submit" name="action" value="DCI" class="btn btn-primary btn-lg px-4">
                                    <i class="bx bx-check-circle me-1"></i> Submit as DCI
                                </button>
                                <button type="submit" name="action" value="DCE" class="btn btn-success btn-lg px-4">
                                    <i class="bx bx-check-circle me-1"></i> Submit as DCE
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- CARD IMPORT EXCEL --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-header border-bottom py-3 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between bg-white gap-2">
                        <div>
                            <h5 class="card-title text-dark fw-bold mb-0">
                                <i class="bx bx-upload text-primary me-2"></i>Import Data Pekerjaan via Excel
                            </h5>
                            <p class="mb-0 mt-1 small text-muted">Upload file spreadsheet (.xlsx / .xls) untuk pencatatan sekaligus (bulk import)</p>
                        </div>
                        <a href="{{ route('work_results.template') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-download me-1"></i> Download Contoh Excel (Template)
                        </a>
                    </div>

                    <div class="card-body mt-3">
                        <form action="{{ route('work_results.import') }}" method="POST" enctype="multipart/form-data" id="excelImportForm">
                            @csrf
                            <div class="row align-items-end">
                                <div class="col-md-9 mb-3 mb-md-0">
                                    <label class="form-label">Berkas File Excel (.xlsx / .xls) <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" name="file" accept=".xlsx, .xls" required>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100 py-2">
                                        <i class="bx bx-upload me-1"></i> Import Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* BRAND THEME COLOR BLUE OVERRIDES (#2f80ed / #2563eb) */
        :root {
            --brand-primary: #2f80ed;
            --brand-primary-hover: #1d4ed8;
            --brand-soft-blue: #eef5ff;
        }

        .btn-primary {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
        }

        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: var(--brand-primary-hover) !important;
            border-color: var(--brand-primary-hover) !important;
        }

        .btn-outline-primary {
            color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
        }

        .btn-outline-primary:hover {
            background-color: var(--brand-primary) !important;
            color: #ffffff !important;
        }

        /* Input Group Seamless Button Alignment Fix */
        .input-group > .form-control {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        .input-group > .btn {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-top-right-radius: 0.375rem !important;
            border-bottom-right-radius: 0.375rem !important;
            border: 1px solid #d9dee3 !important;
            border-left: 0 !important;
            background-color: #f8fafc;
            color: var(--brand-primary) !important;
            font-weight: 500;
        }
        .input-group > .btn:hover {
            background-color: var(--brand-primary) !important;
            border-color: var(--brand-primary) !important;
            color: #ffffff !important;
        }

        .text-primary {
            color: var(--brand-primary) !important;
        }

        .bg-primary {
            background-color: var(--brand-primary) !important;
        }

        .bg-label-primary {
            background-color: var(--brand-soft-blue) !important;
            color: var(--brand-primary) !important;
        }

        /* Card Header Title Visibility Fix */
        .card-header .card-title {
            color: #1e293b !important;
        }
        .card-header p {
            color: #64748b !important;
        }

        /* ENHANCED SELECT2 MULTISELECT COMBOBOX STYLING */
        .select2-container--default .select2-selection--multiple {
            background-color: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            min-height: 44px;
            padding: 4px 8px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: var(--brand-primary) !important;
            box-shadow: 0 0 0 0.25rem rgba(47, 128, 237, 0.2) !important;
        }

        /* Choice Badges (Selected Staff Pills) */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: var(--brand-primary) !important;
            border: none !important;
            color: #ffffff !important;
            border-radius: 6px !important;
            padding: 5px 10px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            margin-top: 4px !important;
            margin-bottom: 4px !important;
            display: inline-flex !important;
            align-items: center !important;
            box-shadow: 0 2px 4px rgba(47, 128, 237, 0.2) !important;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #ffffff !important;
            margin-right: 6px !important;
            border: none !important;
            font-size: 1rem !important;
            line-height: 1 !important;
            opacity: 0.8;
            transition: opacity 0.15s;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            opacity: 1;
            background: transparent !important;
            color: #ffffff !important;
        }

        /* Dropdown Popup */
        .select2-dropdown {
            background-color: #ffffff;
            border: 1px solid #d9dee3;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            z-index: 1055;
        }

        .select2-container--default .select2-results__group {
            padding: 8px 12px;
            font-weight: 700;
            color: var(--brand-primary);
            background-color: var(--brand-soft-blue);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .select2-results__option {
            padding: 8px 14px;
            font-size: 0.9rem;
            color: #334155;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--brand-primary) !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f1f5f9;
            color: #64748b;
        }

        /* Dark Mode Support (html.aps-dark / body.aps-camera-dark) */
        html.aps-dark .card-header .card-title,
        body.aps-camera-dark .card-header .card-title {
            color: #eaf1fb !important;
        }

        html.aps-dark .card-header p,
        body.aps-camera-dark .card-header p {
            color: #94a3b8 !important;
        }

        html.aps-dark .card,
        body.aps-camera-dark .card {
            background-color: #111c31 !important;
            border-color: #24324a !important;
            color: #dbe7f6 !important;
        }

        html.aps-dark .form-control,
        html.aps-dark .form-select,
        body.aps-camera-dark .form-control,
        body.aps-camera-dark .form-select {
            background-color: #17233a !important;
            border-color: #2a3a55 !important;
            color: #eaf1fb !important;
        }

        html.aps-dark .select2-container--default .select2-selection--multiple,
        body.aps-camera-dark .select2-container--default .select2-selection--multiple {
            background-color: #17233a !important;
            border-color: #2a3a55 !important;
            color: #eaf1fb !important;
        }

        html.aps-dark .select2-dropdown,
        body.aps-camera-dark .select2-dropdown {
            background-color: #17233a !important;
            border-color: #2a3a55 !important;
            color: #eaf1fb !important;
        }

        html.aps-dark .select2-results__option,
        body.aps-camera-dark .select2-results__option {
            color: #eaf1fb !important;
        }

        html.aps-dark .select2-container--default .select2-results__group,
        body.aps-camera-dark .select2-container--default .select2-results__group {
            background-color: #1e293b !important;
            color: #60a5fa !important;
        }

        html.aps-dark .select2-search__field,
        body.aps-camera-dark .select2-search__field {
            background-color: #111c31 !important;
            color: #eaf1fb !important;
            border-color: #2a3a55 !important;
        }
    </style>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Function to populate data fields (defined inside document ready but shared)
            // Function to populate data fields
            window.populateFlightData = function(data) {
                if (!data) return;

                console.log('[AP3] populateFlightData triggered:', JSON.stringify(data));

                // Aircraft Registration
                const regVal = data.registasi || data.aircraft_reg;
                if (regVal) {
                    $('#aircraftRegInput').val(regVal);
                }

                // Ex Flight
                const exVal = data.flight_number || data.ex_flight;
                if (exVal) {
                    $('#exFlightInput').val(exVal);
                }

                // To Flight
                if (data.to_flight) {
                    $('#toFlightInput').val(data.to_flight);
                } else if (!$('#toFlightInput').val()) {
                    $('#toFlightInput').val('-');
                }

                // Station
                if (data.station) {
                    const stationEl = document.getElementById('stationInput');
                    if (stationEl) {
                        for (let i = 0; i < stationEl.options.length; i++) {
                            if (stationEl.options[i].value.toUpperCase() === data.station.toUpperCase()) {
                                stationEl.selectedIndex = i;
                                break;
                            }
                        }
                    }
                }

                // Helper to format time strictly to HH:MM (24-hour format)
                function toHHMM(str) {
                    if (!str) return '';
                    str = str.toString().trim();
                    if (str.includes(' ')) str = str.split(' ')[1];
                    if (str.includes('T')) str = str.split('T')[1];
                    if (str.includes('+')) str = str.split('+')[0];
                    if (str.includes('Z')) str = str.replace('Z', '');
                    const parts = str.split(':');
                    if (parts.length >= 2) {
                        const h = parseInt(parts[0], 10);
                        const m = parseInt(parts[1], 10);
                        if (!isNaN(h) && !isNaN(m)) {
                            return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                        }
                    }
                    return '';
                }

                // Start Time & End Time
                let startVal = toHHMM(data.start_time || data.arrival);
                let endVal = toHHMM(data.end_time);

                if (startVal) {
                    const parts = startVal.split(':');
                    const h = parseInt(parts[0], 10);
                    const m = parseInt(parts[1], 10);
                    const totalMin = h * 60 + m + 30;
                    const calcEndH = String(Math.floor(totalMin / 60) % 24).padStart(2, '0');
                    const calcEndM = String(totalMin % 60).padStart(2, '0');
                    if (!endVal) {
                        endVal = calcEndH + ':' + calcEndM;
                    }

                    console.log('[AP3] Applying Start Time:', startVal, '| End Time:', endVal);

                    // Set Start Time
                    $('#startTime').val(startVal).attr('value', startVal);
                    const startEl = document.getElementById('startTime');
                    if (startEl) {
                        startEl.value = startVal;
                        startEl.setAttribute('value', startVal);
                        startEl.dispatchEvent(new Event('input', { bubbles: true }));
                        startEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    // Set End Time
                    $('#endTime').val(endVal).attr('value', endVal);
                    const endEl = document.getElementById('endTime');
                    if (endEl) {
                        endEl.value = endVal;
                        endEl.setAttribute('value', endVal);
                        endEl.dispatchEvent(new Event('input', { bubbles: true }));
                        endEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                // Staff Members
                if (data.staff_ids && data.staff_ids.length > 0) {
                    $('#staffMembers').val(data.staff_ids).trigger('change');
                }
            };

            // Helper to clear flight form inputs & reset combobox selection
            window.clearFlightDataForm = function() {
                // Clear combobox selection
                const $combo = $('#flightCombobox');
                $combo.val('');
                const comboEl = document.getElementById('flightCombobox');
                if (comboEl) comboEl.selectedIndex = 0;

                // Clear form fields
                $('#aircraftRegInput').val('');
                $('#exFlightInput').val('');
                $('#toFlightInput').val('-');
                $('#startTime').val('').attr('value', '').prop('value', '');
                $('#endTime').val('').attr('value', '').prop('value', '');
                const startEl = document.getElementById('startTime');
                if (startEl) {
                    startEl.value = '';
                    startEl.dispatchEvent(new Event('input', { bubbles: true }));
                    startEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
                const endEl = document.getElementById('endTime');
                if (endEl) {
                    endEl.value = '';
                    endEl.dispatchEvent(new Event('input', { bubbles: true }));
                    endEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            // Function to load Flight Combobox options based on Station / Query
            window.loadFlightCombobox = function(stationCode, queryReg, autoSelectFirst) {
                const $combo = $('#flightCombobox');
                const $status = $('#flightSearchStatus');

                // Clear active flights array
                window.__activeFlights = [];

                // Only clear form fields if auto-loading station without query
                if (!queryReg && !autoSelectFirst) {
                    window.clearFlightDataForm();
                }

                $combo.empty().html('<option value="" selected>⏳ Memuat daftar flight untuk station ' + (stationCode || 'terpilih') + '...</option>');
                $status.removeClass('text-success text-danger').addClass('text-muted').text('Sedang memuat...');

                $.ajax({
                    url: "{{ route('work_results.fetch_flight_data') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        station: stationCode,
                        aircraft_reg: queryReg
                    },
                    success: function(response) {
                        if (response.success && response.flights && response.flights.length > 0) {
                            window.__activeFlights = response.flights;
                            let html = '<option value="">-- Pilih Jadwal Penerbangan (Kedatangan) --</option>';
                            response.flights.forEach(function(item, idx) {
                                const ex = item.ex_flight || item.flight_number || '';
                                const to = (item.to_flight && item.to_flight !== '-') ? ` / ${item.to_flight}` : '';
                                const flightNo = `${ex}${to}`;
                                const reg = item.aircraft_reg || item.registasi || '';
                                const regStr = reg ? ` (${reg})` : '';

                                let origin = item.origin || '';
                                if (origin === '-') origin = '';
                                origin = origin
                                    .replace(/ International Airport/gi, '')
                                    .replace(/ Airport/gi, '')
                                    .replace(/ Syamsudin Noor/gi, '')
                                    .replace(/ Adisumarmo/gi, '')
                                    .replace(/ Minangkabau/gi, '')
                                    .replace(/ Soekarno-Hatta/gi, '')
                                    .replace(/ Changi/gi, '')
                                    .replace(/ Juanda/gi, '')
                                    .replace(/ Ngurah Rai/gi, '')
                                    .trim();
                                const originStr = origin ? ` - ${origin}` : '';
                                const arr = item.start_time || item.arrival || '';
                                const timeStr = arr ? ` [${arr}]` : '';

                                html += `<option value="${idx}">${flightNo}${regStr}${originStr}${timeStr}</option>`;
                            });
                            $combo.html(html);

                            // If searching specifically for a reg or autoSelectFirst requested, auto-select & populate first result
                            if ((queryReg || autoSelectFirst) && response.flights[0]) {
                                $combo.val('0');
                                const comboEl = document.getElementById('flightCombobox');
                                if (comboEl) comboEl.selectedIndex = 1; // 1 because index 0 is placeholder option
                                window.populateFlightData(response.flights[0]);
                            } else {
                                $combo.val('');
                                const comboEl = document.getElementById('flightCombobox');
                                if (comboEl) comboEl.selectedIndex = 0;
                            }

                            $status.removeClass('text-muted text-danger').addClass('text-success')
                                .text('✅ ' + response.flights.length + ' flight berhasil dimuat dari ' + (response.source || 'Flightradar24') + '. Data berhasil terisi.');
                        } else {
                            $combo.html('<option value="" selected>-- Tidak ada jadwal arrival khusus di Flightradar24 untuk station ini --</option>');
                            $status.removeClass('text-success').addClass('text-muted').text('Tidak ada data flight.');
                        }
                    },
                    error: function() {
                        $combo.html('<option value="" selected>-- Isian Manual atau Pilih Station Kembali --</option>');
                        $status.removeClass('text-success').addClass('text-danger').text('Gagal memuat data flight.');
                    }
                });
            };

            // Inisialisasi Select2 & Auto-load Combobox
            function initWorkResultPage() {
                const $staffSelect = $('#staffMembers');
                if ($staffSelect.length && typeof $.fn.select2 === 'function') {
                    if ($staffSelect.hasClass('select2-hidden-accessible')) {
                        $staffSelect.select2('destroy');
                    }
                    $staffSelect.select2({
                        placeholder: '-- Cari & Pilih Staff Berdasarkan Nama / NIK --',
                        allowClear: true,
                        width: '100%'
                    });
                }

                // Preview Foto Bukti
                $('#photoInput').off('change').on('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        if (file.size > 2 * 1024 * 1024) {
                            Swal.fire({
                                icon: 'error',
                                title: 'File Terlalu Besar',
                                text: 'Ukuran foto maksimal adalah 2MB.',
                                confirmButtonColor: '#2f80ed'
                            });
                            $(this).val('');
                            $('#photoPreviewBox').addClass('d-none');
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            $('#photoPreview').attr('src', event.target.result);
                            $('#photoPreviewBox').removeClass('d-none');
                        }
                        reader.readAsDataURL(file);
                    } else {
                        $('#photoPreviewBox').addClass('d-none');
                    }
                });

                // Auto-load flight list if station is already selected
                const initStation = $('#stationInput').val();
                if (initStation) {
                    window.loadFlightCombobox(initStation, '');
                }

                // ============================================
                // STATION CHANGE → CLEAR COMBOBOX + RELOAD
                // ============================================
                // Bind DIRECTLY to #stationInput element (not delegated)
                // This ensures it fires even in PJAX-loaded content
                const $stationEl = $('#stationInput');
                // Remove any previous handler to prevent stacking
                $stationEl.off('change.flightClear');
                $stationEl.on('change.flightClear', function() {
                    const selectedStation = $(this).val();
                    console.log('[AP3] stationInput CHANGE fired. New station:', selectedStation);

                    // 1) IMMEDIATELY wipe old flight data
                    window.__activeFlights = [];

                    // 2) IMMEDIATELY clear form fields
                    window.clearFlightDataForm();

                    // 3) IMMEDIATELY clear combobox HTML to empty/loading state
                    const $combo = $('#flightCombobox');
                    const $status = $('#flightSearchStatus');
                    $combo.empty();

                    if (selectedStation) {
                        $combo.append('<option value="" selected>⏳ Memuat flight untuk ' + selectedStation + '...</option>');
                        $status.removeClass('text-success text-danger').addClass('text-muted').text('Memuat...');
                        // Load new station data after DOM update
                        setTimeout(function() {
                            window.loadFlightCombobox(selectedStation, '');
                        }, 100);
                    } else {
                        $combo.append('<option value="">-- Pilih Station Dulu atau Ketik Registrasi --</option>');
                        $status.removeClass('text-success text-danger').addClass('text-muted').text('');
                    }
                });

                // FLIGHT COMBOBOX CHANGE → Auto-fill form
                const $flightCombo = $('#flightCombobox');
                $flightCombo.off('change.flightSelect');
                $flightCombo.on('change.flightSelect', function() {
                    const idx = $(this).val();
                    if (idx !== '' && window.__activeFlights && window.__activeFlights[idx]) {
                        window.populateFlightData(window.__activeFlights[idx]);
                    } else {
                        window.clearFlightDataForm();
                    }
                });
            }

            // Run initial load
            initWorkResultPage();

            // Run on PJAX page load
            window.addEventListener('aps:content-loaded', function() {
                initWorkResultPage();
            });



            // Event Handler: Fetch Flight Data via button
            $(document).off('click', '#btnFetchFlightData').on('click', '#btnFetchFlightData', function() {
                const reg = $('#aircraftRegInput').val().trim();
                const station = $('#stationInput').val();

                if (!reg && !station) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Petunjuk Pengisian',
                        text: 'Silakan pilih Station atau ketik Aircraft Registration (contoh: PK-LGH) terlebih dahulu.',
                        confirmButtonColor: '#2f80ed'
                    });
                    return;
                }

                window.loadFlightCombobox(station, reg, true);
            });

            // Validasi Form sebelum submit
            $(document).off('submit', '#workResultForm').on('submit', '#workResultForm', function(e) {
                const startTime = $('#startTime').val();
                const endTime = $('#endTime').val();
                const selectedStaffs = $('#staffMembers').val() || [];

                if (selectedStaffs.length < 2) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi Staff',
                        text: 'Minimal harus memilih 2 staff pendukung.',
                        confirmButtonColor: '#2f80ed'
                    });
                    return;
                }

                if (selectedStaffs.length > 10) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi Staff',
                        text: 'Maksimal memilih 10 staff pendukung.',
                        confirmButtonColor: '#2f80ed'
                    });
                    return;
                }

                if (startTime && endTime && endTime <= startTime) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validasi Waktu',
                        text: 'End Time harus setelah Start Time.',
                        confirmButtonColor: '#2f80ed'
                    });
                    return;
                }
            });
        });
    </script>
@endsection
