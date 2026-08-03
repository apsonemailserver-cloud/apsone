<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil Pekerjaan - {{ $stationLabel }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
        }
        .logo-img {
            height: 45px;
            width: auto;
        }
        .report-title {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .metadata-table td {
            padding: 6px 10px;
            font-size: 11px;
            border: none;
        }
        .label {
            font-weight: bold;
            color: #475569;
            width: 120px;
        }
        .value {
            color: #0f172a;
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .report-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            padding: 8px 6px;
            border: 1px solid #0f172a;
            text-align: center;
        }
        .report-table td {
            padding: 6px 5px;
            border: 1px solid #cbd5e1;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .report-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8.5px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .badge-dci {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-dce {
            background-color: #dcfce7;
            color: #166534;
        }
        .font-mono {
            font-family: Courier, monospace;
        }
        .footer-section {
            width: 100%;
            margin-top: 40px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            text-align: center;
            border: none;
            font-size: 11px;
        }
        .signature-space {
            height: 70px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    {{-- KOP Surat / Header --}}
    <table class="header-table">
        <tr>
            <td>
                @if($base64Logo)
                    <img class="logo-img" src="{{ $base64Logo }}" alt="JAS Airport Services Logo">
                @else
                    <span style="font-weight: bold; font-size: 16px; color: #1e3a8a;">JAS AIRPORT SERVICES</span>
                @endif
            </td>
            <td class="report-title">
                Laporan Hasil Pekerjaan Pembersihan Pesawat
            </td>
        </tr>
    </table>

    {{-- Metadata Laporan --}}
    <table class="metadata-table">
        <tr>
            <td class="label">Stasiun / Bandara:</td>
            <td class="value">{{ $stationLabel }}</td>
            <td class="label">Tanggal Unduh:</td>
            <td class="value">{{ now()->translatedFormat('d F Y H:i') }} WIB</td>
        </tr>
        <tr>
            <td class="label">Periode Laporan:</td>
            <td class="value">{{ $periodLabel }}</td>
            <td class="label">Diunduh Oleh:</td>
            <td class="value">{{ $user->fullname }} (NIP: {{ $user->id }})</td>
        </tr>
    </table>

    {{-- Tabel Utama Laporan --}}
    <table class="report-table">
        <thead>
            <tr>
                <th width="3%">#</th>
                <th width="10%">Tanggal</th>
                <th width="6%">Stasiun</th>
                <th width="12%">No. WO</th>
                <th width="10%">Reg. Pesawat</th>
                <th width="10%">Ex / To Flight</th>
                <th width="8%">Stand</th>
                <th width="8%">Kategori</th>
                <th width="10%">Jam Kerja (Durasi)</th>
                <th width="10%">Leader Pengawas</th>
                <th width="13%">Staff Terlibat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignments as $idx => $item)
                <tr>
                    <td class="text-center font-mono">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') }}</td>
                    <td class="text-center font-mono">{{ $item->station }}</td>
                    <td class="font-mono text-center">{{ $item->wo_number }}</td>
                    <td class="font-mono text-center fw-bold" style="font-weight: bold;">{{ $item->aircraft_reg }}</td>
                    <td class="text-center">
                        Ex: {{ $item->ex_flight ?: '-' }} / To: {{ $item->to_flight ?: '-' }}
                    </td>
                    <td class="text-center font-mono">{{ $item->parking_stand }}</td>
                    <td class="text-center">
                        @if($item->type === 'DCI')
                            <span class="badge badge-dci">DCI</span>
                        @else
                            <span class="badge badge-dce">DCE</span>
                        @endif
                    </td>
                    <td class="text-center font-mono">
                        {{ substr($item->start_time, 0, 5) }} - {{ substr($item->end_time, 0, 5) }}<br>
                        ({{ $item->duration_minutes }} Min)
                    </td>
                    <td>{{ $item->submittedBy ? $item->submittedBy->fullname : '-' }}</td>
                    <td>
                        @if($item->users && $item->users->count() > 0)
                            @foreach($item->users as $uIdx => $u)
                                {{ $u->fullname }}{{ $uIdx < $item->users->count() - 1 ? ', ' : '' }}
                            @endforeach
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px; font-weight: bold; color: #64748b;">
                        Tidak ada data pekerjaan untuk kriteria filter terpilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Footer Tanda Tangan --}}
    <div class="footer-section">
        <table class="signature-table">
            <tr>
                <td>
                    Disetujui Oleh,<br>
                    <strong>Station Manager / Supervisor</strong>
                    <div class="signature-space"></div>
                    <div class="signature-name">( ........................................ )</div>
                </td>
                <td>
                    Dibuat Oleh,<br>
                    <strong>Leader Pengawas</strong>
                    <div class="signature-space"></div>
                    <div class="signature-name">{{ $user->fullname }}</div>
                    <div>NIP: {{ $user->id }}</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
