@extends('layout.admin')

@section('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    .station-form-shell {
        max-width: 760px;
        margin: 0 auto;
    }

    .station-form-card {
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.05) !important;
        overflow: hidden;
    }

    .station-form-card .card-header {
        padding: 1.4rem 1.65rem !important;
        background: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }

    .station-form-card .card-header h5,
    .station-form-card .card-header h4,
    .station-form-card .card-title {
        color: #1e293b !important;
        font-weight: 700 !important;
    }

    .station-form-card .card-body {
        padding: 1.65rem !important;
    }

    .station-form-card .form-label {
        margin-bottom: 0.45rem;
        font-size: 0.82rem;
        font-weight: 650;
        color: #334155;
        letter-spacing: 0.01em;
    }

    /* Input Groups and Form Controls */
    .station-form-card .input-group-merge {
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        transition: all 0.2s ease;
        background: #ffffff;
    }

    .station-form-card .input-group-merge:focus-within {
        border-color: #2f80ed !important;
        box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.15) !important;
    }

    .station-form-card .input-group-text {
        width: 44px;
        justify-content: center;
        background: #f8fafc;
        border: none !important;
        color: #64748b;
        font-size: 1.1rem;
    }

    .station-form-card .form-control {
        border: none !important;
        background: #ffffff !important;
        color: #1e293b !important;
        font-size: 0.88rem;
        font-weight: 500;
        padding: 0.65rem 0.85rem;
        box-shadow: none !important;
    }

    .station-form-card .form-control:focus {
        box-shadow: none !important;
        background: #ffffff !important;
    }

    /* Readonly inputs */
    .station-form-card .form-control[readonly] {
        background: #f1f5f9 !important;
        color: #475569 !important;
        font-weight: 600;
    }

    .station-form-card .input-group-merge:has(.form-control[readonly]) .input-group-text {
        background: #f1f5f9 !important;
        color: #64748b !important;
    }

    .station-location-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 0.85rem;
        margin-bottom: 0.65rem !important;
    }

    .station-form-card .form-text {
        margin-top: 0.35rem;
        font-size: 0.76rem;
        color: #64748b;
    }

    /* Leaflet Map Preview */
    .station-map-preview {
        position: relative;
        height: 240px;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        overflow: hidden;
        background: #f8fafc;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.85);
    }

    .leaflet-control-attribution {
        display: none !important;
    }

    .custom-station-pin-wrapper {
        background: transparent !important;
        border: none !important;
    }

    .custom-station-pin-container {
        position: relative;
        width: 38px;
        height: 38px;
    }

    .station-leaflet-marker {
        width: 34px;
        height: 34px;
        border-radius: 50% 50% 50% 0;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        position: absolute;
        transform: rotate(-45deg);
        left: 2px;
        top: 2px;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: grab;
    }

    .station-leaflet-marker::after {
        content: "";
        width: 12px;
        height: 12px;
        background: #ffffff;
        border-radius: 50%;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.25);
    }

    html.aps-dark .station-leaflet-marker {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        box-shadow: 0 8px 18px rgba(59, 130, 246, 0.5);
    }

    .station-map-empty {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        text-align: center;
        padding: 1rem;
        color: #64748b;
        pointer-events: none;
        background: #f8fafc;
    }

    .station-map-empty i {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #eaf4ff;
        color: #2f80ed;
        font-size: 1.35rem;
    }

    .station-map-chip span {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    /* Map Search Autocomplete Results Dropdown */
    #stationMapSearchResults {
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 10px !important;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18), 0 4px 10px rgba(15, 23, 42, 0.08) !important;
        overflow: hidden !important;
    }

    #stationMapSearchResults .list-group-item {
        background-color: #ffffff !important;
        color: #1e293b !important;
        border: none !important;
        border-bottom: 1px solid #f1f5f9 !important;
        transition: background-color 0.15s ease;
    }

    #stationMapSearchResults .list-group-item:last-child {
        border-bottom: none !important;
    }

    #stationMapSearchResults .list-group-item:hover,
    #stationMapSearchResults .list-group-item:focus {
        background-color: #f1f5f9 !important;
        color: #2563eb !important;
    }

    html.aps-dark #stationMapSearchResults {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.5) !important;
    }

    html.aps-dark #stationMapSearchResults .list-group-item {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border-bottom: 1px solid #334155 !important;
    }

    html.aps-dark #stationMapSearchResults .list-group-item:hover,
    html.aps-dark #stationMapSearchResults .list-group-item:focus {
        background-color: #334155 !important;
        color: #60a5fa !important;
    }

    .station-map-chip {
        position: absolute;
        left: 12px;
        bottom: 10px;
        z-index: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        max-width: calc(100% - 24px);
        padding: 0.45rem 0.65rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.94);
        color: #334155;
        font-size: 0.76rem;
        font-weight: 500;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.1);
        backdrop-filter: blur(6px);
        pointer-events: none;
    }

    @media (max-width: 767.98px) {
        .station-location-grid {
            grid-template-columns: 1fr;
        }

        .station-map-preview {
            height: 190px;
        }
    }


    /* ── Custom Multi-Select Combobox ──────────────────────── */
    .custom-multiselect-wrapper {
        position: relative;
        width: 100%;
    }

    .multiselect-trigger {
        min-height: 44px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 6px 12px 6px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #334155;
    }

    .multiselect-trigger:hover {
        border-color: #94a3b8;
    }

    .custom-multiselect-wrapper.open .multiselect-trigger {
        border-color: #2f80ed;
        box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.15);
    }

    .multiselect-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        align-items: center;
        flex: 1;
        min-width: 0;
        max-height: 90px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .multiselect-placeholder {
        color: #94a3b8;
        font-size: 0.875rem;
        white-space: nowrap;
    }

    /* Tag pill */
    .multiselect-tag {
        background: #2f80ed;
        color: #ffffff;
        border-radius: 6px;
        padding: 3px 8px 3px 10px;
        font-size: 0.78rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
        line-height: 1.4;
    }

    .multiselect-tag-remove {
        cursor: pointer;
        font-size: 15px;
        line-height: 1;
        opacity: 0.8;
        border: none;
        background: none;
        color: inherit;
        padding: 0;
        display: inline-flex;
        align-items: center;
    }

    .multiselect-tag-remove:hover {
        opacity: 1;
        color: #fca5a5;
    }

    /* Right-side controls */
    .multiselect-controls {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
        color: #64748b;
    }

    .multiselect-count-badge {
        background: #2f80ed;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        border-radius: 6px;
        padding: 2px 7px;
    }

    .multiselect-clear-btn {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        font-size: 18px;
        padding: 0;
        line-height: 1;
        display: inline-flex;
        align-items: center;
    }

    .multiselect-clear-btn:hover { color: #ef4444; }

    .multiselect-arrow {
        font-size: 1rem;
        transition: transform 0.2s ease;
        color: #64748b;
    }

    .custom-multiselect-wrapper.open .multiselect-arrow {
        transform: rotate(180deg);
        color: #2f80ed;
    }

    .multiselect-dropdown {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
        overflow: hidden;
    }

    /* Search box */
    .multiselect-search-box {
        padding: 10px;
        border-bottom: 1px solid #f1f5f9;
    }

    .multiselect-search-input {
        width: 100%;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 7px 36px 7px 12px;
        color: #0f172a;
        font-size: 0.85rem;
        outline: none;
    }

    .multiselect-search-input::placeholder { color: #94a3b8; }

    .multiselect-search-input:focus {
        border-color: #2f80ed;
        box-shadow: 0 0 0 2px rgba(47,128,237,0.15);
    }

    .multiselect-search-box .search-icon {
        position: absolute;
        right: 22px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.95rem;
        pointer-events: none;
    }

    /* Options list */
    .multiselect-options-list {
        max-height: 240px;
        overflow-y: auto;
        padding: 4px 0;
    }

    .multiselect-option-item {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 9px 14px;
        cursor: pointer;
        font-size: 0.875rem;
        color: #334155;
        transition: background 0.12s;
        box-sizing: border-box;
        user-select: none;
    }

    .multiselect-option-item:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .multiselect-option-item.selected {
        color: #2f80ed;
        background: rgba(47, 128, 237, 0.08);
    }

    .custom-checkbox-box {
        width: 18px;
        height: 18px;
        min-width: 18px;
        border: 2px solid #cbd5e1;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
        background: #ffffff;
    }

    .custom-checkbox-box i {
        font-size: 11px;
        color: #fff;
        display: none;
    }

    .js-option-checkbox:checked ~ .custom-checkbox-box,
    .multiselect-option-item.selected .custom-checkbox-box {
        background: #2f80ed;
        border-color: #2f80ed;
    }

    .js-option-checkbox:checked ~ .custom-checkbox-box i,
    .multiselect-option-item.selected .custom-checkbox-box i {
        display: block;
    }

    .option-label-text {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ── DARK MODE OVERRIDES (html.aps-dark) ─────────────────── */
    html.aps-dark .station-form-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 18px 44px rgba(0, 0, 0, 0.3) !important;
    }

    html.aps-dark .station-form-card .card-header {
        background: #1e293b !important;
        border-bottom-color: #334155 !important;
    }

    html.aps-dark .station-form-card .card-header h5,
    html.aps-dark .station-form-card .card-header h4,
    html.aps-dark .station-form-card .card-title {
        color: #f8fafc !important;
    }

    html.aps-dark .station-form-card .form-label {
        color: #e2e8f0 !important;
    }

    html.aps-dark .station-form-card .input-group-merge {
        background: #0f172a !important;
        border-color: #334155 !important;
    }

    html.aps-dark .station-form-card .input-group-merge:focus-within {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2) !important;
    }

    html.aps-dark .station-form-card .input-group-text {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
    }

    html.aps-dark .station-form-card .form-control {
        background: #0f172a !important;
        color: #f8fafc !important;
        border-color: #334155 !important;
    }

    html.aps-dark .station-form-card .form-control[readonly] {
        background: #1e293b !important;
        color: #94a3b8 !important;
    }

    html.aps-dark .station-form-card .input-group-merge:has(.form-control[readonly]) .input-group-text {
        background: #1e293b !important;
        color: #64748b !important;
    }

    html.aps-dark .station-form-card .form-text {
        color: #94a3b8 !important;
    }

    html.aps-dark .station-map-preview {
        background: #0f172a !important;
        border-color: #334155 !important;
    }

    html.aps-dark .station-map-empty {
        background: #0f172a !important;
        color: #94a3b8 !important;
    }

    html.aps-dark .station-map-chip {
        background: rgba(30, 41, 59, 0.94) !important;
        color: #f8fafc !important;
    }

    html.aps-dark .multiselect-trigger {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    html.aps-dark .multiselect-trigger:hover {
        border-color: #475569 !important;
    }

    html.aps-dark .multiselect-placeholder {
        color: #64748b !important;
    }

    html.aps-dark .multiselect-controls {
        color: #94a3b8 !important;
    }

    html.aps-dark .multiselect-dropdown {
        background: #0f172a !important;
        border-color: #334155 !important;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6) !important;
    }

    html.aps-dark .multiselect-search-box {
        border-bottom-color: #1e293b !important;
    }

    html.aps-dark .multiselect-search-input {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
    }

    html.aps-dark .multiselect-option-item {
        color: #cbd5e1 !important;
    }

    html.aps-dark .multiselect-option-item:hover {
        background: #1e293b !important;
        color: #f8fafc !important;
    }

    html.aps-dark .multiselect-option-item.selected {
        color: #60a5fa !important;
        background: rgba(96, 165, 250, 0.14) !important;
    }

    html.aps-dark .custom-checkbox-box {
        background: #0f172a !important;
        border-color: #475569 !important;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Station /</span> Update Station
    </h4>

    <div class="station-form-shell">
            <div class="card mb-4 station-form-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Update Formulir Ekspansi Station</h5>
                        <small class="text-muted">Ubah koordinat untuk memastikan titik station sudah tepat.</small>
                    </div>
                </div>
                <div class="card-body">
                    <form id="stationForm" action="{{ route('stations.update', $station->id) }}" method="POST">
                        @csrf

                        <div class="station-location-grid mb-3">
                            <div>
                                <label class="form-label">Kode Station (IATA Code)</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="ti ti-plane"></i></span>
                                    <input type="text" name="code" class="form-control" value="{{ $station->code }}" maxlength="3" required style="text-transform: uppercase;" readonly />
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Nama Lokasi / Kota</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="ti ti-map-2"></i></span>
                                    <input type="text" name="name" class="form-control" value="{{ $station->name }}" readonly/>
                                </div>
                            </div>
                        </div>

                        <div class="station-location-grid mb-3">
                            <div>
                                <label class="form-label">Latitude</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="ti ti-map-pin"></i>
                                    </span>
                                    <input
                                        type="number"
                                        name="latitude"
                                        class="form-control js-station-latitude"
                                        value="{{ old('latitude', $station->latitude) }}"
                                        step="any"
                                        required />
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Longitude</label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text">
                                        <i class="ti ti-map-pin"></i>
                                    </span>
                                    <input
                                        type="number"
                                        name="longitude"
                                        class="form-control js-station-longitude"
                                        value="{{ old('longitude', $station->longitude) }}"
                                        step="any"
                                        required />
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Radius Absen (Meter)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-radar"></i></span>
                                <input
                                    type="number"
                                    name="radius"
                                    class="form-control"
                                    placeholder="Cth: 40"
                                    min="1"
                                    value="{{ old('radius', $station->radius) }}"
                                    required />
                            </div>
                            <div class="form-text">Radius toleransi absensi dalam satuan meter.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role Operasional Station <span class="text-danger">*</span></label>
                            
                            <div class="custom-multiselect-wrapper js-custom-multiselect" id="stationRoleMultiselect">
                                <!-- Trigger / Input Display -->
                                <div class="multiselect-trigger js-multiselect-trigger" tabindex="0">
                                    <div class="multiselect-tags js-multiselect-tags">
                                        <span class="multiselect-placeholder">Select role...</span>
                                    </div>
                                    <div class="multiselect-controls">
                                        <span class="multiselect-count-badge js-multiselect-count d-none">0</span>
                                        <button type="button" class="multiselect-clear-btn js-multiselect-clear d-none" title="Clear all">&times;</button>
                                        <i class="ti ti-chevron-down multiselect-arrow"></i>
                                    </div>
                                </div>

                                <!-- Dropdown Panel -->
                                <div class="multiselect-dropdown js-multiselect-dropdown d-none">
                                    <div class="multiselect-search-box">
                                        <input type="text" class="multiselect-search-input js-multiselect-search" placeholder="Search...">
                                        <i class="ti ti-search search-icon"></i>
                                    </div>
                                    <div class="multiselect-options-list js-multiselect-options">
                                        @php
                                            $allRoles = $availableRoles ?? \App\Models\Role::orderBy('name', 'asc')->pluck('name')->toArray();
                                            $stationRoles = old('role', explode(',', (string)($station->role ?? '')));
                                            if (!is_array($stationRoles)) {
                                                $stationRoles = explode(',', (string)$stationRoles);
                                            }
                                            $stationRoles = array_map('trim', (array)$stationRoles);
                                        @endphp
                                        @foreach($allRoles as $r)
                                            @php $isChecked = in_array($r, $stationRoles); @endphp
                                            <label class="multiselect-option-item {{ $isChecked ? 'selected' : '' }}" data-label="{{ strtolower($r) }}">
                                                <input type="checkbox" name="role[]" value="{{ $r }}" form="stationForm" {{ $isChecked ? 'checked' : '' }} class="js-option-checkbox" style="position:absolute;opacity:0;width:0;height:0;pointer-events:none;">
                                                <span class="custom-checkbox-box">
                                                    <i class="ti ti-check"></i>
                                                </span>
                                                <span class="option-label-text">{{ $r }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="form-text">Pilih satu atau beberapa role pekerjaan yang beroperasi pada station ini.</div>
                        </div>

                        <div class="station-map-field mb-3">
                            <label class="form-label">Pencarian & Preview Titik Lokasi</label>
                            
                            <div class="position-relative mb-2">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="ti ti-search text-muted"></i></span>
                                    <input type="text" id="stationMapSearchInput" class="form-control ps-0" placeholder="Cari nama lokasi / bandara / kota (cth: Soekarno-Hatta, Juanda, Kualanamu)..." autocomplete="off" />
                                    <button type="button" id="stationMapSearchBtn" class="btn btn-primary px-3">
                                        <i class="ti ti-search me-1"></i>Cari
                                    </button>
                                </div>
                                <div id="stationMapSearchResults" class="list-group shadow-lg border-0 d-none mt-1 position-absolute w-100" style="z-index: 99999; max-height: 230px; overflow-y: auto; border-radius: 10px;"></div>
                            </div>

                            <div class="station-map-preview js-station-map-preview" aria-label="Preview titik lokasi station">
                                <div id="leafletStationMap" style="width: 100%; height: 100%; z-index: 1;"></div>
                                <div class="station-map-empty" style="z-index: 2;">
                                    <i class="ti ti-map-search"></i>
                                    <strong>Belum ada titik</strong>
                                    <small>Masukkan latitude dan longitude untuk melihat preview.</small>
                                </div>
                                <div class="station-map-chip">
                                    <i class="ti ti-map-pin-filled"></i>
                                    <span class="js-station-map-coordinate">-</span>
                                </div>
                            </div>
                            <div class="form-text mt-1"><i class="ti ti-info-circle me-1"></i>Cari lokasi di atas, geser marker, atau klik area pada peta untuk menentukan koordinat presisi.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy"></i> Simpan Perubahan Station
                            </button>
                            <a href="{{ route('stations.index') }}" class="btn btn-label-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ── Custom Multi-Select ────────────────────────────────────────
        (function () {
            const wrapper = document.getElementById('stationRoleMultiselect');
            if (!wrapper) return;

            const trigger    = wrapper.querySelector('.js-multiselect-trigger');
            const dropdown   = wrapper.querySelector('.js-multiselect-dropdown');
            const tagsArea   = wrapper.querySelector('.js-multiselect-tags');
            const countBadge = wrapper.querySelector('.js-multiselect-count');
            const clearBtn   = wrapper.querySelector('.js-multiselect-clear');
            const searchIn   = wrapper.querySelector('.js-multiselect-search');
            const optsList   = wrapper.querySelector('.js-multiselect-options');
            const items      = optsList.querySelectorAll('.multiselect-option-item');

            // ── Portal: move dropdown to <body> to escape overflow:hidden ──
            document.body.appendChild(dropdown);

            const form = wrapper.closest('form') || document.getElementById('stationForm');
            if (form) {
                form.addEventListener('submit', function () {
                    wrapper.appendChild(dropdown);
                });
            }

            let isOpen = false;

            function reposition() {
                const rect = trigger.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.left     = rect.left + 'px';
                dropdown.style.width    = rect.width + 'px';
                dropdown.style.zIndex   = '999999';

                const spaceBelow = window.innerHeight - rect.bottom;
                const dropdownHeight = 290;

                if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
                    dropdown.style.top    = (rect.top - dropdownHeight - 2) + 'px';
                    dropdown.style.bottom = 'auto';
                } else {
                    dropdown.style.top    = (rect.bottom + 2) + 'px';
                    dropdown.style.bottom = 'auto';
                }
            }

            function open() {
                isOpen = true;
                dropdown.classList.remove('d-none');
                wrapper.classList.add('open');
                reposition();
                searchIn.value = '';
                items.forEach(i => i.classList.remove('d-none-filter'));
                setTimeout(() => searchIn.focus(), 50);
            }

            function close() {
                isOpen = false;
                dropdown.classList.add('d-none');
                wrapper.classList.remove('open');
            }

            function renderUI() {
                const checked = optsList.querySelectorAll('.js-option-checkbox:checked');
                tagsArea.innerHTML = '';

                if (checked.length === 0) {
                    tagsArea.innerHTML = '<span class="multiselect-placeholder">Pilih role...</span>';
                    countBadge.classList.add('d-none');
                    clearBtn.classList.add('d-none');
                } else {
                    checked.forEach(cb => {
                        const tag = document.createElement('span');
                        tag.className = 'multiselect-tag';
                        tag.innerHTML =
                            cb.value +
                            `<button type="button" class="multiselect-tag-remove" data-val="${cb.value}">\u00d7</button>`;
                        tagsArea.appendChild(tag);
                    });
                    countBadge.textContent = checked.length;
                    countBadge.classList.remove('d-none');
                    clearBtn.classList.remove('d-none');
                }

                items.forEach(item => {
                    const cb  = item.querySelector('.js-option-checkbox');
                    const box = item.querySelector('.custom-checkbox-box');
                    const ico = box ? box.querySelector('i') : null;
                    item.classList.toggle('selected', cb.checked);
                    if (box) {
                        box.style.background  = cb.checked ? '#2f80ed' : '';
                        box.style.borderColor = cb.checked ? '#2f80ed' : '';
                    }
                    if (ico) ico.style.display = cb.checked ? 'block' : 'none';
                });

                if (isOpen) {
                    requestAnimationFrame(reposition);
                }
            }

            wrapper.addEventListener('click', e => e.stopPropagation());
            dropdown.addEventListener('click', e => e.stopPropagation());
            document.addEventListener('click', () => { if (isOpen) close(); });

            trigger.addEventListener('click', function (e) {
                if (e.target.closest('.multiselect-tag-remove') ||
                    e.target.closest('.js-multiselect-clear')) return;
                isOpen ? close() : open();
            });

            tagsArea.addEventListener('click', function (e) {
                const btn = e.target.closest('.multiselect-tag-remove');
                if (!btn) return;
                const cb = optsList.querySelector(
                    `.js-option-checkbox[value="${CSS.escape(btn.dataset.val)}"]`
                );
                if (cb) { cb.checked = false; renderUI(); }
            });

            clearBtn.addEventListener('click', function () {
                optsList.querySelectorAll('.js-option-checkbox').forEach(cb => (cb.checked = false));
                renderUI();
            });

            items.forEach(item => {
                item.addEventListener('click', function () {
                    requestAnimationFrame(renderUI);
                });
            });

            searchIn.addEventListener('input', function () {
                const q = this.value.toLowerCase();
                items.forEach(item => {
                    item.classList.toggle(
                        'd-none-filter',
                        !(item.dataset.label || '').includes(q)
                    );
                });
            });

            window.addEventListener('scroll', () => { if (isOpen) reposition(); }, true);
            window.addEventListener('resize', () => { if (isOpen) reposition(); });

            renderUI();
        })();

        // ── Leaflet Interactive Map ────────────────────────────────────
        const latitudeInput = document.querySelector('.js-station-latitude');
        const longitudeInput = document.querySelector('.js-station-longitude');
        const radiusInput = document.querySelector('input[name="radius"]');
        const mapContainer = document.getElementById('leafletStationMap');
        const mapEmptyOverlay = document.querySelector('.station-map-empty');
        const coordinateChip = document.querySelector('.js-station-map-coordinate');

        if (!latitudeInput || !longitudeInput || !mapContainer) return;

        let map = null;
        let marker = null;
        let radiusCircle = null;
        let currentTileLayer = null;

        const GOOGLE_ROADS = 'https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}';
        const DARK_TILES = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';

        function isDarkMode() {
            return document.documentElement.classList.contains('aps-dark');
        }

        function getTileUrl() {
            return GOOGLE_ROADS;
        }

        function getSubdomains() {
            return '0123';
        }

        function initMap(lat, lng) {
            map = L.map('leafletStationMap', {
                center: [lat, lng],
                zoom: 16,
                zoomControl: true,
                attributionControl: false
            });

            currentTileLayer = L.tileLayer(getTileUrl(), {
                maxZoom: 20,
                subdomains: getSubdomains(),
                attribution: ''
            }).addTo(map);

            const customIcon = L.divIcon({
                className: 'custom-station-pin-wrapper',
                html: `<div class="custom-station-pin-container"><div class="station-leaflet-marker"></div></div>`,
                iconSize: [38, 38],
                iconAnchor: [19, 36]
            });

            marker = L.marker([lat, lng], { draggable: true, icon: customIcon }).addTo(map);

            const rMeters = Number(radiusInput?.value) || 40;
            radiusCircle = L.circle([lat, lng], {
                radius: rMeters,
                color: '#2563eb',
                fillColor: '#2563eb',
                fillOpacity: 0.15,
                weight: 2
            }).addTo(map);

            marker.on('dragend', function () {
                const pos = marker.getLatLng();
                latitudeInput.value = pos.lat.toFixed(6);
                longitudeInput.value = pos.lng.toFixed(6);
                updateRadiusCircle(pos.lat, pos.lng);
                updateChip(pos.lat, pos.lng);
            });

            map.on('click', function (e) {
                const { lat, lng } = e.latlng;
                latitudeInput.value = lat.toFixed(6);
                longitudeInput.value = lng.toFixed(6);
                marker.setLatLng([lat, lng]);
                updateRadiusCircle(lat, lng);
                updateChip(lat, lng);
            });
        }

        function updateRadiusCircle(lat, lng) {
            const rMeters = Number(radiusInput?.value) || 40;
            if (radiusCircle) {
                radiusCircle.setLatLng([lat, lng]);
                radiusCircle.setRadius(rMeters);
            }
        }

        function updateChip(lat, lng) {
            if (coordinateChip) {
                coordinateChip.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            }
        }

        function updateMap() {
            const latVal = Number(latitudeInput.value.trim());
            const lngVal = Number(longitudeInput.value.trim());

            const isValid = Number.isFinite(latVal) && Number.isFinite(lngVal) &&
                latVal >= -90 && latVal <= 90 && lngVal >= -180 && lngVal <= 180;

            if (!isValid) {
                if (mapEmptyOverlay) mapEmptyOverlay.style.display = 'flex';
                return;
            }

            if (mapEmptyOverlay) mapEmptyOverlay.style.display = 'none';

            if (!map) {
                initMap(latVal, lngVal);
            } else {
                map.setView([latVal, lngVal], 16);
                marker.setLatLng([latVal, lngVal]);
                updateRadiusCircle(latVal, lngVal);
            }
            updateChip(latVal, lngVal);
        }

        latitudeInput.addEventListener('input', updateMap);
        longitudeInput.addEventListener('input', updateMap);
        if (radiusInput) {
            radiusInput.addEventListener('input', function () {
                const latVal = Number(latitudeInput.value.trim());
                const lngVal = Number(longitudeInput.value.trim());
                if (Number.isFinite(latVal) && Number.isFinite(lngVal)) {
                    updateRadiusCircle(latVal, lngVal);
                }
            });
        }

        const observer = new MutationObserver(() => {
            if (currentTileLayer) {
                currentTileLayer.setUrl(getTileUrl());
            }
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        updateMap();

        // ── Geocoding Location Search (100% Free OpenStreetMap Nominatim) ──
        const searchInput = document.getElementById('stationMapSearchInput');
        const searchBtn = document.getElementById('stationMapSearchBtn');
        const searchResults = document.getElementById('stationMapSearchResults');

        if (searchInput && searchResults) {
            let debounceTimer = null;

            async function performSearch(query) {
                if (!query || query.length < 2) {
                    searchResults.classList.add('d-none');
                    return;
                }

                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`);
                    const data = await res.json();

                    if (!data || data.length === 0) {
                        searchResults.innerHTML = '<div class="list-group-item text-muted small py-2 px-3">Lokasi tidak ditemukan</div>';
                        searchResults.classList.remove('d-none');
                        return;
                    }

                    searchResults.innerHTML = data.map(item => `
                        <button type="button" class="list-group-item list-group-item-action py-2 px-3 text-start js-search-result-item" data-lat="${item.lat}" data-lng="${item.lon}">
                            <div class="fw-semibold small"><i class="ti ti-map-pin me-1 text-primary"></i>${item.display_name}</div>
                        </button>
                    `).join('');

                    searchResults.classList.remove('d-none');
                } catch (err) {
                    console.error('Geocoding search error:', err);
                }
            }

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => performSearch(this.value.trim()), 350);
            });

            if (searchBtn) {
                searchBtn.addEventListener('click', function () {
                    performSearch(searchInput.value.trim());
                });
            }

            searchResults.addEventListener('click', function (e) {
                const btn = e.target.closest('.js-search-result-item');
                if (!btn) return;

                const lat = Number(btn.dataset.lat);
                const lng = Number(btn.dataset.lng);

                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    latitudeInput.value = lat.toFixed(6);
                    longitudeInput.value = lng.toFixed(6);
                    updateMap();
                    searchResults.classList.add('d-none');
                    searchInput.value = '';
                }
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('#stationMapSearchInput') && !e.target.closest('#stationMapSearchResults')) {
                    searchResults.classList.add('d-none');
                }
            });
        }
    });
</script>
@endsection
