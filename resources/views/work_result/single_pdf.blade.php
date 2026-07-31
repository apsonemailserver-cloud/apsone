<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Work Order {{ $workResult->wo_number }}</title>
    <style>
        @page {
            margin: 18px 24px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #0f172a;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        
        /* Kop Surat Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .company-title {
            font-size: 12.5pt;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 7.5pt;
            color: #475569;
            font-weight: 600;
            margin-top: 1px;
        }
        .station-badge {
            display: inline-block;
            font-size: 7pt;
            color: #1e40af;
            font-weight: 700;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 1.5px 7px;
            border-radius: 3px;
            margin-top: 3px;
        }

        /* Document Title Box */
        .doc-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #2f80ed;
            padding: 6px 10px;
            text-align: right;
            border-radius: 4px;
        }
        .doc-title {
            font-size: 10.5pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
        }
        .doc-subtitle {
            font-size: 7.5pt;
            color: #2f80ed;
            font-weight: 700;
            margin-top: 1px;
        }
        .wo-number-tag {
            display: inline-block;
            background-color: #1e3a8a;
            color: #ffffff;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            font-size: 9pt;
            padding: 2px 7px;
            border-radius: 3px;
            margin-top: 3px;
            letter-spacing: 0.8px;
        }

        /* Gradient Divider Line */
        .divider {
            border: 0;
            height: 2px;
            background: #2f80ed;
            margin: 6px 0 10px 0;
        }

        /* Section Header Banner */
        .section-header {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 4px 8px;
            border-radius: 3px 3px 0 0;
            margin-top: 8px;
        }

        /* Info Grid Box */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            border-top: none;
            background-color: #ffffff;
            margin-bottom: 10px;
        }
        .info-grid td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 8.5pt;
            vertical-align: middle;
        }
        .info-label {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            width: 18%;
        }
        .info-value {
            color: #0f172a;
            font-weight: 600;
            width: 32%;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 1.5px 6px;
            font-size: 7.5pt;
            font-weight: 700;
            border-radius: 3px;
        }
        .badge-dci {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .badge-dce {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-reg {
            background-color: #eff6ff;
            color: #1e3a8a;
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            font-size: 8.5pt;
            padding: 1.5px 6px;
            border: 1px solid #93c5fd;
            border-radius: 3px;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 600;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Staff Table */
        .table-staff {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #cbd5e1;
            border-top: none;
            margin-bottom: 10px;
        }
        .table-staff th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
        }
        .table-staff td {
            padding: 4.5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 8.5pt;
            color: #0f172a;
        }
        .table-staff tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* Photo Box */
        .photo-card {
            border: 1px solid #cbd5e1;
            border-top: none;
            background-color: #f8fafc;
            padding: 8px;
            text-align: center;
            margin-bottom: 10px;
            border-radius: 0 0 4px 4px;
        }
        .photo-img {
            max-height: 180px;
            max-width: 85%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            display: block;
            margin: 0 auto;
        }
        .photo-caption {
            font-size: 7.5pt;
            color: #475569;
            font-weight: 600;
            margin-top: 4px;
        }

        /* Signatures Section */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            page-break-inside: avoid;
        }
        .signature-cell {
            width: 33.33%;
            vertical-align: top;
            padding: 0 4px;
        }
        .signature-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            background-color: #ffffff;
            padding: 6px 8px;
            text-align: center;
        }
        .signature-title {
            font-size: 7.5pt;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 3px;
            margin-bottom: 30px;
        }
        .signature-name {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
        }
        .signature-role {
            font-size: 7pt;
            color: #64748b;
            margin-top: 1px;
        }

        /* Footer Stamp */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
        .footer-note {
            font-size: 7pt;
            color: #64748b;
            line-height: 1.3;
        }
        .footer-stamp {
            font-size: 7pt;
            font-family: 'Courier New', Courier, monospace;
            color: #94a3b8;
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
                            <td style="width: 48px; vertical-align: middle; padding-right: 8px;">
                                <img src="{{ $base64Logo }}" style="max-height: 40px; max-width: 100px;" alt="Logo APS">
                            </td>
                        @endif
                        <td style="vertical-align: middle;">
                            <div class="company-title">PT ANGKASA PRATAMA SEJAHTERA</div>
                            <div class="company-sub">Ground Handling & Aircraft Operations Support Services</div>
                            <div class="station-badge">STATION: {{ $workResult->station }} | OPERATIONAL REPORT</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="doc-box">
                    <div class="doc-title">LAPORAN WORK ORDER</div>
                    <div class="doc-subtitle">DEEP CLEANING {{ $workResult->type == 'DCI' ? 'INTERIOR (DCI)' : 'EXTERIOR (DCE)' }}</div>
                    <div class="wo-number-tag">{{ $workResult->wo_number }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- INFORMASI PENERBANGAN & PESAWAT --}}
    <div class="section-header">Informasi Pekerjaan & Data Pesawat</div>
    <table class="info-grid">
        <tr>
            <td class="info-label">Nomor Work Order</td>
            <td class="info-value font-mono">{{ $workResult->wo_number }}</td>
            <td class="info-label">Kategori Pekerjaan</td>
            <td class="info-value">
                <span class="badge {{ $workResult->type == 'DCI' ? 'badge-dci' : 'badge-dce' }}">
                    {{ $workResult->type }} - {{ $workResult->type == 'DCI' ? 'Deep Cleaning Interior' : 'Deep Cleaning Exterior' }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="info-label">Stasiun / Bandara</td>
            <td class="info-value font-mono">{{ $workResult->station }}</td>
            <td class="info-label">Tanggal Kerja</td>
            <td class="info-value">{{ date('d F Y', strtotime($workResult->date)) }}</td>
        </tr>
        <tr>
            <td class="info-label">Aircraft Registration</td>
            <td class="info-value"><span class="badge-reg">{{ $workResult->aircraft_reg }}</span></td>
            <td class="info-label">Parking Stand</td>
            <td class="info-value font-mono">Stand {{ $workResult->parking_stand }}</td>
        </tr>
        <tr>
            <td class="info-label">Ex Flight (Arrival)</td>
            <td class="info-value font-mono">{{ $workResult->ex_flight ?: '-' }}</td>
            <td class="info-label">To Flight (Departure)</td>
            <td class="info-value font-mono">{{ $workResult->to_flight ?: '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Jam Kerja (WIB)</td>
            <td class="info-value font-mono">{{ substr($workResult->start_time, 0, 5) }} - {{ substr($workResult->end_time, 0, 5) }} WIB</td>
            <td class="info-label">Total Durasi</td>
            <td class="info-value"><strong>{{ $workResult->duration_minutes }} Menit</strong></td>
        </tr>
        <tr>
            <td class="info-label">Leader Pengawas</td>
            <td class="info-value" colspan="3">
                <strong>{{ $workResult->submittedBy ? $workResult->submittedBy->fullname : 'Leader On Duty' }}</strong> 
                (NIP: <span class="font-mono">{{ $workResult->submitted_by ?: '-' }}</span>)
            </td>
        </tr>
    </table>

    {{-- TIM PELAKSANA (STAFF MEMBERS) --}}
    <div class="section-header">Tim Pelaksana Pekerjaan (Staff On Duty)</div>
    <table class="table-staff">
        <thead>
            <tr>
                <th width="6%" class="text-center">#</th>
                <th width="24%">NIP / ID Staff</th>
                <th width="45%">Nama Lengkap Staff</th>
                <th width="25%" class="text-center">Stasiun Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workResult->users as $idx => $staff)
                <tr>
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="font-mono">{{ $staff->id }}</td>
                    <td><strong>{{ $staff->fullname }}</strong></td>
                    <td class="text-center font-mono">{{ $staff->station }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-2">Tidak ada data staff pendukung.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- FOTO BUKTI PEKERJAAN --}}
    <div class="section-header">Dokumentasi Bukti Hasil Pembersihan</div>
    <div class="photo-card">
        @if($base64Photo)
            <img src="{{ $base64Photo }}" class="photo-img" alt="Foto Bukti WO {{ $workResult->wo_number }}">
            <div class="photo-caption">Lampiran Foto Bukti Pembersihan Pesawat ({{ $workResult->aircraft_reg }}) — WO: {{ $workResult->wo_number }}</div>
        @else
            <div style="padding: 12px; color: #94a3b8; font-style: italic;">(Tidak ada lampiran foto bukti pekerjaan)</div>
        @endif
    </div>

    {{-- LEMBAR PENGESAHAN / SIGNATURE BLOCKS --}}
    <table class="signature-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-box">
                    <div class="signature-title">Dibuat Oleh (Leader)</div>
                    <div class="signature-name">{{ $workResult->submittedBy ? $workResult->submittedBy->fullname : 'Leader On Duty' }}</div>
                    <div class="signature-role">NIP: {{ $workResult->submitted_by ?: '-' }}</div>
                </div>
            </td>
            <td class="signature-cell">
                <div class="signature-box">
                    <div class="signature-title">Diperiksa Oleh (Supervisor)</div>
                    <div class="signature-name">Supervisor Operations</div>
                    <div class="signature-role">Station {{ $workResult->station }}</div>
                </div>
            </td>
            <td class="signature-cell">
                <div class="signature-box">
                    <div class="signature-title">Disetujui Oleh (Airlines Rep)</div>
                    <div class="signature-name">Airlines Representative</div>
                    <div class="signature-role">Flight Operations</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="footer-table">
        <tr>
            <td class="footer-note">
                * Dokumen ini diterbitkan secara resmi melalui Sistem Manajemen Operasional PT Angkasa Pratama Sejahtera (APS ONE).
            </td>
            <td class="footer-stamp">
                DOC-REF: APS-WO-{{ $workResult->wo_number }} | {{ date('d/m/Y H:i') }} WIB
            </td>
        </tr>
    </table>

</body>
</html>
