<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Assignment {{ $workResult->wo_number }}</title>
    <style>
        @page {
            margin: 20px 24px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* Utility Styles */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #64748b; }
        .font-mono { font-family: 'Courier', monospace; }
        
        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .company-title {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 7.5pt;
            color: #64748b;
            font-weight: 500;
            margin-top: 1px;
        }
        .station-pill {
            display: inline-block;
            font-size: 7pt;
            color: #1d4ed8;
            font-weight: 700;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
            letter-spacing: 0.3px;
        }

        /* Document Title Card */
        .doc-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #2563eb;
            padding: 8px 12px;
            text-align: right;
            border-radius: 6px;
        }
        .doc-title {
            font-size: 10pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 7pt;
            color: #2563eb;
            font-weight: 700;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .wo-badge {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 8.5pt;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
            letter-spacing: 0.5px;
        }

        /* Divider Line */
        .header-divider {
            height: 2px;
            background-color: #2563eb;
            margin: 6px 0 14px 0;
            border-radius: 1px;
        }

        /* Section Title Header */
        .section-header {
            background-color: #f1f5f9;
            border-left: 3px solid #2563eb;
            padding: 5px 10px;
            font-size: 8.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-top: 14px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }

        /* Structured Info Grid Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .info-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            font-size: 8pt;
            vertical-align: middle;
        }
        .info-label {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            width: 20%;
        }
        .info-value {
            color: #0f172a;
            font-weight: 600;
            width: 30%;
        }

        /* Badges & Value Styling */
        .pill-job {
            display: inline-block;
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-size: 7.5pt;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
        }
        .pill-reg {
            display: inline-block;
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            font-size: 8pt;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
        }

        /* Staff Table */
        .staff-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }
        .staff-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        .staff-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #0f172a;
        }
        .staff-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Photo Documentation Box */
        .photo-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background-color: #f8fafc;
            padding: 12px;
            text-align: center;
            margin-bottom: 12px;
        }
        .photo-img {
            max-height: 220px;
            max-width: 90%;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            display: block;
            margin: 0 auto;
            object-fit: contain;
        }
        .photo-caption {
            font-size: 7.5pt;
            color: #64748b;
            font-weight: 600;
            margin-top: 8px;
        }

        /* Signatures Grid */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            page-break-inside: avoid;
        }
        .sig-cell {
            width: 33.33%;
            vertical-align: top;
            padding: 0 4px;
        }
        .sig-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }
        .sig-title {
            font-size: 7.5pt;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 35px;
        }
        .sig-name {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
        }
        .sig-sub {
            font-size: 7pt;
            color: #64748b;
            margin-top: 2px;
        }

        /* Footer */
        .footer-line {
            height: 1px;
            background-color: #e2e8f0;
            margin-top: 14px;
            margin-bottom: 6px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-note {
            font-size: 6.5pt;
            color: #64748b;
            line-height: 1.35;
        }
        .footer-stamp {
            font-size: 6.5pt;
            color: #64748b;
            font-weight: 600;
            text-align: right;
        }
    </style>
</head>
<body>

    {{-- KOP SURAT --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        @if($base64Logo)
                            <td style="width: 50px; vertical-align: middle; padding-right: 10px;">
                                <img src="{{ $base64Logo }}" style="max-height: 44px; max-width: 120px;" alt="Logo APS">
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="company-title">PT ANGKASA PRATAMA SEJAHTERA</div>
                            <div class="company-sub">Ground Handling & Aircraft Operations Support Services</div>
                            <div class="station-pill">STATION: {{ $workResult->station }} | OPERATIONAL REPORT</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="doc-card">
                    <div class="doc-title">LAPORAN ASSIGNMENT</div>
                    <div class="doc-subtitle">DEEP CLEANING {{ $workResult->type == 'DCI' ? 'INTERIOR (DCI)' : 'EXTERIOR (DCE)' }}</div>
                    <div class="wo-badge">{{ $workResult->wo_number }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="header-divider"></div>

    {{-- INFORMASI PEKERJAAN & DATA PESAWAT --}}
    <div class="section-header">Informasi Pekerjaan & Data Pesawat</div>

    <table class="info-table">
        <tr>
            <td class="info-label">Nomor Assignment</td>
            <td class="info-value font-mono">{{ $workResult->wo_number }}</td>
            <td class="info-label">Kategori Pekerjaan</td>
            <td class="info-value">
                <span class="pill-job">
                    {{ $workResult->type }} - {{ $workResult->type == 'DCI' ? 'Deep Cleaning Interior' : 'Deep Cleaning Exterior' }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="info-label">Stasiun / Bandara</td>
            <td class="info-value">{{ $workResult->station }}</td>
            <td class="info-label">Tanggal Kerja</td>
            <td class="info-value">{{ date('d F Y', strtotime($workResult->date)) }}</td>
        </tr>
        <tr>
            <td class="info-label">Aircraft Registration</td>
            <td class="info-value"><span class="pill-reg">{{ $workResult->aircraft_reg }}</span></td>
            <td class="info-label">Parking Stand</td>
            <td class="info-value">Stand {{ $workResult->parking_stand }}</td>
        </tr>
        <tr>
            <td class="info-label">Ex Flight (Arrival)</td>
            <td class="info-value">{{ $workResult->ex_flight ?: '-' }}</td>
            <td class="info-label">To Flight (Departure)</td>
            <td class="info-value">{{ $workResult->to_flight ?: '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Jam Kerja (WIB)</td>
            <td class="info-value">{{ substr($workResult->start_time, 0, 5) }} - {{ substr($workResult->end_time, 0, 5) }} WIB</td>
            <td class="info-label">Total Durasi</td>
            <td class="info-value"><strong>{{ $workResult->duration_minutes }} Menit</strong></td>
        </tr>
        <tr>
            <td class="info-label">Leader Pengawas</td>
            <td class="info-value" colspan="3">
                <strong>{{ strtoupper($workResult->submittedBy ? $workResult->submittedBy->fullname : 'Leader On Duty') }}</strong> 
                <span class="text-muted" style="font-size: 7.5pt;">(NIP: {{ $workResult->submitted_by ?: '-' }})</span>
            </td>
        </tr>
    </table>

    {{-- TIM PELAKSANA PEKERJAAN --}}
    <div class="section-header">Tim Pelaksana Pekerjaan (Staff On Duty)</div>

    <table class="staff-table">
        <thead>
            <tr>
                <th width="8%" class="text-center">#</th>
                <th width="30%">NIP / ID STAFF</th>
                <th width="42%">NAMA LENGKAP STAFF</th>
                <th width="20%" class="text-center">STASIUN UNIT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workResult->users as $idx => $staff)
                <tr>
                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                    <td class="font-mono">{{ $staff->id }}</td>
                    <td><strong>{{ $staff->fullname }}</strong></td>
                    <td class="text-center">{{ $staff->station }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted" style="padding: 10px 0;">Tidak ada data staff pendukung.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- DOKUMENTASI BUKTI HASIL PEMBERSIHAN --}}
    <div class="section-header">Dokumentasi Bukti Hasil Pembersihan</div>

    <div class="photo-card">
        @if($base64Photo)
            <img src="{{ $base64Photo }}" class="photo-img" alt="Foto Bukti WO {{ $workResult->wo_number }}">
            <div class="photo-caption">Lampiran Foto Bukti Pembersihan Pesawat ({{ $workResult->aircraft_reg }}) — WO: {{ $workResult->wo_number }}</div>
        @else
            <div style="padding: 24px; color: #94a3b8; font-style: italic; font-size: 8pt;">
                (Tidak ada lampiran foto bukti pekerjaan)
            </div>
        @endif
    </div>

    {{-- LEMBAR PENGESAHAN --}}
    <table class="sig-table">
        <tr>
            <td class="sig-cell">
                <div class="sig-card">
                    <div class="sig-title">Dibuat Oleh (Leader)</div>
                    <div class="sig-name">{{ strtoupper($workResult->submittedBy ? $workResult->submittedBy->fullname : 'Leader On Duty') }}</div>
                    <div class="sig-sub">NIP: {{ $workResult->submitted_by ?: '-' }}</div>
                </div>
            </td>
            <td class="sig-cell">
                <div class="sig-card">
                    <div class="sig-title">Diperiksa Oleh (Supervisor)</div>
                    <div class="sig-name">Supervisor Operations</div>
                    <div class="sig-sub">Station {{ $workResult->station }}</div>
                </div>
            </td>
            <td class="sig-cell">
                <div class="sig-card">
                    <div class="sig-title">Disetujui Oleh (Airlines Rep)</div>
                    <div class="sig-name">Airlines Representative</div>
                    <div class="sig-sub">Flight Operations</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer-line"></div>
    <table class="footer-table">
        <tr>
            <td class="footer-note">
                * Dokumen ini diterbitkan secara resmi melalui Sistem Manajemen Operasional PT Angkasa Pratama Sejahtera (APS ONE).
            </td>
            <td class="footer-stamp">
                DOC-REF: APS-WO-{{ $workResult->wo_number }} &nbsp;|&nbsp; {{ date('d/m/Y H:i') }} WIB
            </td>
        </tr>
    </table>

</body>
</html>
