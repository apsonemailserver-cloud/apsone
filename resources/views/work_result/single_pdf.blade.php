<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Work Order {{ $workResult->wo_number }}</title>
    <style>
        @page {
            margin: 22px 28px;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        
        /* Kop Surat Header */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .kop-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .company-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            letter-spacing: 0.3px;
        }
        .company-sub {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 1px;
        }
        .station-tag {
            display: inline-block;
            font-size: 8px;
            color: #2563eb;
            font-weight: bold;
            background-color: #eff6ff;
            padding: 1px 6px;
            border-radius: 3px;
            margin-top: 3px;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 13px;
            color: #0f172a;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .doc-title p {
            margin: 1px 0 0 0;
            font-size: 9px;
            color: #2563eb;
            font-weight: 600;
        }
        .wo-badge {
            display: inline-block;
            background-color: #2f80ed;
            color: #ffffff;
            font-family: Courier, monospace;
            font-weight: bold;
            font-size: 10.5px;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 3px;
        }

        /* Divider Line */
        .header-divider {
            height: 2px;
            background-color: #2f80ed;
            margin-bottom: 12px;
            border: none;
        }

        /* Section Headings (Clean Minimalist Left Accent) */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-left: 3.5px solid #2f80ed;
            padding-left: 7px;
            margin-top: 12px;
            margin-bottom: 6px;
        }

        /* Grid Table Info - Borderless Clean Lines */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            background-color: #ffffff;
        }
        .info-table td {
            padding: 4px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
            font-size: 8.5px;
            text-transform: uppercase;
            width: 120px;
        }
        .info-value {
            color: #0f172a;
            font-weight: 600;
        }

        /* Staff Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 8px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 4.5px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9.5px;
            color: #1e293b;
        }
        .data-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 1.5px 6px;
            font-size: 8.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
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
            color: #1e40af;
            font-family: Courier, monospace;
            font-weight: bold;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .font-mono {
            font-family: Courier, monospace;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Photo Box */
        .photo-container {
            text-align: center;
            margin: 6px 0 10px 0;
            padding: 6px;
            border: 1px solid #e2e8f0;
            background-color: #fafafa;
            border-radius: 4px;
        }
        .photo-img {
            max-height: 160px;
            max-width: 85%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        .photo-caption {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 3px;
            font-style: italic;
        }

        /* Signatures Section */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            border: none;
            font-size: 9.5px;
        }
        .signature-title {
            font-weight: 600;
            color: #475569;
            margin-bottom: 42px;
        }
        .signature-name {
            font-weight: bold;
            color: #0f172a;
        }
        .signature-line {
            border-bottom: 1px solid #475569;
            width: 75%;
            margin: 0 auto 3px auto;
        }
        .signature-role {
            font-size: 8.5px;
            color: #64748b;
        }

        /* Footer Stamp */
        .footer-note {
            margin-top: 15px;
            padding-top: 6px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    {{-- KOP SURAT --}}
    <table class="kop-table">
        <tr>
            <td style="width: 55%;">
                <div class="company-title">PT ANGKASA PRATAMA SEJAHTERA</div>
                <div class="company-sub">Ground Handling & Aircraft Operations Support Services</div>
                <div class="station-tag">STATION: {{ $workResult->station }} | OPERATIONAL REPORT</div>
            </td>
            <td class="doc-title" style="width: 45%;">
                <h2>LAPORAN WORK ORDER</h2>
                <p>DEEP CLEANING {{ $workResult->type == 'DCI' ? 'INTERIOR (DCI)' : 'EXTERIOR (DCE)' }}</p>
                <div class="wo-badge">{{ $workResult->wo_number }}</div>
            </td>
        </tr>
    </table>

    <div class="header-divider"></div>

    {{-- INFORMASI PENERBANGAN & PESAWAT --}}
    <div class="section-title">Informasi Pekerjaan & Data Pesawat</div>
    <table class="info-table">
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
                (NIP / ID: <span class="font-mono">{{ $workResult->submitted_by ?: '-' }}</span>)
            </td>
        </tr>
    </table>

    {{-- TIM PELAKSANA (STAFF MEMBERS) --}}
    <div class="section-title">Tim Pelaksana Pekerjaan (Staff On Duty)</div>
    <table class="data-table">
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
    <div class="section-title">Dokumentasi Bukti Hasil Pembersihan</div>
    <div class="photo-container">
        @if($base64Photo)
            <img src="{{ $base64Photo }}" class="photo-img" alt="Foto Bukti WO {{ $workResult->wo_number }}">
            <div class="photo-caption">Lampiran Foto Bukti Pembersihan Pesawat ({{ $workResult->aircraft_reg }}) — WO: {{ $workResult->wo_number }}</div>
        @else
            <div style="padding: 15px; color: #94a3b8; font-style: italic;">(Tidak ada lampiran foto bukti pekerjaan)</div>
        @endif
    </div>

    {{-- LEMBAR PENGESAHAN / SIGNATURE BLOCKS --}}
    <table class="signature-table">
        <tr>
            <td>
                <div class="signature-title">Dibuat Oleh (Leader),</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $workResult->submittedBy ? $workResult->submittedBy->fullname : 'Leader On Duty' }}</div>
                <div class="signature-role">NIP: {{ $workResult->submitted_by ?: '-' }}</div>
            </td>
            <td>
                <div class="signature-title">Diperiksa Oleh (Supervisor),</div>
                <div class="signature-line"></div>
                <div class="signature-name">Supervisor Operations</div>
                <div class="signature-role">Station {{ $workResult->station }}</div>
            </td>
            <td>
                <div class="signature-title">Disetujui Oleh (Airlines Rep),</div>
                <div class="signature-line"></div>
                <div class="signature-name">Airlines Representative</div>
                <div class="signature-role">Flight Operations</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        * Dokumen ini dibuat secara resmi melalui sistem manajemen operasional pembersihan pesawat PT Angkasa Pratama Sejahtera (APS ONE).
        Dicetak pada tanggal: {{ date('d F Y H:i') }} WIB oleh {{ auth()->user() ? auth()->user()->fullname : 'System User' }}.
    </div>

</body>
</html>
