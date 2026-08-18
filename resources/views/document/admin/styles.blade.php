<style>
    /* ── Role Chips ─────────────────────────────────────────── */
    .document-admin-page .role-chips-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        align-items: center;
    }

    .document-admin-page .role-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.28rem 0.6rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        line-height: 1.3;
        white-space: nowrap;
        letter-spacing: 0.01em;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .document-admin-page .role-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    /* Semua Role */
    .document-admin-page .role-chip--all {
        background: linear-gradient(135deg, rgba(47, 128, 237, 0.14), rgba(47, 128, 237, 0.08));
        color: #2368c8;
        border: 1px solid rgba(47, 128, 237, 0.25);
    }

    /* Admin */
    .document-admin-page .role-chip--is-admin {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.12), rgba(220, 53, 69, 0.06));
        color: #c0392b;
        border: 1px solid rgba(220, 53, 69, 0.22);
    }

    /* Manager / Head */
    .document-admin-page .role-chip--is-manager {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.14), rgba(245, 158, 11, 0.07));
        color: #92650a;
        border: 1px solid rgba(245, 158, 11, 0.26);
    }

    /* Staff Admin */
    .document-admin-page .role-chip--is-staff-admin {
        background: linear-gradient(135deg, rgba(22, 161, 99, 0.13), rgba(22, 161, 99, 0.06));
        color: #127a4e;
        border: 1px solid rgba(22, 161, 99, 0.24);
    }

    /* +N more chip */
    .document-admin-page .role-chip--more {
        background: rgba(100, 116, 139, 0.1);
        color: #64748b;
        border: 1px solid rgba(100, 116, 139, 0.22);
        cursor: pointer;
    }

    .document-admin-page .role-chip--more:hover {
        background: rgba(100, 116, 139, 0.18);
        color: #475569;
    }

    /* Tooltip untuk daftar role lengkap */
    .role-tooltip-list {
        text-align: left;
        font-size: 0.75rem;
        line-height: 1.7;
    }

    .document-admin-page .document-file-summary {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
    }

    .document-admin-page .document-file-summary-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        flex: 0 0 auto;
        border-radius: 999px;
        background: linear-gradient(135deg, #2f80ed, #2368c8);
        color: #fff;
        font-size: 1.15rem;
    }

    .document-admin-page .min-w-0 {
        min-width: 0;
    }

    .document-admin-page .document-role-picker {
        padding: 0.85rem;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .document-admin-page .document-role-picker.is-invalid {
        border-color: #ff3e1d;
    }

    .document-admin-page .document-role-search {
        position: relative;
        margin: 0.7rem 0;
    }

    .document-admin-page .document-role-search i {
        position: absolute;
        top: 50%;
        left: 0.9rem;
        z-index: 1;
        color: #718096;
        transform: translateY(-50%);
    }

    .document-admin-page .document-role-search .form-control {
        padding-left: 2.5rem !important;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .document-admin-page .document-role-list,
    .document-admin-page .document-role-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 0.5rem;
        max-height: 260px;
        overflow-y: auto;
        padding-right: 0.25rem;
    }

    .document-admin-page .document-role-option {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        min-height: 40px;
        margin: 0;
        padding: 0.58rem 0.7rem;
        border-radius: 12px;
        color: #28364a;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
    }

    .document-admin-page .document-role-option:hover {
        background: #edf5ff;
        border-color: #dbeafe;
    }

    .document-admin-page .document-role-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .document-admin-page .document-role-check {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        flex: 0 0 auto;
        border: 1px solid #c9d8eb;
        border-radius: 999px;
        color: transparent;
        font-size: 0.82rem;
    }

    .document-admin-page .document-role-option input:checked + .document-role-check {
        border-color: #2f80ed;
        background: #2f80ed;
        color: #fff;
    }

    .document-admin-page .document-role-option:has(input:checked) {
        background: #e8f2ff;
        color: #2368c8;
        font-weight: 700;
        border-color: #bfdbfe;
    }

    .document-admin-page .document-role-option.is-hidden {
        display: none;
    }

    html.aps-dark .document-admin-page .document-file-summary {
        border-color: #2b3b5a;
        background: #172339 !important;
    }

    html.aps-dark .document-admin-page .document-role-picker {
        border-color: #2b3b5a !important;
        background: #172339 !important;
    }

    html.aps-dark .document-admin-page .document-role-search .form-control {
        background: #0f172a !important;
        border-color: #2b3b5a !important;
        color: #e2e8f0 !important;
    }

    html.aps-dark .document-admin-page input[type="file"] {
        background-color: #0f172a !important;
        border-color: #2b3b5a !important;
        color: #cbd5e1 !important;
    }

    html.aps-dark .document-admin-page input[type="file"]::file-selector-button,
    html.aps-dark .document-admin-page input[type="file"]::-webkit-file-upload-button {
        background-color: #2b3b5a !important;
        color: #f1f5f9 !important;
        border-color: #3b4d71 !important;
    }

    html.aps-dark .document-admin-page input[type="file"]::file-selector-button:hover,
    html.aps-dark .document-admin-page input[type="file"]::-webkit-file-upload-button:hover {
        background-color: #3b4d71 !important;
        color: #ffffff !important;
    }

    html.aps-dark .document-admin-page .document-role-option {
        color: #e2e8f0;
        background: #0f172a;
        border-color: #1e293b;
    }

    html.aps-dark .document-admin-page .document-role-option:hover {
        background: #1e293b;
        color: #60a5fa;
    }

    html.aps-dark .document-admin-page .document-role-option:has(input:checked) {
        background: rgba(47, 128, 237, 0.25) !important;
        color: #60a5fa !important;
        border-color: rgba(47, 128, 237, 0.4) !important;
    }

    html.aps-dark .document-admin-page .document-role-check {
        border-color: #315071;
    }

    html.aps-dark .document-admin-page .role-chip--all {
        background: rgba(47, 128, 237, 0.18);
        color: #8fc1ff;
        border-color: rgba(47, 128, 237, 0.3);
    }

    html.aps-dark .document-admin-page .role-chip--is-admin {
        background: rgba(239, 68, 68, 0.15);
        color: #fb7185;
        border-color: rgba(239, 68, 68, 0.28);
    }

    html.aps-dark .document-admin-page .role-chip--is-manager {
        background: rgba(245, 158, 11, 0.15);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.28);
    }

    html.aps-dark .document-admin-page .role-chip--is-staff-admin {
        background: rgba(34, 197, 94, 0.13);
        color: #6ee7a8;
        border-color: rgba(34, 197, 94, 0.26);
    }

    html.aps-dark .document-admin-page .role-chip--more {
        background: rgba(148, 163, 184, 0.1);
        color: #94a3b8;
        border-color: rgba(148, 163, 184, 0.22);
    }
</style>
