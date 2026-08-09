@csrf
@isset($method)
    @method($method)
@endisset

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold">{{ isset($document) ? 'Edit Dokumen' : 'Tambah Dokumen Baru' }}</h5>
        <small class="text-muted float-end">Form Pengelolaan File Dokumen</small>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            {{-- Nama Dokumen --}}
            <div class="col-md-6">
                <label for="nama_dokumen" class="form-label">
                    Nama Dokumen <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control @error('nama_dokumen') is-invalid @enderror" id="nama_dokumen"
                    name="nama_dokumen" value="{{ old('nama_dokumen', $document->nama_dokumen ?? '') }}"
                    placeholder="Contoh: SOP Pelaksanaan Ground Handling 2026" required>
                @error('nama_dokumen')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- File Dokumen --}}
            <div class="col-md-6">
                <label for="document_file" class="form-label">
                    File Dokumen <span class="text-danger">{{ empty($document) ? '*' : '' }}</span>
                </label>
                <input type="file" class="form-control @error('document_file') is-invalid @enderror" id="document_file"
                    name="document_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" {{ empty($document) ? 'required' : '' }}>
                <div class="form-text">
                    Format diperbolehkan: PDF, DOC, XLS, JPG, PNG (Maksimal 2 MB).
                </div>
                @error('document_file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                @isset($document)
                    <div class="document-file-summary mt-2">
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
            <div class="col-12">
                <label for="deskripsi_dokumen" class="form-label">
                    Deskripsi Dokumen <span class="text-danger">*</span>
                </label>
                <textarea class="form-control @error('deskripsi_dokumen') is-invalid @enderror" id="deskripsi_dokumen"
                    name="deskripsi_dokumen" rows="3" placeholder="Jelaskan ringkasan atau rincian isi dokumen ini..." required>{{ old('deskripsi_dokumen', $document->deskripsi_dokumen ?? '') }}</textarea>
                @error('deskripsi_dokumen')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Hak Akses Role --}}
            <div class="col-12 mt-4">
                <label class="form-label fw-bold">
                    Hak Akses Dokumen (Role) <span class="text-danger">*</span>
                </label>
                <div class="form-text mb-2">Tentukan role mana saja yang diizinkan mengunduh dokumen ini.</div>

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
                    <label class="document-role-option is-all mb-3">
                        <input type="checkbox" name="role_akses_dokumen[]" value="{{ \App\Models\Document::ACCESS_ALL }}"
                            data-document-role-all {{ $allRolesSelected ? 'checked' : '' }}>
                        <span class="document-role-check"><i class="ti ti-check"></i></span>
                        <span class="fw-bold text-primary">Semua Role (Akses Terbuka)</span>
                    </label>

                    <div class="document-role-search mb-2">
                        <i class="ti ti-search"></i>
                        <input type="text" class="form-control" placeholder="Cari role..." data-document-role-search>
                    </div>

                    <div class="document-role-grid" data-document-role-list>
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

        <x-form-actions :cancelHref="route('admin.documents.index')" :submitText="$submitLabel" />
    </div>
</div>
