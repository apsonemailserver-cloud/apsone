@csrf
@isset($method)
    @method($method)
@endisset

<div class="row g-4">
    {{-- KOLOM KIRI: METADATA & UPLOAD FILE (MAIN CONTENT) --}}
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-transparent border-bottom pb-3">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-file-text text-primary fs-4"></i>
                    <span>Informasi & File Dokumen</span>
                </h5>
                <p class="text-muted small mb-0 mt-1">Lengkapi nama, file dokumen yang akan diunggah, dan deskripsi ringkas.</p>
            </div>
            <div class="card-body pt-4">
                {{-- Nama Dokumen --}}
                <div class="mb-4">
                    <label for="nama_dokumen" class="form-label fw-bold">
                        Nama Dokumen <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control @error('nama_dokumen') is-invalid @enderror" id="nama_dokumen"
                        name="nama_dokumen" value="{{ old('nama_dokumen', $document->nama_dokumen ?? '') }}"
                        placeholder="Contoh: SOP Pelaksanaan Ground Handling 2026" required>
                    @error('nama_dokumen')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                {{-- File Dokumen --}}
                <div class="mb-4">
                    <label for="document_file" class="form-label fw-bold">
                        File Dokumen <span class="text-danger">{{ empty($document) ? '*' : '' }}</span>
                    </label>
                    <input type="file" class="form-control @error('document_file') is-invalid @enderror" id="document_file"
                        name="document_file" {{ empty($document) ? 'required' : '' }}>
                    <div class="form-text mt-1 text-muted">
                        <i class="ti ti-info-circle me-1"></i>Format diperbolehkan: <strong>PDF, DOC, XLS, JPG, PNG</strong>. Ukuran maksimal: <strong>10 MB</strong>.
                    </div>
                    @error('document_file')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    @isset($document)
                        <div class="document-file-summary mt-3">
                            <div class="document-file-summary-icon">
                                <i class="ti ti-file-text"></i>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate">{{ $document->nama_file }}</div>
                                <div class="small text-muted">{{ $document->ukuran_file ?? '-' }}</div>
                            </div>
                            <span class="badge bg-label-info ms-auto">File Saat Ini</span>
                        </div>
                    @endisset
                </div>

                {{-- Deskripsi Dokumen --}}
                <div class="mb-3">
                    <label for="deskripsi_dokumen" class="form-label fw-bold">
                        Deskripsi Dokumen <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control @error('deskripsi_dokumen') is-invalid @enderror" id="deskripsi_dokumen"
                        name="deskripsi_dokumen" rows="4" placeholder="Jelaskan ringkasan atau rincian isi dokumen ini..." required>{{ old('deskripsi_dokumen', $document->deskripsi_dokumen ?? '') }}</textarea>
                    @error('deskripsi_dokumen')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- KOLOM KANAN: HAK AKSES ROLE & AKSI --}}
    <div class="col-lg-5 d-flex flex-column gap-4">
        <div class="card shadow-sm border-0 flex-grow-1">
            <div class="card-header bg-transparent border-bottom pb-3">
                <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                    <i class="ti ti-shield-lock text-primary fs-4"></i>
                    <span>Hak Akses Dokumen</span>
                </h5>
                <p class="text-muted small mb-0 mt-1">Tentukan role mana saja yang diizinkan mengunduh dokumen ini.</p>
            </div>
            <div class="card-body pt-4">
                @php
                    $selectedAccess = old(
                        'role_akses_dokumen',
                        isset($document) ? $document->role_access_values : [\App\Models\Document::ACCESS_ALL],
                    );
                    $selectedAccess = is_array($selectedAccess) ? $selectedAccess : [$selectedAccess];
                    $selectedAccessNormalized = collect($selectedAccess)
                        ->map(fn ($role) => \App\Models\Document::normalizeRole($role))
                        ->all();
                    $allRolesSelected = in_array(\App\Models\Document::ACCESS_ALL, $selectedAccessNormalized, true);
                @endphp

                <div class="document-role-picker @error('role_akses_dokumen') is-invalid @enderror @error('role_akses_dokumen.*') is-invalid @enderror">
                    <label class="document-role-option is-all mb-2">
                        <input type="checkbox" name="role_akses_dokumen[]" value="{{ \App\Models\Document::ACCESS_ALL }}"
                            data-document-role-all {{ $allRolesSelected ? 'checked' : '' }}>
                        <span class="document-role-check"><i class="ti ti-check"></i></span>
                        <span class="fw-bold">Semua Role</span>
                    </label>

                    <div class="document-role-search mb-2">
                        <i class="ti ti-search"></i>
                        <input type="text" class="form-control" placeholder="Cari role..." data-document-role-search>
                    </div>

                    <div class="document-role-list" data-document-role-list style="max-height: 250px; overflow-y: auto;">
                        @foreach ($availableRoles as $role)
                            @php
                                $roleSelected = !$allRolesSelected
                                    && in_array(\App\Models\Document::normalizeRole($role), $selectedAccessNormalized, true);
                            @endphp
                            <label class="document-role-option" data-document-role-option data-role-label="{{ strtolower($role) }}">
                                <input type="checkbox" name="role_akses_dokumen[]" value="{{ $role }}"
                                    data-document-role-item {{ $roleSelected ? 'checked' : '' }}>
                                <span class="document-role-check"><i class="ti ti-check"></i></span>
                                <span>{{ $role }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                @error('role_akses_dokumen')
                    <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                @enderror
                @error('role_akses_dokumen.*')
                    <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Action Card --}}
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm w-100">
                        <i class="ti ti-device-floppy me-1 fs-5"></i>{{ $submitLabel }}
                    </button>
                    <a href="{{ route('document') }}" class="btn btn-label-secondary w-100">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

