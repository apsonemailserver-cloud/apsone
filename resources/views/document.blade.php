@extends('layout.admin')

@section('styles')
    <style>
        .document-page {
            --doc-bg: #f8fafc;
            --doc-surface: #ffffff;
            --doc-border: #e2e8f0;
            --doc-border-hover: #cbd5e1;
            --doc-text-main: #0f172a;
            --doc-text-sub: #475569;
            --doc-text-muted: #64748b;
            
            --doc-primary: #2f80ed;
            --doc-primary-hover: #1e6bd6;
            --doc-primary-soft: #eff6ff;
            
            --doc-pdf: #e34d4d;
            --doc-pdf-bg: #fdecec;
            --doc-word: #2f80ed;
            --doc-word-bg: #eff6ff;
            --doc-excel: #16a163;
            --doc-excel-bg: #e9f8f0;
            --doc-default: #4f46e5;
            --doc-default-bg: #eef2ff;

            --doc-shadow-sm: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            --doc-shadow-md: 0 4px 6px -1px rgba(0,0,0,0.06), 0 2px 4px -1px rgba(0,0,0,0.04);
            --doc-shadow-hover: 0 20px 25px -5px rgba(0,0,0,0.08), 0 8px 10px -6px rgba(0,0,0,0.04);
        }

        /* Hero Banner */
        .document-page .hero-banner {
            position: relative;
            background: linear-gradient(135deg, #2f80ed 0%, #2368c8 52%, #184fa8 100%);
            border-radius: 1.25rem;
            padding: 2.25rem 2rem;
            color: #ffffff;
            overflow: hidden;
            box-shadow: 0 10px 30px -5px rgba(47, 128, 237, 0.28);
            margin-bottom: 2rem;
        }

        .document-page .hero-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .document-page .hero-banner::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: 20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .document-page .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #ffffff;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.25);
        }

        .document-page .hero-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .document-page .hero-subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.88);
            max-width: 620px;
            margin-bottom: 0;
            line-height: 1.5;
        }

        /* Search & Filter Bar */
        .document-page .filter-bar {
            background: var(--doc-surface);
            border: 1px solid var(--doc-border);
            border-radius: 1rem;
            padding: 0.85rem 1.25rem;
            box-shadow: var(--doc-shadow-sm);
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .document-page .search-box {
            position: relative;
            flex: 1;
            min-width: 260px;
        }

        .document-page .search-box input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.65rem;
            border: 1px solid var(--doc-border);
            border-radius: 0.75rem;
            background: var(--doc-bg);
            color: var(--doc-text-main);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .document-page .search-box input:focus {
            outline: none;
            border-color: var(--doc-primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .document-page .search-box i {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--doc-text-muted);
            font-size: 1.15rem;
        }

        .document-page .doc-counter-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--doc-primary-soft);
            color: var(--doc-primary);
            font-weight: 700;
            font-size: 0.825rem;
            padding: 0.45rem 0.9rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(37, 99, 235, 0.2);
        }

        /* Stat Cards */
        .document-page .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .document-page .stat-card-item {
            background: var(--doc-surface);
            border: 1px solid var(--doc-border);
            border-radius: 1rem;
            padding: 1.15rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--doc-shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .document-page .stat-card-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--doc-shadow-md);
        }

        .document-page .stat-icon-wrapper {
            width: 46px;
            height: 46px;
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .document-page .stat-info .stat-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--doc-text-main);
            line-height: 1.1;
        }

        .document-page .stat-info .stat-label {
            font-size: 0.775rem;
            font-weight: 600;
            color: var(--doc-text-muted);
            margin-top: 0.2rem;
        }

        /* Document Grid & Cards */
        .document-page .doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.25rem;
        }

        .document-page .doc-card {
            background: var(--doc-surface);
            border: 1px solid var(--doc-border);
            border-radius: 1.15rem;
            padding: 1.35rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            box-shadow: var(--doc-shadow-sm);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .document-page .doc-card:hover {
            transform: translateY(-4px);
            border-color: var(--doc-border-hover);
            box-shadow: var(--doc-shadow-hover);
        }

        .document-page .doc-card-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .document-page .doc-type-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
        }

        /* File Type Colors */
        .doc-type-icon.pdf { background: var(--doc-pdf-bg); color: var(--doc-pdf); }
        .doc-type-icon.word { background: var(--doc-word-bg); color: var(--doc-word); }
        .doc-type-icon.excel { background: var(--doc-excel-bg); color: var(--doc-excel); }
        .doc-type-icon.default { background: var(--doc-default-bg); color: var(--doc-default); }

        .document-page .doc-meta-top {
            flex: 1;
            min-width: 0;
        }

        .document-page .doc-badge-pill {
            display: inline-block;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.2rem 0.55rem;
            border-radius: 0.4rem;
            margin-bottom: 0.35rem;
        }

        .doc-badge-pill.pdf { background: var(--doc-pdf-bg); color: var(--doc-pdf); }
        .doc-badge-pill.word { background: var(--doc-word-bg); color: var(--doc-word); }
        .doc-badge-pill.excel { background: var(--doc-excel-bg); color: var(--doc-excel); }
        .doc-badge-pill.default { background: var(--doc-default-bg); color: var(--doc-default); }

        .document-page .doc-title {
            font-size: 0.975rem;
            font-weight: 700;
            color: var(--doc-text-main);
            margin: 0;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .document-page .doc-desc {
            font-size: 0.85rem;
            color: var(--doc-text-sub);
            line-height: 1.5;
            margin-bottom: 1.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .document-page .doc-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding-top: 0.85rem;
            border-top: 1px dashed var(--doc-border);
            margin-top: auto;
        }

        .document-page .doc-file-info {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.775rem;
            font-weight: 600;
            color: var(--doc-text-muted);
        }

        .document-page .doc-card-actions {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }






        /* Empty State */
        .document-page .empty-doc-state {
            grid-column: 1 / -1;
            background: var(--doc-surface);
            border: 2px dashed var(--doc-border);
            border-radius: 1.25rem;
            padding: 3.5rem 1.5rem;
            text-align: center;
        }

        .document-page .empty-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--doc-primary-soft);
            color: var(--doc-primary);
            font-size: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        /* Access Role Badge Styles */
        .document-page .access-role-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        .document-page .access-role-badge.is-all {
            background: rgba(47, 128, 237, 0.1);
            color: #2f80ed;
            border: 1px solid rgba(47, 128, 237, 0.25);
        }

        .document-page .access-role-badge.is-admin {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.25);
        }

        .document-page .access-role-badge.is-manager {
            background: rgba(217, 119, 6, 0.1);
            color: #d97706;
            border: 1px solid rgba(217, 119, 6, 0.25);
        }

        .document-page .access-role-badge.is-custom {
            background: rgba(22, 163, 74, 0.1);
            color: #16a34a;
            border: 1px solid rgba(22, 163, 74, 0.25);
        }

        /* Dark Mode Extensions */
        html.aps-dark .document-page {
            --doc-bg: #0f172a;
            --doc-surface: #111c31;
            --doc-border: #24324a;
            --doc-border-hover: #3b4d6b;
            --doc-text-main: #f1f5f9;
            --doc-text-sub: #cbd5e1;
            --doc-text-muted: #94a3b8;
            --doc-primary-soft: rgba(47, 128, 237, 0.25);
            
            --doc-pdf: #fca5a5;
            --doc-pdf-bg: rgba(220, 38, 38, 0.28);
            --doc-word: #93c5fd;
            --doc-word-bg: rgba(47, 128, 237, 0.28);
            --doc-excel: #86efac;
            --doc-excel-bg: rgba(22, 163, 74, 0.28);
            --doc-default: #c7d2fe;
            --doc-default-bg: rgba(79, 70, 229, 0.28);
        }

        html.aps-dark .document-page .hero-banner {
            background: linear-gradient(135deg, #193863 0%, #1c5296 52%, #143e75 100%) !important;
            border: 1px solid #24324a;
            box-shadow: 0 14px 36px rgba(0, 0, 0, 0.4) !important;
        }

        html.aps-dark .document-page .filter-bar {
            background: #111c31 !important;
            border-color: #24324a !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        }

        html.aps-dark .document-page .search-box input {
            background: #0b1324 !important;
            border-color: #24324a !important;
            color: #f1f5f9 !important;
        }

        html.aps-dark .document-page .search-box input::placeholder {
            color: #64748b !important;
        }

        html.aps-dark .document-page .doc-counter-badge {
            background: rgba(47, 128, 237, 0.22) !important;
            color: #93c5fd !important;
            border-color: rgba(147, 197, 253, 0.3) !important;
        }

        html.aps-dark .document-page .stat-card-item {
            background: #111c31 !important;
            border-color: #24324a !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        }

        html.aps-dark .document-page .stat-card-item:hover {
            border-color: #3b4d6b !important;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.35) !important;
        }

        html.aps-dark .document-page .stat-info .stat-value {
            color: #f8fafc !important;
        }

        html.aps-dark .document-page .stat-info .stat-label {
            color: #94a3b8 !important;
        }

        html.aps-dark .document-page .doc-card {
            background: #111c31 !important;
            border-color: #24324a !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25) !important;
        }

        html.aps-dark .document-page .doc-card:hover {
            border-color: #3b4d6b !important;
            box-shadow: 0 14px 32px rgba(0, 0, 0, 0.45) !important;
        }

        html.aps-dark .document-page .doc-title {
            color: #f8fafc !important;
        }

        html.aps-dark .document-page .doc-desc {
            color: #cbd5e1 !important;
        }

        html.aps-dark .document-page .doc-file-info {
            color: #94a3b8 !important;
        }

        html.aps-dark .document-page .doc-badge-pill {
            font-weight: 800 !important;
        }

        html.aps-dark .document-page .doc-badge-pill.pdf {
            background: rgba(220, 38, 38, 0.28) !important;
            color: #fca5a5 !important;
        }

        html.aps-dark .document-page .doc-badge-pill.word {
            background: rgba(47, 128, 237, 0.28) !important;
            color: #93c5fd !important;
        }

        html.aps-dark .document-page .doc-badge-pill.excel {
            background: rgba(22, 163, 74, 0.28) !important;
            color: #86efac !important;
        }

        html.aps-dark .document-page .doc-badge-pill.default {
            background: rgba(79, 70, 229, 0.28) !important;
            color: #c7d2fe !important;
        }

        html.aps-dark .document-page .doc-type-icon.pdf {
            background: rgba(220, 38, 38, 0.25) !important;
            color: #fca5a5 !important;
        }

        html.aps-dark .document-page .doc-type-icon.word {
            background: rgba(47, 128, 237, 0.25) !important;
            color: #93c5fd !important;
        }

        html.aps-dark .document-page .doc-type-icon.excel {
            background: rgba(22, 163, 74, 0.25) !important;
            color: #86efac !important;
        }

        html.aps-dark .document-page .doc-type-icon.default {
            background: rgba(79, 70, 229, 0.25) !important;
            color: #c7d2fe !important;
        }

        html.aps-dark .document-page .access-role-badge.is-all {
            background: rgba(47, 128, 237, 0.25) !important;
            color: #93c5fd !important;
            border-color: rgba(147, 197, 253, 0.35) !important;
        }

        html.aps-dark .document-page .access-role-badge.is-admin {
            background: rgba(220, 38, 38, 0.25) !important;
            color: #fca5a5 !important;
            border-color: rgba(252, 165, 165, 0.35) !important;
        }

        html.aps-dark .document-page .access-role-badge.is-manager {
            background: rgba(217, 119, 6, 0.25) !important;
            color: #fde047 !important;
            border-color: rgba(253, 224, 71, 0.35) !important;
        }

        html.aps-dark .document-page .access-role-badge.is-custom {
            background: rgba(22, 163, 74, 0.25) !important;
            color: #86efac !important;
            border-color: rgba(134, 239, 172, 0.35) !important;
        }



        html.aps-dark .document-page .empty-doc-state {
            background: #111c31 !important;
            border-color: #24324a !important;
        }


        html.aps-dark .document-page .empty-doc-state h4 {
            color: #f8fafc !important;
        }

        html.aps-dark .document-page .empty-doc-state p {
            color: #94a3b8 !important;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y document-page">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-1 mb-4 pt-3 pb-1">
            <div>
                <h4 class="fw-bold mb-1">Dokumen & Panduan</h4>
                <p class="text-muted mb-0" style="font-size:0.875rem;">Daftar formulir, kebijakan, panduan kerja, dan laporan resmi perusahaan.</p>
            </div>
        </div>

        {{-- Statistics Overview --}}
        <div class="stats-row">
            <div class="stat-card-item">
                <div class="stat-icon-wrapper" style="background: rgba(47, 128, 237, 0.1); color: #2f80ed;">
                    <i class="bx bx-file"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ $totalDocuments }}</div>
                    <div class="stat-label">Total Dokumen</div>
                </div>
            </div>

            <div class="stat-card-item">
                <div class="stat-icon-wrapper" style="background: rgba(22, 163, 74, 0.1); color: #16a34a;">
                    <i class="bx bx-group"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ $allRoleDocuments }}</div>
                    <div class="stat-label">Semua Role Staff</div>
                </div>
            </div>

            <div class="stat-card-item">
                <div class="stat-icon-wrapper" style="background: rgba(217, 119, 6, 0.1); color: #d97706;">
                    <i class="bx bx-briefcase"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ $managerDocuments }}</div>
                    <div class="stat-label">Khusus Manager</div>
                </div>
            </div>

            @if (Auth::user()?->role === 'Admin')
            <div class="stat-card-item">
                <div class="stat-icon-wrapper" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                    <i class="bx bx-shield-quarter"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ $adminDocuments }}</div>
                    <div class="stat-label">Khusus Admin</div>
                </div>
            </div>
            @endif
        </div>

        {{-- Live Search & Counter Bar --}}
        {{-- Live Search & Counter Bar --}}
        <div class="filter-bar">
            <div class="search-box">
                <i class="bx bx-search"></i>
                <input type="text" id="docSearchInput" placeholder="Cari nama atau deskripsi dokumen..." onkeyup="filterDocuments()">
            </div>
            <div class="d-flex align-items-center gap-2">
                @if ($canManage)
                    <a href="{{ route('admin.documents.create') }}" class="btn btn-sm btn-primary fw-bold px-3.5 py-2 rounded-3 shadow-sm">
                        <i class="bx bx-plus me-1"></i>Tambah Dokumen
                    </a>
                @endif
                <div class="doc-counter-badge">
                    <i class="bx bx-folder"></i>
                    <span id="visibleDocCount">{{ count($visibleDocuments) }}</span> Dokumen Tersedia
                </div>
            </div>
        </div>

        {{-- Document Grid List --}}
        <div class="doc-grid" id="documentGrid">
            @forelse ($visibleDocuments as $document)
                @php
                    $fileExt = strtolower(pathinfo($document->file_path ?? '', PATHINFO_EXTENSION));
                    if (empty($fileExt) && !empty($document->ukuran_file)) {
                        if (str_contains(strtolower($document->ukuran_file), 'pdf')) $fileExt = 'pdf';
                    }
                    
                    $typeClass = 'default';
                    $typeLabel = 'DOKUMEN';
                    $iconClass = 'bx bx-file';
                    
                    if (in_array($fileExt, ['pdf'])) {
                        $typeClass = 'pdf';
                        $typeLabel = 'PDF DOCUMENT';
                        $iconClass = 'bxs-file-pdf';
                    } elseif (in_array($fileExt, ['doc', 'docx'])) {
                        $typeClass = 'word';
                        $typeLabel = 'WORD DOCUMENT';
                        $iconClass = 'bxs-file-doc';
                    } elseif (in_array($fileExt, ['xls', 'xlsx', 'csv'])) {
                        $typeClass = 'excel';
                        $typeLabel = 'EXCEL SPREADSHEET';
                        $iconClass = 'bxs-file-json';
                    }

                    $accessLabel = 'Semua Role';
                    $accessBadgeClass = 'is-all';

                    if ($document->isAllRoleAccess()) {
                        $accessLabel = 'Semua Role';
                        $accessBadgeClass = 'is-all';
                    } elseif ($document->hasRoleAccess('Admin') && count($document->role_access_values) === 1) {
                        $accessLabel = 'Khusus Admin';
                        $accessBadgeClass = 'is-admin';
                    } elseif ($document->hasAnyRoleAccess(\App\Models\Document::managerRoles())) {
                        $accessLabel = 'Khusus Manager';
                        $accessBadgeClass = 'is-manager';
                    } elseif (!empty($document->role_access_values)) {
                        $accessLabel = implode(', ', array_map(fn($r) => ucfirst($r), $document->role_access_values));
                        $accessBadgeClass = 'is-custom';
                    }
                @endphp
                
                <div class="doc-card doc-item-card" data-title="{{ strtolower($document->nama_dokumen) }}" data-desc="{{ strtolower($document->deskripsi_dokumen) }}">
                    <div>
                        <div class="doc-card-header">
                            <div class="doc-type-icon {{ $typeClass }}">
                                <i class="bx {{ $iconClass }}"></i>
                            </div>
                            <div class="doc-meta-top">
                                <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                    <span class="doc-badge-pill {{ $typeClass }} mb-0">{{ $typeLabel }}</span>
                                    <span class="access-role-badge {{ $accessBadgeClass }}"><i class="bx bx-user-check"></i>{{ $accessLabel }}</span>
                                </div>
                                <h3 class="doc-title">{{ $document->nama_dokumen }}</h3>
                            </div>
                        </div>
                        <p class="doc-desc">{{ $document->deskripsi_dokumen ?: 'Dokumen resmi PT Angkasa Pratama Sejahtera.' }}</p>
                    </div>

                    <div class="doc-card-footer">
                        <div class="doc-file-info">
                            <i class="bx bx-hdd"></i>
                            <span>{{ $document->ukuran_file ?: 'File Resmi' }}</span>
                        </div>
                        <div class="doc-card-actions d-flex gap-1">
                            <x-action-button action="download" :href="route('document.download', $document)" title="Unduh Dokumen" aria-label="Unduh {{ $document->nama_dokumen }}" />
                            @if ($canManage)
                                <x-action-button action="edit" :href="route('admin.documents.edit', $document)" title="Edit Dokumen" />
                                <form action="{{ route('admin.documents.destroy', $document) }}" method="POST" class="d-inline delete-document-form">
                                    @csrf
                                    @method('DELETE')
                                    <x-action-button type="button" action="delete" class="btn-delete-doc" title="Hapus Dokumen" />
                                </form>
                            @endif
                        </div>



                    </div>
                </div>
            @empty
                <div class="empty-doc-state">
                    <div class="empty-icon">
                        <i class="bx bx-folder-open"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Belum Ada Dokumen</h4>
                    <p class="text-muted mb-0">Dokumen untuk kategori role Anda belum diunggah oleh Administrator.</p>
                </div>
            @endforelse
            
            {{-- Search Not Found State --}}
            <div class="empty-doc-state d-none" id="noSearchResultsState">
                <div class="empty-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                    <i class="bx bx-search-alt"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Dokumen Tidak Ditemukan</h4>
                <p class="text-muted mb-0">Tidak ada dokumen yang cocok dengan kata kunci pencarian Anda.</p>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script>
        function filterDocuments() {
            const query = document.getElementById('docSearchInput').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.doc-item-card');
            const noResultsState = document.getElementById('noSearchResultsState');
            let visibleCount = 0;

            cards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const desc = card.getAttribute('data-desc') || '';

                if (title.includes(query) || desc.includes(query)) {
                    card.classList.remove('d-none');
                    visibleCount++;
                } else {
                    card.classList.add('d-none');
                }
            });

            const countBadge = document.getElementById('visibleDocCount');
            if (countBadge) {
                countBadge.textContent = visibleCount;
            }

            if (visibleCount === 0 && cards.length > 0) {
                noResultsState.classList.remove('d-none');
            } else {
                noResultsState.classList.add('d-none');
            }
        }

        $(document).ready(function() {
            // Delete Confirmation
            $(document).on('click', '.btn-delete-doc', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Hapus Dokumen?',
                    text: 'Dokumen yang dihapus tidak dapat dikembalikan lagi.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection


