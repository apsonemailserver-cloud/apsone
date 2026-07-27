@extends('layout.admin')

@section('styles')
<style>
    .station-form-shell {
        max-width: 760px;
        margin: 0 auto;
    }

    .station-form-card .card-header {
        padding: 1.35rem 1.65rem !important;
    }

    .station-form-card .card-body {
        padding: 1.65rem !important;
    }

    .station-form-card .input-group-text {
        width: 46px;
        justify-content: center;
        background: #ffffff;
        border-color: #e6edf5;
        color: #64748b;
    }

    .station-location-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 0.85rem;
        margin-bottom: 0.65rem !important;
    }

    .station-form-card .form-label {
        margin-bottom: 0.38rem;
    }

    .station-map-field {
        margin-bottom: 1rem !important;
    }

    .station-map-preview {
        position: relative;
        height: 148px;
        border: 1px solid #e6edf5;
        border-radius: 14px;
        overflow: hidden;
        background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.85);
    }

    .station-map-preview iframe {
        position: absolute;
        inset: 0;
        width: 100%;
        height: calc(100% + 34px);
        border: 0;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s ease;
    }

    .station-map-preview::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 1;
        cursor: default;
    }

    .station-map-preview.has-location iframe {
        opacity: 1;
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
    }

    .station-map-preview.has-location .station-map-empty {
        display: none;
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

    .station-map-chip {
        position: absolute;
        left: 12px;
        bottom: 10px;
        z-index: 2;
        display: none;
        align-items: center;
        gap: 0.35rem;
        max-width: calc(100% - 24px);
        padding: 0.45rem 0.65rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: #334155;
        font-size: 0.76rem;
        font-weight: 500;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.1);
        backdrop-filter: blur(6px);
        pointer-events: none;
    }

    .station-map-preview.has-location .station-map-chip {
        display: inline-flex;
    }

    @media (max-width: 767.98px) {
        .station-location-grid {
            grid-template-columns: 1fr;
        }

        .station-map-preview {
            height: 140px;
        }
    }

    /* ── Custom Multi-Select Combobox ──────────────────────── */
    .custom-multiselect-wrapper {
        position: relative;
        width: 100%;
    }

    /* Trigger box */
    .multiselect-trigger {
        min-height: 44px;
        background: #ffffff;
        border: 1px solid #d9d9d9;
        border-radius: 8px;
        padding: 6px 12px 6px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        cursor: pointer;
        transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        color: #435971;
    }

    .multiselect-trigger:hover {
        border-color: #b4b7b9;
    }

    .custom-multiselect-wrapper.open .multiselect-trigger {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }

    /* Tags area inside trigger */
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

    .multiselect-tags::-webkit-scrollbar {
        width: 4px;
    }

    .multiselect-tags::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    html.aps-dark .multiselect-tags::-webkit-scrollbar-thumb {
        background: #334155;
    }

    .multiselect-placeholder {
        color: #a1acb8;
        font-size: 0.875rem;
        white-space: nowrap;
    }

    /* Tag pill */
    .multiselect-tag {
        background: #2563eb;
        color: #ffffff;
        border-radius: 5px;
        padding: 3px 7px 3px 10px;
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
        background: #2563eb;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 700;
        border-radius: 5px;
        padding: 2px 7px;
        letter-spacing: 0.01em;
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
        color: #3b82f6;
    }

    /* Dropdown panel — position + size controlled by JS portal */
    .multiselect-dropdown {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    /* Search box */
    .multiselect-search-box {
        position: relative;
        padding: 10px 10px;
        border-bottom: 1px solid #f1f5f9;
    }

    .multiselect-search-input {
        width: 100%;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 7px 36px 7px 12px;
        color: #0f172a;
        font-size: 0.85rem;
        outline: none;
    }

    .multiselect-search-input::placeholder { color: #94a3b8; }

    .multiselect-search-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59,130,246,0.15);
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

    /* Options list — vertical list */
    .multiselect-options-list {
        max-height: 240px;
        overflow-y: auto;
        padding: 4px 0;
    }

    .multiselect-options-list::-webkit-scrollbar { width: 4px; }
    .multiselect-options-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    /* Each option row — BLOCK so they stack vertically */
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
        color: #2563eb;
        background: rgba(37, 99, 235, 0.08);
    }

    .multiselect-option-item.d-none-filter {
        display: none !important;
    }

    /* Hide native checkbox, show custom box */
    .js-option-checkbox {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
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
        background: #2563eb;
        border-color: #2563eb;
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

    /* Dark Mode Overrides */
    html.aps-dark .multiselect-trigger {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }

    html.aps-dark .multiselect-trigger:hover {
        border-color: #475569;
    }

    html.aps-dark .multiselect-placeholder {
        color: #64748b;
    }

    html.aps-dark .multiselect-controls {
        color: #94a3b8;
    }

    html.aps-dark .multiselect-arrow {
        color: #64748b;
    }

    html.aps-dark .multiselect-dropdown {
        background: #0f172a;
        border-color: #3b82f6;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.6);
    }

    html.aps-dark .multiselect-search-box {
        border-bottom-color: #1e293b;
    }

    html.aps-dark .multiselect-search-input {
        background: #1e293b;
        border-color: #334155;
        color: #f8fafc;
    }

    html.aps-dark .multiselect-search-input::placeholder {
        color: #64748b;
    }

    html.aps-dark .multiselect-search-box .search-icon {
        color: #64748b;
    }

    html.aps-dark .multiselect-options-list::-webkit-scrollbar-thumb {
        background: #334155;
    }

    html.aps-dark .multiselect-option-item {
        color: #cbd5e1;
    }

    html.aps-dark .multiselect-option-item:hover {
        background: #1e293b;
        color: #f1f5f9;
    }

    html.aps-dark .multiselect-option-item.selected {
        color: #93c5fd;
        background: rgba(37, 99, 235, 0.15);
    }

    html.aps-dark .custom-checkbox-box {
        border-color: #475569;
        background: #1e293b;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Station /</span> Buka Station Baru
    </h4>

    <div class="station-form-shell">
            <div class="card mb-4 station-form-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Formulir Ekspansi Station</h5>
                        <small class="text-muted">Isi koordinat untuk melihat titik lokasi station.</small>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('stations.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Kode Station (IATA Code)</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-plane"></i></span>
                                <input type="text" name="code" class="form-control" placeholder="Cth: SOC" maxlength="3" required style="text-transform: uppercase;" value="{{ old('code') }}" />
                            </div>
                            <div class="form-text">Maksimal 3 Huruf (Contoh: CGK, SUB, SOC).</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Lokasi / Kota</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-map-2"></i></span>
                                <input type="text" name="name" class="form-control" placeholder="Cth: Solo (Adi Soemarmo)" required value="{{ old('name') }}" />
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
                                        placeholder="Cth: -6.12345678"
                                        step="any"
                                        value="{{ old('latitude') }}"
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
                                        placeholder="Cth: 106.12345678"
                                        step="any"
                                        value="{{ old('longitude') }}"
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
                                    value="{{ old('radius') }}"
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
                                            $allRoles = [
                                                'Admin', 'Finance', 'Leader Bge', 'SPV Bge', 'SPV Apron',
                                                'Leader Apron', 'Porter Bge', 'HSE', 'Head Of Airport Service',
                                                'Porter Apron', 'Ass Leader Apron', 'Dispatcher', 'Ass Leader Bge',
                                                'Driver', 'Aircraft Interior Exterior Cleaning',
                                                'Leader Aircraft Interior Exterior Cleaning', 'Leader Porter Apron',
                                                'Controller', 'Quality Control'
                                            ];
                                            $selectedRoles = (array) old('role', []);
                                        @endphp
                                        @foreach($allRoles as $r)
                                            @php $isChecked = in_array($r, $selectedRoles); @endphp
                                            <label class="multiselect-option-item {{ $isChecked ? 'selected' : '' }}" data-label="{{ strtolower($r) }}">
                                                <input type="checkbox" name="role[]" value="{{ $r }}" {{ $isChecked ? 'checked' : '' }} class="js-option-checkbox" style="position:absolute;opacity:0;width:0;height:0;pointer-events:none;">
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

                        <div class="station-map-field">
                            <label class="form-label">Preview Titik Lokasi</label>
                            <div class="station-map-preview js-station-map-preview" aria-label="Preview titik lokasi station">
                                <iframe class="js-station-map-frame" title="Preview Map Station" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                                <div class="station-map-empty">
                                    <i class="ti ti-map-search"></i>
                                    <strong>Belum ada titik</strong>
                                    <small>Masukkan latitude dan longitude untuk melihat preview.</small>
                                </div>
                                <div class="station-map-chip">
                                    <i class="ti ti-map-pin-filled"></i>
                                    <span class="js-station-map-coordinate">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy"></i> Buka Station Sekarang
                            </button>
                            <a href="{{ url()->previous() }}" class="btn btn-label-secondary">Batal</a>
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
            dropdown.classList.remove('d-none');
            dropdown.style.cssText = [
                'display:none',
                'position:fixed',
                'z-index:99999',
                'top:0',
                'left:0',
                'right:auto',
            ].join(';') + ';';

            let isOpen = false;

            function reposition() {
                const r = trigger.getBoundingClientRect();
                const dropHeight = dropdown.offsetHeight || 260;
                const spaceBelow = window.innerHeight - r.bottom;

                if (spaceBelow < dropHeight && r.top > dropHeight) {
                    dropdown.style.top = Math.max(10, r.top - dropHeight - 4) + 'px';
                } else {
                    dropdown.style.top = (r.bottom + 4) + 'px';
                }
                dropdown.style.left  = r.left  + 'px';
                dropdown.style.width = r.width + 'px';
            }

            function open() {
                dropdown.style.display = 'block';
                wrapper.classList.add('open');
                isOpen = true;
                reposition();
                searchIn.value = '';
                items.forEach(item => item.classList.remove('d-none-filter'));
                setTimeout(() => {
                    reposition();
                    searchIn.focus();
                }, 20);
            }

            function close() {
                dropdown.style.display = 'none';
                wrapper.classList.remove('open');
                isOpen = false;
            }

            function renderUI() {
                const checked = Array.from(optsList.querySelectorAll('.js-option-checkbox:checked'));
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
                        box.style.background  = cb.checked ? '#2563eb' : '';
                        box.style.borderColor = cb.checked ? '#2563eb' : '';
                    }
                    if (ico) ico.style.display = cb.checked ? 'block' : 'none';
                });

                if (isOpen) {
                    requestAnimationFrame(reposition);
                }
            }

            // ── stopPropagation pattern ──────────────────────────────────
            // 1. wrapper stops propagation → document never sees clicks inside wrapper
            wrapper.addEventListener('click', e => e.stopPropagation());

            // 2. dropdown (in body) stops propagation → document never sees clicks inside dropdown
            dropdown.addEventListener('click', e => e.stopPropagation());

            // 3. document click = click was OUTSIDE both → close
            document.addEventListener('click', () => { if (isOpen) close(); });

            // ── Trigger ──────────────────────────────────────────────────
            trigger.addEventListener('click', function (e) {
                if (e.target.closest('.multiselect-tag-remove') ||
                    e.target.closest('.js-multiselect-clear')) return;
                isOpen ? close() : open();
            });

            // ── Remove tag ───────────────────────────────────────────────
            tagsArea.addEventListener('click', function (e) {
                const btn = e.target.closest('.multiselect-tag-remove');
                if (!btn) return;
                const cb = optsList.querySelector(
                    `.js-option-checkbox[value="${CSS.escape(btn.dataset.val)}"]`
                );
                if (cb) { cb.checked = false; renderUI(); }
            });

            // ── Clear all ─────────────────────────────────────────────────
            clearBtn.addEventListener('click', function () {
                optsList.querySelectorAll('.js-option-checkbox').forEach(cb => (cb.checked = false));
                renderUI();
            });

            // ── Toggle option ──────────────────────────────────────────────────
            // Items are <label> elements — browser NATIVELY toggles the nested
            // checkbox on label click. We only need to call renderUI().
            items.forEach(item => {
                item.addEventListener('click', function () {
                    // Use rAF to read state AFTER browser has toggled the checkbox
                    requestAnimationFrame(renderUI);
                });
            });

            // ── Search filter ─────────────────────────────────────────────
            searchIn.addEventListener('input', function () {
                const q = this.value.toLowerCase();
                items.forEach(item => {
                    item.classList.toggle(
                        'd-none-filter',
                        !(item.dataset.label || '').includes(q)
                    );
                });
            });

            // ── Reposition on scroll / resize ─────────────────────────────
            window.addEventListener('scroll', () => { if (isOpen) reposition(); }, true);
            window.addEventListener('resize', () => { if (isOpen) reposition(); });

            renderUI();
        })();



        const latitudeInput = document.querySelector('.js-station-latitude');
        const longitudeInput = document.querySelector('.js-station-longitude');
        const preview = document.querySelector('.js-station-map-preview');
        const frame = document.querySelector('.js-station-map-frame');
        const coordinate = document.querySelector('.js-station-map-coordinate');

        if (!latitudeInput || !longitudeInput || !preview || !frame || !coordinate) return;

        const isCoordinateValid = (lat, lng) => Number.isFinite(lat) && Number.isFinite(lng) &&
            lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;

        const updateMapPreview = () => {
            const latitudeValue = latitudeInput.value.trim();
            const longitudeValue = longitudeInput.value.trim();
            const lat = Number(latitudeValue);
            const lng = Number(longitudeValue);

            if (!latitudeValue || !longitudeValue || !isCoordinateValid(lat, lng)) {
                preview.classList.remove('has-location');
                frame.removeAttribute('src');
                coordinate.textContent = '-';
                return;
            }

            const delta = 0.002;
            const bbox = [
                (lng - delta).toFixed(6),
                (lat - delta).toFixed(6),
                (lng + delta).toFixed(6),
                (lat + delta).toFixed(6),
            ].join('%2C');

            frame.src = `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat.toFixed(6)}%2C${lng.toFixed(6)}`;
            coordinate.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            preview.classList.add('has-location');
        };

        latitudeInput.addEventListener('input', updateMapPreview);
        longitudeInput.addEventListener('input', updateMapPreview);
        updateMapPreview();
    });
</script>
@endsection
