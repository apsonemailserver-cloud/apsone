@extends('layout.admin')

@section('styles')
<style>
/* ══════════════════════════════════════════════════════
   AP3 BLUE PRIMARY THEME ENGINE FOR ORGANIGRAMME
══════════════════════════════════════════════════════ */

/* LIGHT MODE CANVAS */
.holi-canvas {
    background-color: #f8fafc !important;
    background-image: radial-gradient(circle, #cbd5e1 1.2px, transparent 1.2px) !important;
    background-size: 22px 22px !important;
    padding: 35px 40px 80px !important;
    overflow: auto !important;
    min-height: 680px !important;
    position: relative !important;
    transition: background-color 0.3s ease !important;
}

/* DARK MODE CANVAS */
html.dark-style .holi-canvas,
html.aps-dark .holi-canvas,
html[data-aps-theme="dark"] .holi-canvas,
.dark-style .holi-canvas,
.aps-dark .holi-canvas {
    background-color: #0f172a !important;
    background-image: radial-gradient(circle, #334155 1.2px, transparent 1.2px) !important;
}

/* FULLSCREEN CANVAS MODE */
.organigramme-card-container.is-fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 99999 !important;
    border-radius: 0 !important;
    margin: 0 !important;
}

.organigramme-card-container.is-fullscreen .holi-canvas {
    height: 100vh !important;
    min-height: 100vh !important;
}

/* TREE ROOT - ALWAYS HORIZONTALLY CENTERED */
.org-tree-root {
    display: flex !important;
    flex-direction: row !important;
    justify-content: center !important;
    align-items: flex-start !important;
    gap: 36px !important;
    margin: 0 auto !important;
    padding: 0 !important;
    width: max-content !important;
    min-width: 100% !important;
}

/* NODE WRAPPER */
.org-node-wrapper {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    position: relative !important;
    padding: 0 14px !important;
    margin: 0 !important;
    width: auto !important;
    flex-shrink: 0 !important;
}

/* NODE CARD ITEM - LIGHT MODE */
.org-card-item {
    width: 220px !important;
    min-width: 220px !important;
    max-width: 220px !important;
    background: #ffffff !important;
    border: 1.5px solid #dbeafe !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important;
    overflow: hidden !important;
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease !important;
    position: relative !important;
    z-index: 2 !important;
}

.org-card-item:hover {
    border-color: #2563eb !important;
    box-shadow: 0 15px 30px -5px rgba(37, 99, 235, 0.22) !important;
    transform: translateY(-3px) !important;
}

/* NODE CARD ITEM - DARK MODE */
html.dark-style .org-card-item,
html.aps-dark .org-card-item,
html[data-aps-theme="dark"] .org-card-item,
.dark-style .org-card-item,
.aps-dark .org-card-item {
    background: #1e293b !important;
    border: 1.5px solid #334155 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
}

html.dark-style .org-card-item:hover,
html.aps-dark .org-card-item:hover,
html[data-aps-theme="dark"] .org-card-item:hover,
.dark-style .org-card-item:hover,
.aps-dark .org-card-item:hover {
    border-color: #3b82f6 !important;
    box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.3) !important;
}

/* CARD CONTENT */
.org-card-content {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    padding: 12px 12px 8px 12px !important;
}

/* AVATAR BOX - CIRCULAR AVATAR (BULET PERFEKT) */
.org-avatar-box {
    width: 40px !important;
    height: 40px !important;
    min-width: 40px !important;
    min-height: 40px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
    border: 1.5px solid #bfdbfe !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    overflow: hidden !important;
}

.org-avatar-text {
    font-size: 14px !important;
    font-weight: 700 !important;
    color: #1d4ed8 !important;
    line-height: 1 !important;
}

html.dark-style .org-avatar-box,
html.aps-dark .org-avatar-box,
html[data-aps-theme="dark"] .org-avatar-box,
.dark-style .org-avatar-box,
.aps-dark .org-avatar-box {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%) !important;
    border-color: #3b82f6 !important;
}

html.dark-style .org-avatar-text,
html.aps-dark .org-avatar-text,
html[data-aps-theme="dark"] .org-avatar-text,
.dark-style .org-avatar-text,
.aps-dark .org-avatar-text {
    color: #ffffff !important;
}

.org-avatar-img {
    width: 100% !important;
    height: 100% !important;
    border-radius: 50% !important;
    object-fit: cover !important;
}

/* INFO BOX */
.org-info-box {
    flex: 1 !important;
    overflow: hidden !important;
    text-align: left !important;
}

.org-name-text {
    display: block !important;
    font-size: 0.825rem !important;
    font-weight: 700 !important;
    color: #0f172a !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    line-height: 1.25 !important;
}

html.dark-style .org-name-text,
html.aps-dark .org-name-text,
html[data-aps-theme="dark"] .org-name-text,
.dark-style .org-name-text,
.aps-dark .org-name-text {
    color: #f8fafc !important;
}

.org-role-text {
    display: block !important;
    font-size: 0.7rem !important;
    color: #475569 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    line-height: 1.3 !important;
    margin-top: 2px !important;
}

html.dark-style .org-role-text,
html.aps-dark .org-role-text,
html[data-aps-theme="dark"] .org-role-text,
.dark-style .org-role-text,
.aps-dark .org-role-text {
    color: #cbd5e1 !important;
}

.org-nip-text {
    display: block !important;
    font-size: 0.65rem !important;
    color: #2563eb !important;
    font-family: monospace !important;
    font-weight: 600 !important;
    margin-top: 2px !important;
}

html.dark-style .org-nip-text,
html.aps-dark .org-nip-text,
html[data-aps-theme="dark"] .org-nip-text,
.dark-style .org-nip-text,
.aps-dark .org-nip-text {
    color: #60a5fa !important;
}

/* CARD FOOTER */
.org-card-footer {
    border-top: 1px solid #f1f5f9 !important;
    padding: 4px 8px !important;
    text-align: center !important;
    background: #fafafa !important;
}

html.dark-style .org-card-footer,
html.aps-dark .org-card-footer,
html[data-aps-theme="dark"] .org-card-footer,
.dark-style .org-card-footer,
.aps-dark .org-card-footer {
    border-color: #334155 !important;
    background: #0f172a !important;
}

.org-details-btn {
    font-size: 0.7rem !important;
    color: #2563eb !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    transition: color 0.2s ease !important;
}

html.dark-style .org-details-btn,
html.aps-dark .org-details-btn,
html[data-aps-theme="dark"] .org-details-btn,
.dark-style .org-details-btn,
.aps-dark .org-details-btn {
    color: #38bdf8 !important;
}

.org-details-btn:hover {
    color: #1d4ed8 !important;
    text-decoration: underline !important;
}

html.dark-style .org-details-btn:hover,
html.aps-dark .org-details-btn:hover,
html[data-aps-theme="dark"] .org-details-btn:hover,
.dark-style .org-details-btn:hover,
.aps-dark .org-details-btn:hover {
    color: #7dd3fc !important;
}

/* CONNECTING STEM DOWN */
.org-stem-down {
    width: 2px !important;
    height: 32px !important;
    background-color: #2563eb !important;
    margin: 0 auto !important;
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 3 !important;
}

html.dark-style .org-stem-down,
html.aps-dark .org-stem-down,
html[data-aps-theme="dark"] .org-stem-down,
.dark-style .org-stem-down,
.aps-dark .org-stem-down {
    background-color: #3b82f6 !important;
}

/* CIRCULAR SUBORDINATE BADGE (STRICT PERFECT CIRCLE) */
.org-circle-badge {
    width: 28px !important;
    min-width: 28px !important;
    max-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    max-height: 28px !important;
    border-radius: 50% !important;
    background-color: #2563eb !important;
    color: #ffffff !important;
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 1px !important;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.4) !important;
    cursor: pointer !important;
    flex-shrink: 0 !important;
    transition: all 0.2s ease !important;
    line-height: 1 !important;
}

html.dark-style .org-circle-badge,
html.aps-dark .org-circle-badge,
html[data-aps-theme="dark"] .org-circle-badge,
.dark-style .org-circle-badge,
.aps-dark .org-circle-badge {
    background-color: #3b82f6 !important;
    box-shadow: 0 0 12px rgba(59, 130, 246, 0.6) !important;
}

.org-circle-badge:hover {
    transform: scale(1.15) !important;
    background-color: #1d4ed8 !important;
}

.org-circle-badge.collapsed {
    background-color: #64748b !important;
    box-shadow: 0 4px 10px rgba(100, 116, 139, 0.4) !important;
}

html.dark-style .org-circle-badge.collapsed,
html.aps-dark .org-circle-badge.collapsed,
html[data-aps-theme="dark"] .org-circle-badge.collapsed,
.dark-style .org-circle-badge.collapsed,
.aps-dark .org-circle-badge.collapsed {
    background-color: #475569 !important;
    box-shadow: 0 0 8px rgba(71, 85, 105, 0.5) !important;
}

/* CHILDREN CONTAINER */
.org-children-container {
    display: flex !important;
    flex-direction: row !important;
    justify-content: center !important;
    align-items: flex-start !important;
    position: relative !important;
    padding-top: 24px !important;
    margin: 0 !important;
    width: auto !important;
    transition: all 0.3s ease !important;
}

.org-children-container.org-hidden {
    display: none !important;
}

/* HORIZONTAL CONNECTOR LINE */
.org-children-container::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    height: 2px !important;
    background-color: #2563eb !important;
}

html.dark-style .org-children-container::before,
html.aps-dark .org-children-container::before,
html[data-aps-theme="dark"] .org-children-container::before,
.dark-style .org-children-container::before,
.aps-dark .org-children-container::before {
    background-color: #3b82f6 !important;
}

/* VERTICAL LINE INTO EACH CHILD */
.org-children-container > .org-node-wrapper::before {
    content: '' !important;
    position: absolute !important;
    top: -24px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    width: 2px !important;
    height: 24px !important;
    background-color: #2563eb !important;
}

html.dark-style .org-children-container > .org-node-wrapper::before,
html.aps-dark .org-children-container > .org-node-wrapper::before,
html[data-aps-theme="dark"] .org-children-container > .org-node-wrapper::before,
.dark-style .org-children-container > .org-node-wrapper::before,
.aps-dark .org-children-container > .org-node-wrapper::before {
    background-color: #3b82f6 !important;
}

/* TRIM HORIZONTAL LINES FOR FIRST AND LAST CHILD */
.org-children-container > .org-node-wrapper:first-child::after {
    content: '' !important;
    position: absolute !important;
    top: -24px !important;
    left: 0 !important;
    width: 50% !important;
    height: 2px !important;
    background-color: #f8fafc !important;
}

html.dark-style .org-children-container > .org-node-wrapper:first-child::after,
html.aps-dark .org-children-container > .org-node-wrapper:first-child::after,
html[data-aps-theme="dark"] .org-children-container > .org-node-wrapper:first-child::after,
.dark-style .org-children-container > .org-node-wrapper:first-child::after,
.aps-dark .org-children-container > .org-node-wrapper:first-child::after {
    background-color: #0f172a !important;
}

.org-children-container > .org-node-wrapper:last-child::after {
    content: '' !important;
    position: absolute !important;
    top: -24px !important;
    right: 0 !important;
    width: 50% !important;
    height: 2px !important;
    background-color: #f8fafc !important;
}

html.dark-style .org-children-container > .org-node-wrapper:last-child::after,
html.aps-dark .org-children-container > .org-node-wrapper:last-child::after,
html[data-aps-theme="dark"] .org-children-container > .org-node-wrapper:last-child::after,
.dark-style .org-children-container > .org-node-wrapper:last-child::after,
.aps-dark .org-children-container > .org-node-wrapper:last-child::after {
    background-color: #0f172a !important;
}

.org-children-container > .org-node-wrapper:only-child::after {
    display: none !important;
}

.org-children-container:has(> .org-node-wrapper:only-child)::before {
    display: none !important;
}

/* ULTRA COMPACT FLOATING CONTROL BAR */
.holi-floating-bar-wrapper {
    position: absolute !important;
    bottom: 14px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    z-index: 100 !important;
}

.holi-floating-bar {
    background: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    color: #1e293b !important;
    padding: 3px 8px !important;
    border-radius: 9999px !important;
    box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.12), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
    white-space: nowrap !important;
}

.holi-floating-bar-pills {
    background: #f1f5f9 !important;
    padding: 2px !important;
    border-radius: 9999px !important;
    display: flex !important;
    align-items: center !important;
    gap: 2px !important;
}

.holi-floating-bar-pills .btn-inactive {
    color: #64748b !important;
    background: transparent !important;
    border: none !important;
    text-decoration: none !important;
}

.holi-floating-bar-pills .btn-inactive:hover {
    color: #0f172a !important;
}

.holi-floating-bar .btn {
    white-space: nowrap !important;
    font-size: 0.675rem !important; /* 10.8px compact */
    padding: 2.5px 8px !important;
    line-height: 1.3 !important;
    border-radius: 9999px !important;
}

.holi-floating-bar-divider {
    width: 1px !important;
    height: 14px !important;
    background-color: #cbd5e1 !important;
}

.holi-btn-secondary {
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    color: #334155 !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
}

.holi-btn-secondary:hover {
    background: #f8fafc !important;
    border-color: #94a3b8 !important;
    color: #0f172a !important;
}

/* FLOATING CONTROL BAR - DARK MODE OVERRIDES */
html.dark-style .holi-floating-bar,
html.aps-dark .holi-floating-bar,
html[data-aps-theme="dark"] .holi-floating-bar,
.dark-style .holi-floating-bar,
.aps-dark .holi-floating-bar {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #ffffff !important;
    box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.5) !important;
}

html.dark-style .holi-floating-bar-pills,
html.aps-dark .holi-floating-bar-pills,
html[data-aps-theme="dark"] .holi-floating-bar-pills,
.dark-style .holi-floating-bar-pills,
.aps-dark .holi-floating-bar-pills {
    background: #0f172a !important;
}

html.dark-style .holi-floating-bar-pills .btn-inactive,
html.aps-dark .holi-floating-bar-pills .btn-inactive,
html[data-aps-theme="dark"] .holi-floating-bar-pills .btn-inactive,
.dark-style .holi-floating-bar-pills .btn-inactive,
.aps-dark .holi-floating-bar-pills .btn-inactive {
    color: #94a3b8 !important;
}

html.dark-style .holi-floating-bar-pills .btn-inactive:hover,
html.aps-dark .holi-floating-bar-pills .btn-inactive:hover,
html[data-aps-theme="dark"] .holi-floating-bar-pills .btn-inactive:hover,
.dark-style .holi-floating-bar-pills .btn-inactive:hover,
.aps-dark .holi-floating-bar-pills .btn-inactive:hover {
    color: #ffffff !important;
}

html.dark-style .holi-floating-bar-divider,
html.aps-dark .holi-floating-bar-divider,
html[data-aps-theme="dark"] .holi-floating-bar-divider,
.dark-style .holi-floating-bar-divider,
.aps-dark .holi-floating-bar-divider {
    background-color: #334155 !important;
}

html.dark-style .holi-btn-secondary,
html.aps-dark .holi-btn-secondary,
html[data-aps-theme="dark"] .holi-btn-secondary,
.dark-style .holi-btn-secondary,
.aps-dark .holi-btn-secondary {
    background: #0f172a !important;
    border-color: #475569 !important;
    color: #e2e8f0 !important;
}

html.dark-style .holi-btn-secondary:hover,
html.aps-dark .holi-btn-secondary:hover,
html[data-aps-theme="dark"] .holi-btn-secondary:hover,
.dark-style .holi-btn-secondary:hover,
.aps-dark .holi-btn-secondary:hover {
    background: #334155 !important;
    border-color: #64748b !important;
    color: #ffffff !important;
}
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Inline Style Fallback for PJAX / Dynamic Loader --}}
    <style>
    .holi-canvas { background-color: #f8fafc !important; background-image: radial-gradient(circle, #cbd5e1 1.2px, transparent 1.2px) !important; background-size: 22px 22px !important; padding: 35px 40px 80px !important; overflow: auto !important; min-height: 680px !important; position: relative !important; }
    html.dark-style .holi-canvas, html.aps-dark .holi-canvas, html[data-aps-theme="dark"] .holi-canvas, .dark-style .holi-canvas, .aps-dark .holi-canvas { background-color: #0f172a !important; background-image: radial-gradient(circle, #334155 1.2px, transparent 1.2px) !important; }
    .organigramme-card-container.is-fullscreen { position: fixed !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 99999 !important; border-radius: 0 !important; margin: 0 !important; }
    .organigramme-card-container.is-fullscreen .holi-canvas { height: 100vh !important; min-height: 100vh !important; }
    .org-tree-root { display: flex !important; flex-direction: row !important; justify-content: center !important; align-items: flex-start !important; gap: 36px !important; margin: 0 auto !important; padding: 0 !important; width: max-content !important; min-width: 100% !important; }
    .org-node-wrapper { display: flex !important; flex-direction: column !important; align-items: center !important; position: relative !important; padding: 0 14px !important; margin: 0 !important; width: auto !important; flex-shrink: 0 !important; }
    .org-card-item { width: 220px !important; min-width: 220px !important; max-width: 220px !important; background: #ffffff !important; border: 1.5px solid #dbeafe !important; border-radius: 16px !important; box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important; overflow: hidden !important; transition: all 0.25s ease !important; position: relative !important; z-index: 2 !important; }
    html.dark-style .org-card-item, html.aps-dark .org-card-item, html[data-aps-theme="dark"] .org-card-item, .dark-style .org-card-item, .aps-dark .org-card-item { background: #1e293b !important; border: 1.5px solid #334155 !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important; }
    .org-card-item:hover { border-color: #2563eb !important; box-shadow: 0 15px 30px -5px rgba(37, 99, 235, 0.22) !important; transform: translateY(-3px) !important; }
    html.dark-style .org-card-item:hover, html.aps-dark .org-card-item:hover, html[data-aps-theme="dark"] .org-card-item:hover, .dark-style .org-card-item:hover, .aps-dark .org-card-item:hover { border-color: #3b82f6 !important; box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.3) !important; }
    .org-card-content { display: flex !important; align-items: center !important; gap: 10px !important; padding: 12px 12px 8px 12px !important; }
    .org-avatar-box { width: 40px !important; height: 40px !important; min-width: 40px !important; min-height: 40px !important; border-radius: 50% !important; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important; border: 1.5px solid #bfdbfe !important; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; overflow: hidden !important; }
    .org-avatar-text { font-size: 14px !important; font-weight: 700 !important; color: #1d4ed8 !important; line-height: 1 !important; }
    html.dark-style .org-avatar-box, html.aps-dark .org-avatar-box, html[data-aps-theme="dark"] .org-avatar-box, .dark-style .org-avatar-box, .aps-dark .org-avatar-box { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%) !important; border-color: #3b82f6 !important; }
    html.dark-style .org-avatar-text, html.aps-dark .org-avatar-text, html[data-aps-theme="dark"] .org-avatar-text, .dark-style .org-avatar-text, .aps-dark .org-avatar-text { color: #ffffff !important; }
    .org-avatar-img { width: 100% !important; height: 100% !important; border-radius: 50% !important; object-fit: cover !important; }
    .org-info-box { flex: 1 !important; overflow: hidden !important; text-align: left !important; }
    .org-name-text { display: block !important; font-size: 0.825rem !important; font-weight: 700 !important; color: #0f172a !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; line-height: 1.25 !important; }
    html.dark-style .org-name-text, html.aps-dark .org-name-text, html[data-aps-theme="dark"] .org-name-text, .dark-style .org-name-text, .aps-dark .org-name-text { color: #f8fafc !important; }
    .org-role-text { display: block !important; font-size: 0.7rem !important; color: #475569 !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important; line-height: 1.3 !important; margin-top: 2px !important; }
    html.dark-style .org-role-text, html.aps-dark .org-role-text, html[data-aps-theme="dark"] .org-role-text, .dark-style .org-role-text, .aps-dark .org-role-text { color: #cbd5e1 !important; }
    .org-nip-text { display: block !important; font-size: 0.65rem !important; color: #2563eb !important; font-family: monospace !important; font-weight: 600 !important; margin-top: 2px !important; }
    html.dark-style .org-nip-text, html.aps-dark .org-nip-text, html[data-aps-theme="dark"] .org-nip-text, .dark-style .org-nip-text, .aps-dark .org-nip-text { color: #60a5fa !important; }
    .org-card-footer { border-top: 1px solid #f1f5f9 !important; padding: 4px 8px !important; text-align: center !important; background: #fafafa !important; }
    html.dark-style .org-card-footer, html.aps-dark .org-card-footer, html[data-aps-theme="dark"] .org-card-footer, .dark-style .org-card-footer, .aps-dark .org-card-footer { border-color: #334155 !important; background: #0f172a !important; }
    .org-details-btn { font-size: 0.7rem !important; color: #2563eb !important; font-weight: 600 !important; text-decoration: none !important; transition: color 0.2s ease !important; }
    html.dark-style .org-details-btn, html.aps-dark .org-details-btn, html[data-aps-theme="dark"] .org-details-btn, .dark-style .org-details-btn, .aps-dark .org-details-btn { color: #38bdf8 !important; }
    .org-details-btn:hover { color: #1d4ed8 !important; text-decoration: underline !important; }
    html.dark-style .org-details-btn:hover, html.aps-dark .org-details-btn:hover, html[data-aps-theme="dark"] .org-details-btn:hover, .dark-style .org-details-btn:hover, .aps-dark .org-details-btn:hover { color: #7dd3fc !important; }
    .org-stem-down { width: 2px !important; height: 32px !important; background-color: #2563eb !important; margin: 0 auto !important; position: relative !important; display: flex !important; align-items: center !important; justify-content: center !important; z-index: 3 !important; }
    html.dark-style .org-stem-down, html.aps-dark .org-stem-down, html[data-aps-theme="dark"] .org-stem-down, .dark-style .org-stem-down, .aps-dark .org-stem-down { background-color: #3b82f6 !important; }
    .org-circle-badge { width: 28px !important; min-width: 28px !important; max-width: 28px !important; height: 28px !important; min-height: 28px !important; max-height: 28px !important; border-radius: 50% !important; background-color: #2563eb !important; color: #ffffff !important; font-size: 0.7rem !important; font-weight: 700 !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 1px !important; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.4) !important; cursor: pointer !important; flex-shrink: 0 !important; transition: all 0.2s ease !important; line-height: 1 !important; }
    html.dark-style .org-circle-badge, html.aps-dark .org-circle-badge, html[data-aps-theme="dark"] .org-circle-badge, .dark-style .org-circle-badge, .aps-dark .org-circle-badge { background-color: #3b82f6 !important; box-shadow: 0 0 12px rgba(59, 130, 246, 0.6) !important; }
    .org-circle-badge:hover { transform: scale(1.15) !important; background-color: #1d4ed8 !important; }
    .org-circle-badge.collapsed { background-color: #64748b !important; box-shadow: 0 4px 10px rgba(100, 116, 139, 0.4) !important; }
    html.dark-style .org-circle-badge.collapsed, html.aps-dark .org-circle-badge.collapsed, html[data-aps-theme="dark"] .org-circle-badge.collapsed, .dark-style .org-circle-badge.collapsed, .aps-dark .org-circle-badge.collapsed { background-color: #475569 !important; box-shadow: 0 0 8px rgba(71, 85, 105, 0.5) !important; }
    .org-children-container { display: flex !important; flex-direction: row !important; justify-content: center !important; align-items: flex-start !important; position: relative !important; padding-top: 24px !important; margin: 0 !important; width: auto !important; transition: all 0.3s ease !important; }
    .org-children-container.org-hidden { display: none !important; }
    .org-children-container::before { content: '' !important; position: absolute !important; top: 0 !important; left: 0 !important; right: 0 !important; height: 2px !important; background-color: #2563eb !important; }
    html.dark-style .org-children-container::before, html.aps-dark .org-children-container::before, html[data-aps-theme="dark"] .org-children-container::before, .dark-style .org-children-container::before, .aps-dark .org-children-container::before { background-color: #3b82f6 !important; }
    .org-children-container > .org-node-wrapper::before { content: '' !important; position: absolute !important; top: -24px !important; left: 50% !important; transform: translateX(-50%) !important; width: 2px !important; height: 24px !important; background-color: #2563eb !important; }
    html.dark-style .org-children-container > .org-node-wrapper::before, html.aps-dark .org-children-container > .org-node-wrapper::before, html[data-aps-theme="dark"] .org-children-container > .org-node-wrapper::before, .dark-style .org-children-container > .org-node-wrapper::before, .aps-dark .org-children-container > .org-node-wrapper::before { background-color: #3b82f6 !important; }
    .org-children-container > .org-node-wrapper:first-child::after { content: '' !important; position: absolute !important; top: -24px !important; left: 0 !important; width: 50% !important; height: 2px !important; background-color: #f8fafc !important; }
    html.dark-style .org-children-container > .org-node-wrapper:first-child::after, html.aps-dark .org-children-container > .org-node-wrapper:first-child::after, html[data-aps-theme="dark"] .org-children-container > .org-node-wrapper:first-child::after, .dark-style .org-children-container > .org-node-wrapper:first-child::after, .aps-dark .org-children-container > .org-node-wrapper:first-child::after { background-color: #0f172a !important; }
    .org-children-container > .org-node-wrapper:last-child::after { content: '' !important; position: absolute !important; top: -24px !important; right: 0 !important; width: 50% !important; height: 2px !important; background-color: #f8fafc !important; }
    html.dark-style .org-children-container > .org-node-wrapper:last-child::after, html.aps-dark .org-children-container > .org-node-wrapper:last-child::after, html[data-aps-theme="dark"] .org-children-container > .org-node-wrapper:last-child::after, .dark-style .org-children-container > .org-node-wrapper:last-child::after, .aps-dark .org-children-container > .org-node-wrapper:last-child::after { background-color: #0f172a !important; }
    .org-children-container > .org-node-wrapper:only-child::after { display: none !important; }
    .org-children-container:has(> .org-node-wrapper:only-child)::before { display: none !important; }
    .holi-floating-bar-wrapper { position: absolute !important; bottom: 14px !important; left: 50% !important; transform: translateX(-50%) !important; z-index: 100 !important; }
    .holi-floating-bar { background: #ffffff !important; border: 1px solid #e2e8f0 !important; color: #1e293b !important; padding: 3px 8px !important; border-radius: 9999px !important; box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.12), 0 2px 4px -1px rgba(0, 0, 0, 0.04) !important; display: flex !important; align-items: center !important; gap: 6px !important; white-space: nowrap !important; }
    .holi-floating-bar-pills { background: #f1f5f9 !important; padding: 2px !important; border-radius: 9999px !important; display: flex !important; align-items: center !important; gap: 2px !important; }
    .holi-floating-bar-pills .btn-inactive { color: #64748b !important; background: transparent !important; border: none !important; text-decoration: none !important; }
    .holi-floating-bar-pills .btn-inactive:hover { color: #0f172a !important; }
    .holi-floating-bar .btn { white-space: nowrap !important; font-size: 0.675rem !important; padding: 2.5px 8px !important; line-height: 1.3 !important; border-radius: 9999px !important; }
    .holi-floating-bar-divider { width: 1px !important; height: 14px !important; background-color: #cbd5e1 !important; }
    .holi-btn-secondary { background: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #334155 !important; font-weight: 600 !important; transition: all 0.2s ease !important; }
    .holi-btn-secondary:hover { background: #f8fafc !important; border-color: #94a3b8 !important; color: #0f172a !important; }
    html.dark-style .holi-floating-bar, html.aps-dark .holi-floating-bar, html[data-aps-theme="dark"] .holi-floating-bar, .dark-style .holi-floating-bar, .aps-dark .holi-floating-bar { background: #1e293b !important; border-color: #334155 !important; color: #ffffff !important; box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.5) !important; }
    html.dark-style .holi-floating-bar-pills, html.aps-dark .holi-floating-bar-pills, html[data-aps-theme="dark"] .holi-floating-bar-pills, .dark-style .holi-floating-bar-pills, .aps-dark .holi-floating-bar-pills { background: #0f172a !important; }
    html.dark-style .holi-floating-bar-pills .btn-inactive, html.aps-dark .holi-floating-bar-pills .btn-inactive, html[data-aps-theme="dark"] .holi-floating-bar-pills .btn-inactive, .dark-style .holi-floating-bar-pills .btn-inactive, .aps-dark .holi-floating-bar-pills .btn-inactive { color: #94a3b8 !important; }
    html.dark-style .holi-floating-bar-pills .btn-inactive:hover, html.aps-dark .holi-floating-bar-pills .btn-inactive:hover, html[data-aps-theme="dark"] .holi-floating-bar-pills .btn-inactive:hover, .dark-style .holi-floating-bar-pills .btn-inactive:hover, .aps-dark .holi-floating-bar-pills .btn-inactive:hover { color: #ffffff !important; }
    html.dark-style .holi-floating-bar-divider, html.aps-dark .holi-floating-bar-divider, html[data-aps-theme="dark"] .holi-floating-bar-divider, .dark-style .holi-floating-bar-divider, .aps-dark .holi-floating-bar-divider { background-color: #334155 !important; }
    html.dark-style .holi-btn-secondary, html.aps-dark .holi-btn-secondary, html[data-aps-theme="dark"] .holi-btn-secondary, .dark-style .holi-btn-secondary, .aps-dark .holi-btn-secondary { background: #0f172a !important; border-color: #475569 !important; color: #e2e8f0 !important; }
    html.dark-style .holi-btn-secondary:hover, html.aps-dark .holi-btn-secondary:hover, html[data-aps-theme="dark"] .holi-btn-secondary:hover, .dark-style .holi-btn-secondary:hover, .aps-dark .holi-btn-secondary:hover { background: #334155 !important; border-color: #64748b !important; color: #ffffff !important; }
    </style>

    {{-- PAGE HEADER --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1">Struktur Karyawan</h4>
            <p class="text-muted small mb-0">Organigramme hirarki posisi dan atasan langsung per NIP (Hierarchy Tree)</p>
        </div>

        {{-- TOP RIGHT ACTION --}}
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('employee.structure.simulate') }}" method="POST" id="form-simulate">
                @csrf
                <input type="hidden" name="station" value="{{ $selectedStation ?? '' }}">
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" onclick="confirmSimulate()">
                    <i class="ti ti-wand me-1"></i> Sync Auto-Hierarchy
                </button>
            </form>
        </div>
    </div>

    {{-- FILTER BAR (ULTRA COMPACT SINGLE LINE) --}}
    <div class="card border-0 shadow-xs rounded-3 mb-3">
        <div class="card-body py-2 px-3">
            <form action="{{ route('employee.structure.index') }}" method="GET" id="form-filter" class="d-flex flex-wrap align-items-center justify-content-between gap-3 m-0">
                <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 380px;">
                    <label class="form-label mb-0 small fw-semibold text-muted text-nowrap">Station <span class="text-danger">*</span></label>
                    <select name="station" class="form-select form-select-sm" onchange="document.getElementById('form-filter').submit()" required>
                        @foreach($stations as $st)
                            <option value="{{ $st->code }}" {{ ($selectedStation ?? '') === $st->code ? 'selected' : '' }}>
                                {{ $st->name }} ({{ $st->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 420px;">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Cari NIP, Nama, Jabatan..." value="{{ $search ?? '' }}">
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-search"></i>
                        </button>
                    </div>

                    @if(($search ?? ''))
                        <a href="{{ route('employee.structure.index', ['view_mode' => $viewMode, 'station' => $selectedStation]) }}" class="btn btn-xs btn-outline-secondary rounded-pill text-nowrap">
                            <i class="ti ti-refresh me-1"></i> Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-xs mb-3" role="alert">
            <i class="ti ti-circle-check me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-xs mb-3" role="alert">
            <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ══════════════════════════ BAGAN VIEW ══════════════════════════ --}}
    @if($viewMode === 'bagan')
        <div class="card border-0 shadow-sm overflow-hidden position-relative organigramme-card-container" id="orgCardContainer" style="border-radius: 20px; min-height: 700px;">
            
            {{-- CANVAS CANVAS (HOLI DOTTED GRID) --}}
            <div class="holi-canvas" id="holiCanvas">

                @if(empty($treeData) || count($treeData) === 0)
                    <div class="text-center py-5 my-5">
                        <div class="mb-3" style="font-size: 3rem; opacity: 0.3;">🏢</div>
                        <h5 class="fw-bold text-muted mb-2">Belum Ada Data Hirarki</h5>
                        <p class="text-muted small mb-3">Gunakan tombol <strong>Sync Auto-Hierarchy</strong> untuk menyinkronkan struktur.</p>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-4" onclick="confirmSimulate()">
                            <i class="ti ti-wand me-1"></i> Sync Auto-Hierarchy
                        </button>
                    </div>
                @else
                    {{-- TREE ROOT --}}
                    <div class="org-tree-root">
                        @foreach($treeData as $rootNode)
                            @include('employee_structure._node', ['node' => $rootNode, 'level' => 1])
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- SLEEK COMPACT ADAPTIVE FLOATING BOTTOM CONTROL BAR --}}
            <div class="holi-floating-bar-wrapper">
                <div class="holi-floating-bar">
                    {{-- View mode toggle pills --}}
                    <div class="holi-floating-bar-pills">
                        <a href="{{ route('employee.structure.index', array_merge(request()->query(), ['view_mode' => 'bagan'])) }}"
                           class="btn btn-xs rounded-pill px-2.5 py-1 fw-semibold {{ $viewMode === 'bagan' ? 'btn-primary text-white shadow-sm' : 'btn-inactive' }}">
                            <i class="ti ti-sitemap me-1"></i> Bagan
                        </a>
                        <a href="{{ route('employee.structure.index', array_merge(request()->query(), ['view_mode' => 'list'])) }}"
                           class="btn btn-xs rounded-pill px-2.5 py-1 fw-semibold {{ $viewMode === 'list' ? 'btn-primary text-white shadow-sm' : 'btn-inactive' }}">
                            <i class="ti ti-list-details me-1"></i> Tabel
                        </a>
                    </div>

                    <div class="holi-floating-bar-divider"></div>

                    {{-- Actions --}}
                    <button type="button" class="btn btn-xs holi-btn-secondary rounded-pill px-2.5 py-1" onclick="toggleAllBranches()">
                        <i class="ti ti-arrows-maximize me-1" id="icon-toggle-all"></i> <span id="text-toggle-all">Expand</span>
                    </button>

                    <button type="button" class="btn btn-xs holi-btn-secondary rounded-pill px-2.5 py-1" onclick="centerCanvasTree()">
                        <i class="ti ti-focus-2 me-1"></i> Center
                    </button>

                    <button type="button" class="btn btn-xs btn-primary rounded-pill px-2.5 py-1 text-white shadow-sm" onclick="toggleCanvasFullscreen()">
                        <i class="ti ti-maximize me-1" id="icon-fullscreen"></i> <span id="text-fullscreen">Fullscreen</span>
                    </button>
                </div>
            </div>
        </div>

    {{-- ══════════════════════════ LIST VIEW ══════════════════════════ --}}
    @else
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body">
                <x-dt-toolbar
                    :searchFormAction="route('employee.structure.index')"
                    searchPlaceholder="Cari NIP, Nama, Jabatan..."
                >
                    <x-slot:actions>
                        <div class="d-flex align-items-center gap-1 bg-light border p-1 rounded-pill">
                            <a href="{{ route('employee.structure.index', array_merge(request()->query(), ['view_mode' => 'bagan'])) }}"
                               class="btn btn-xs rounded-pill px-3 fw-semibold {{ $viewMode === 'bagan' ? 'btn-primary text-white shadow-sm' : 'text-secondary' }}">
                                <i class="ti ti-sitemap me-1"></i> Bagan
                            </a>
                            <a href="{{ route('employee.structure.index', array_merge(request()->query(), ['view_mode' => 'list'])) }}"
                               class="btn btn-xs rounded-pill px-3 fw-semibold {{ $viewMode === 'list' ? 'btn-primary text-white shadow-sm' : 'text-secondary' }}">
                                <i class="ti ti-list-details me-1"></i> Tabel
                            </a>
                        </div>
                    </x-slot:actions>
                </x-dt-toolbar>

                <div class="table-responsive text-nowrap mt-3">
                    <table class="table table-hover align-middle border-top">
                        <thead class="table-light">
                            <tr>
                                <th>NIP</th>
                                <th>Nama Karyawan</th>
                                <th>Jabatan</th>
                                <th>Station</th>
                                <th>Atasan Langsung</th>
                                <th class="text-center">Bawahan</th>
                                <th class="text-center" style="width: 80px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td class="fw-bold font-monospace text-primary">{{ $user->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-sm me-2 flex-shrink-0" style="width: 36px; height: 36px; min-width: 36px; min-height: 36px;">
                                                @if($user->profile_picture && file_exists(public_path('storage/photo/'.$user->profile_picture)))
                                                    <img src="{{ asset('storage/photo/'.$user->profile_picture) }}" alt="Avatar" class="rounded-circle" style="object-fit: cover; width:100%; height:100%;">
                                                @else
                                                    <span class="avatar-initial rounded-circle bg-label-primary fw-bold">
                                                        {{ strtoupper(substr($user->fullname ?? 'U', 0, 2)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div>
                                                <span class="fw-semibold d-block text-dark">{{ $user->fullname }}</span>
                                                <small class="text-muted">{{ $user->getRoleName() ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $user->jobTitle->name ?? $user->getRoleName() ?? '-' }}</td>
                                    <td><span class="badge bg-label-info">{{ $user->station ?? '-' }}</span></td>
                                    <td>
                                        @if($user->pic)
                                            <span class="fw-semibold">{{ $user->pic->fullname }}</span>
                                            <small class="text-muted d-block font-monospace">{{ $user->pic->id }}</small>
                                        @else
                                            <span class="text-muted fst-italic small">Root Node</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php $subCount = $user->subordinates->count(); @endphp
                                        @if($subCount > 0)
                                            <span class="badge bg-primary rounded-pill">{{ $subCount }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-action-button
                                            type="button"
                                            action="edit"
                                            title="Ubah Atasan Langsung"
                                            onclick="openSetSuperiorModal('{{ $user->id }}', '{{ addslashes($user->fullname) }}', '{{ $user->pic_id }}')"
                                        />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="ti ti-users-off fs-4 d-block mb-2 opacity-50"></i>
                                        Data karyawan tidak ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $users->links('vendor.pagination.custom') }}
                </div>
            </div>
        </div>
    @endif
</div>

{{-- MODAL UBAH ATASAN --}}
<div class="modal fade" id="modalSetSuperior" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form action="{{ route('employee.structure.update-superior') }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" id="modal_user_id">

                <div class="modal-header border-bottom-0 pb-1">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Ubah Atasan Langsung</h5>
                        <p class="text-muted small mb-0">Pilih karyawan yang menjadi atasan langsung</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-2">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-semibold">Karyawan</label>
                        <input type="text" id="modal_user_name" class="form-control bg-light fw-bold" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Atasan Langsung (PIC)</label>
                        <select name="pic_id" id="modal_pic_id" class="form-select">
                            <option value="">— Tanpa Atasan (Root Node) —</option>
                            @foreach($allUsers as $au)
                                <option value="{{ $au->id }}">
                                    {{ $au->fullname }} · {{ $au->id }} · {{ $au->jobTitle->name ?? $au->getRoleName() ?? 'Staff' }} [{{ $au->station ?? 'HO' }}]
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Karyawan ini akan menjadi bawahan langsung dari orang yang dipilih.</small>
                    </div>
                </div>

                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
/* ══ Center Root Node in Canvas Viewport ══ */
function centerCanvasTree() {
    const canvas = document.getElementById('holiCanvas');
    if (canvas) {
        const scrollWidth = canvas.scrollWidth;
        const clientWidth = canvas.clientWidth;
        if (scrollWidth > clientWidth) {
            canvas.scrollTo({
                left: (scrollWidth - clientWidth) / 2,
                behavior: 'smooth'
            });
        }
    }
}

/* ══ Toggle Canvas Fullscreen ══ */
function toggleCanvasFullscreen() {
    const container = document.getElementById('orgCardContainer');
    const textEl = document.getElementById('text-fullscreen');
    const iconEl = document.getElementById('icon-fullscreen');

    if (!container) return;

    if (container.classList.contains('is-fullscreen')) {
        container.classList.remove('is-fullscreen');
        if (document.exitFullscreen && document.fullscreenElement) {
            document.exitFullscreen().catch(() => {});
        }
        textEl.textContent = 'Fullscreen';
        iconEl.className = 'ti ti-maximize me-1';
    } else {
        container.classList.add('is-fullscreen');
        if (container.requestFullscreen) {
            container.requestFullscreen().catch(() => {});
        }
        textEl.textContent = 'Exit Fullscreen';
        iconEl.className = 'ti ti-minimize me-1';
    }
    setTimeout(centerCanvasTree, 200);
}

/* ══ Open Set Superior Modal ══ */
function openSetSuperiorModal(userId, userName, currentPicId) {
    document.getElementById('modal_user_id').value = userId;
    document.getElementById('modal_user_name').value = userName + ' (' + userId + ')';

    const selectPic = document.getElementById('modal_pic_id');
    selectPic.value = currentPicId || '';
    Array.from(selectPic.options).forEach(opt => {
        opt.disabled = (opt.value === userId);
    });

    new bootstrap.Modal(document.getElementById('modalSetSuperior')).show();
}

/* ══ Confirm Simulate ══ */
function confirmSimulate() {
    Swal.fire({
        title: 'Jalankan Auto-Hierarchy?',
        html: 'Sistem akan menyinkronkan atasan langsung karyawan<br>secara otomatis berdasarkan jabatan dan station.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#8592a3',
        confirmButtonText: '<i class="ti ti-wand me-1"></i> Ya, Jalankan!',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('form-simulate').submit();
        }
    });
}

/* ══ Interactive Node Collapse/Expand Toggle ══ */
function toggleNodeBranch(nodeId) {
    const childrenContainer = document.getElementById('children-' + nodeId);
    const badge = document.getElementById('badge-' + nodeId);

    if (!childrenContainer || !badge) return;

    if (childrenContainer.classList.contains('org-hidden')) {
        childrenContainer.classList.remove('org-hidden');
        badge.classList.remove('collapsed');
        badge.title = 'Klik untuk menutup bawahan';
    } else {
        childrenContainer.classList.add('org-hidden');
        badge.classList.add('collapsed');
        badge.title = 'Klik mebuka bawahan';
    }

    // Auto center canvas after branch expands/collapses so parent stays centered
    setTimeout(centerCanvasTree, 100);
}

/* ══ Toggle All Branches (Expand All / Collapse All) ══ */
let isAllExpanded = false;
function toggleAllBranches() {
    const containers = document.querySelectorAll('.org-children-container');
    const badges = document.querySelectorAll('.org-circle-badge');
    const textEl = document.getElementById('text-toggle-all');
    const iconEl = document.getElementById('icon-toggle-all');

    if (isAllExpanded) {
        containers.forEach(c => c.classList.add('org-hidden'));
        badges.forEach(b => b.classList.add('collapsed'));
        textEl.textContent = 'Expand';
        iconEl.className = 'ti ti-arrows-maximize me-1';
        isAllExpanded = false;
    } else {
        containers.forEach(c => c.classList.remove('org-hidden'));
        badges.forEach(b => b.classList.remove('collapsed'));
        textEl.textContent = 'Collapse';
        iconEl.className = 'ti ti-arrows-minimize me-1';
        isAllExpanded = true;
    }
    setTimeout(centerCanvasTree, 150);
}

// Auto center root on initial page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(centerCanvasTree, 150);
});
window.addEventListener('load', function() {
    setTimeout(centerCanvasTree, 300);
});
</script>
@endsection
