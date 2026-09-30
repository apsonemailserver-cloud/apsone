<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportExport implements WithMultipleSheets
{
    protected Collection $matrixData;
    protected Collection $detailedAttendances;
    protected string $month;
    protected string $stationName;

    /**
     * @param Collection|array $dataOrAttendances  Dapat berupa $matrixData (sudah format rekap) atau flat $attendances
     * @param Collection|null  $detailedAttendances Koleksi flat untuk Sheet Detail Presensi Harian
     * @param string           $month               Format 'YYYY-MM'
     * @param string           $stationName         Nama Station untuk judul
     */
    public function __construct(
        $dataOrAttendances,
        ?Collection $detailedAttendances = null,
        string $month = '',
        string $stationName = 'Semua Station'
    ) {
        $input = $dataOrAttendances instanceof Collection ? $dataOrAttendances : collect($dataOrAttendances);
        $this->month = $month ?: date('Y-m');
        $this->stationName = $stationName;

        // Cek apakah input sudah berupa $matrixData (elemen memiliki property 'days')
        $firstItem = $input->first();
        if ($firstItem && (isset($firstItem->days) || (is_array($firstItem) && isset($firstItem['days'])))) {
            $this->matrixData = $input;
            $this->detailedAttendances = $detailedAttendances ?: collect();
        } else {
            // Backward-compatibility: Jika dipassing flat attendances, transformasi ke matrixData
            $this->detailedAttendances = $input;
            $this->matrixData = $this->transformFlatToMatrix($input, $this->month);
        }
    }

    public function sheets(): array
    {
        return [
            // Sheet 1 (Utama): Rekap Presensi Matrix Horizontal (Status: H, T, C, OFF, A)
            new AttendanceMonthlyMatrixSheet($this->matrixData, $this->month, $this->stationName, 'status'),

            // Sheet 2: Rekap Presensi Matrix Horizontal (Waktu Check-In: 08:00, OFF, Cuti)
            new AttendanceMonthlyMatrixSheet($this->matrixData, $this->month, $this->stationName, 'time'),

            // Sheet 3: Log Detail Presensi Harian per Transaksi
            new AttendanceDailyDetailSheet($this->detailedAttendances),
        ];
    }

    /**
     * Mengubah koleksi flat attendances menjadi struktur matrix bulanan per karyawan.
     */
    protected function transformFlatToMatrix(Collection $attendances, string $month): Collection
    {
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();
        $today = Carbon::today();
        $daysInMonth = $startDate->daysInMonth;

        $groupedByUser = $attendances->groupBy(fn ($item) => $item->user->id ?? 'unknown');
        $matrix = collect();

        foreach ($groupedByUser as $userId => $userRows) {
            $first = $userRows->first();
            $user = $first->user ?? null;
            $rowsByDate = $userRows->keyBy(fn ($item) => Carbon::parse($item->date)->toDateString());

            $days = [];
            $summary = [
                'hadir'       => 0,
                'tepat_waktu' => 0,
                'telat'       => 0,
                'cuti'        => 0,
                'libur'       => 0,
                'tidak_hadir' => 0,
            ];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $curDate = $startDate->copy()->day($d);
                $curDateStr = $curDate->toDateString();
                $isWeekend = in_array($curDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);

                $row = $rowsByDate->get($curDateStr);
                $att = $row->attendance ?? null;
                $sched = $row->schedule ?? null;
                $leave = $row->leave ?? null;
                $corr = $row->correction ?? null;

                $hasAtt = $att !== null;
                $hasApprovedCorr = $corr && $corr->status === 'approved';

                $checkInTime = $hasAtt && $att->check_in_time ? Carbon::parse($att->check_in_time) : null;
                $checkOutTime = $hasAtt && $att->check_out_time ? Carbon::parse($att->check_out_time) : null;
                if ($hasApprovedCorr) {
                    $checkInTime = Carbon::parse($corr->proposed_check_in_time);
                    $checkOutTime = Carbon::parse($corr->proposed_check_out_time);
                }

                $statusCode = '-';
                $timeDisplay = '-';

                if ($leave) {
                    $statusCode = 'C';
                    $timeDisplay = 'Cuti';
                    $summary['cuti']++;
                } elseif ($hasAtt || $hasApprovedCorr) {
                    $summary['hadir']++;

                    $isLate = false;
                    if ($checkInTime && $sched && !empty($sched->start_time)) {
                        $schedStart = Carbon::parse($curDateStr . ' ' . $sched->start_time);
                        if ($checkInTime->gt($schedStart)) {
                            $isLate = true;
                        }
                    } elseif ($hasAtt && $att->status === 'Terlambat') {
                        $isLate = true;
                    }

                    if ($isLate) {
                        $statusCode = 'T';
                        $timeDisplay = ($checkInTime ? $checkInTime->format('H:i') : '-') . ' (T)';
                        $summary['telat']++;
                    } else {
                        $statusCode = 'H';
                        $timeDisplay = $checkInTime ? $checkInTime->format('H:i') : 'H';
                        $summary['tepat_waktu']++;
                    }
                } elseif ($curDate->lt($today)) {
                    if ($sched && strtolower($sched->shift_id ?? '') !== 'off') {
                        $statusCode = 'A';
                        $timeDisplay = 'A';
                        $summary['tidak_hadir']++;
                    } else {
                        $statusCode = 'OFF';
                        $timeDisplay = 'OFF';
                        $summary['libur']++;
                    }
                } else {
                    if (!$sched || strtolower($sched->shift_id ?? '') === 'off') {
                        $statusCode = 'OFF';
                        $timeDisplay = 'OFF';
                        $summary['libur']++;
                    } else {
                        $statusCode = '-';
                        $timeDisplay = '-';
                    }
                }

                $days[$d] = [
                    'day'          => $d,
                    'date'         => $curDateStr,
                    'day_name'     => $curDate->isoFormat('ddd'),
                    'is_weekend'   => $isWeekend,
                    'status_code'  => $statusCode,
                    'time_display' => $timeDisplay,
                    'check_in'     => $checkInTime ? $checkInTime->format('H:i:s') : '-',
                    'check_out'    => $checkOutTime ? $checkOutTime->format('H:i:s') : '-',
                ];
            }

            $matrix->push((object) [
                'user'    => $user,
                'days'    => $days,
                'summary' => $summary,
            ]);
        }

        return $matrix;
    }
}

/**
 * Sheet 1 & 2: Rekapitulasi Presensi Format Matrix Horizontal (Ke Kanan).
 * 1 Baris = 1 Karyawan, Kolom = Tanggal 1 s/d 28/29/30/31 ke samping, diakhiri Total H, T, C, OFF, A.
 */
class AttendanceMonthlyMatrixSheet implements FromArray, WithStyles, WithTitle
{
    protected Collection $matrixData;
    protected string $month;
    protected string $stationName;
    protected string $mode; // 'status' (H, T, C, OFF, A) atau 'time' (08:00, 08:15 (T), Cuti, OFF, A)
    protected int $daysInMonth;
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct(Collection $matrixData, string $month, string $stationName, string $mode = 'status')
    {
        $this->matrixData = $matrixData;
        $this->month = $month ?: date('Y-m');
        $this->stationName = $stationName;
        $this->mode = $mode;

        $this->startDate = Carbon::parse($this->month . '-01')->startOfMonth();
        $this->endDate = Carbon::parse($this->month . '-01')->endOfMonth();
        $this->daysInMonth = $this->startDate->daysInMonth;
    }

    public function title(): string
    {
        return $this->mode === 'status' ? 'Rekap Matrix Presensi' : 'Jam Check-In (Matrix)';
    }

    public function array(): array
    {
        $rows = [];
        $periodTitle = $this->startDate->translatedFormat('F Y');
        $modeSubtitle = $this->mode === 'status' ? 'KODE STATUS PRESENSI' : 'WAKTU MASUK (CHECK-IN)';

        // Row 1: Judul Utama
        $rows[] = ['LAPORAN REKAPITULASI PRESENSI KARYAWAN (' . $modeSubtitle . ')'];

        // Row 2: Metadata Periode & Station
        $rows[] = [
            'Periode: ' . $periodTitle . '   |   Station: ' . $this->stationName . '   |   Total Karyawan: ' . $this->matrixData->count() . ' Orang'
        ];

        // Row 3: Legend / Keterangan Singkatan
        if ($this->mode === 'status') {
            $rows[] = ['Keterangan:  H = Hadir Tepat Waktu   |   T = Terlambat   |   C = Cuti / Izin   |   OFF = Libur   |   A = Alpa / Tidak Hadir   |   - = Belum Berlangsung'];
        } else {
            $rows[] = ['Keterangan:  Format Jam (08:00) = Hadir   |   (T) = Terlambat   |   Cuti = Cuti/Izin   |   OFF = Libur   |   A = Alpa   |   - = Belum Berlangsung'];
        }

        // Row 4: Baris Kosong (Spacer)
        $rows[] = [''];

        // Row 5: Table Header
        $header = ['No', 'Nama Karyawan', 'NIP', 'Station', 'Role'];
        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            $header[] = $day;
        }
        $header[] = 'Hadir (H)';
        $header[] = 'Telat (T)';
        $header[] = 'Cuti (C)';
        $header[] = 'Libur (OFF)';
        $header[] = 'Alpa (A)';
        $rows[] = $header;

        // Inisialisasi penghitung total per tanggal untuk baris paling bawah
        $dailyTotals = array_fill(1, $this->daysInMonth, 0);
        $grandTotalHadir = 0;
        $grandTotalTelat = 0;
        $grandTotalCuti = 0;
        $grandTotalLibur = 0;
        $grandTotalAlpa = 0;

        // Row 6 s/d N: Baris Data Karyawan (1 Orang = 1 Baris)
        $no = 1;
        foreach ($this->matrixData as $empItem) {
            $user = $empItem->user ?? null;
            $days = $empItem->days ?? [];
            $summary = $empItem->summary ?? [];

            $row = [
                $no++,
                $user->fullname ?? ($user->name ?? '-'),
                (string) ($user->id ?? '-'),
                $user->station ?? '-',
                $user->role ?? '-',
            ];

            // Kolom Tanggal 1 s/d daysInMonth
            for ($day = 1; $day <= $this->daysInMonth; $day++) {
                $dayData = $days[$day] ?? null;
                $val = '-';

                if ($dayData) {
                    if ($this->mode === 'status') {
                        $val = $dayData['status_code'] ?? '-';
                    } else {
                        $val = $dayData['time_display'] ?? '-';
                    }

                    // Tambahkan ke total hadir harian jika hadir (H atau T)
                    $statusCode = $dayData['status_code'] ?? '';
                    if (in_array($statusCode, ['H', 'T'])) {
                        $dailyTotals[$day]++;
                    }
                }

                $row[] = $val;
            }

            // Kolom Ringkasan di Sebelah Kanan
            $hadirCount = (int) ($summary['hadir'] ?? 0);
            $telatCount = (int) ($summary['telat'] ?? 0);
            $cutiCount  = (int) ($summary['cuti'] ?? 0);
            $liburCount = (int) ($summary['libur'] ?? 0);
            $alpaCount  = (int) ($summary['tidak_hadir'] ?? 0);

            $row[] = $hadirCount;
            $row[] = $telatCount;
            $row[] = $cutiCount;
            $row[] = $liburCount;
            $row[] = $alpaCount;

            $grandTotalHadir += $hadirCount;
            $grandTotalTelat += $telatCount;
            $grandTotalCuti  += $cutiCount;
            $grandTotalLibur += $liburCount;
            $grandTotalAlpa  += $alpaCount;

            $rows[] = $row;
        }

        // Row N+1: Baris Rekap Total di Paling Bawah
        $bottomRow = ['', 'TOTAL HADIR HARIAN', '', '', ''];
        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            $bottomRow[] = $dailyTotals[$day];
        }
        $bottomRow[] = $grandTotalHadir;
        $bottomRow[] = $grandTotalTelat;
        $bottomRow[] = $grandTotalCuti;
        $bottomRow[] = $grandTotalLibur;
        $bottomRow[] = $grandTotalAlpa;
        $rows[] = $bottomRow;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $totalEmp = $this->matrixData->count();
        $headerRow = 5;
        $firstDataRow = 6;
        $lastDataRow = $firstDataRow + $totalEmp - 1;
        $bottomRow = $lastDataRow + 1;

        // Hitung index kolom
        // Kolom A s/d E = 5 kolom identitas
        // Kolom F s/d (5 + daysInMonth) = Kolom Tanggal
        // 5 kolom berikutnya = Summary Totals
        $lastDateColNum = 5 + $this->daysInMonth;
        $totalCols = $lastDateColNum + 5;

        $lastDateColLetter = Coordinate::stringFromColumnIndex($lastDateColNum);
        $firstSummaryColLetter = Coordinate::stringFromColumnIndex($lastDateColNum + 1);
        $lastColLetter = Coordinate::stringFromColumnIndex($totalCols);

        // Merge Cell Judul & Subjudul
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->mergeCells("A3:{$lastColLetter}3");
        $sheet->mergeCells("B{$bottomRow}:E{$bottomRow}");

        // Freeze Pane: Kolom A-E (Info Karyawan) dan Baris 1-5 (Header) tetap terlihat saat discroll
        $sheet->freezePane('F6');

        // Atur Tinggi Baris
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(3)->setRowHeight(18);
        $sheet->getRowDimension(4)->setRowHeight(8);
        $sheet->getRowDimension($headerRow)->setRowHeight(26);
        $sheet->getRowDimension($bottomRow)->setRowHeight(22);

        // Atur Lebar Kolom
        $sheet->getColumnDimension('A')->setWidth(6);   // No
        $sheet->getColumnDimension('B')->setWidth(26);  // Nama Karyawan
        $sheet->getColumnDimension('C')->setWidth(14);  // NIP
        $sheet->getColumnDimension('D')->setWidth(10);  // Station
        $sheet->getColumnDimension('E')->setWidth(20);  // Role

        // Lebar kolom tanggal 1 s/d daysInMonth
        $dateColWidth = $this->mode === 'status' ? 4.8 : 9.5;
        for ($col = 6; $col <= $lastDateColNum; $col++) {
            $sheet->getColumnDimensionByColumn($col)->setWidth($dateColWidth);
        }

        // Lebar kolom ringkasan
        for ($col = $lastDateColNum + 1; $col <= $totalCols; $col++) {
            $sheet->getColumnDimensionByColumn($col)->setWidth(11);
        }

        // Identifikasi kolom akhir pekan (Sabtu & Minggu) untuk styling khusus
        $weekendColLetters = [];
        for ($day = 1; $day <= $this->daysInMonth; $day++) {
            $cDate = $this->startDate->copy()->day($day);
            if (in_array($cDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $weekendColLetters[] = Coordinate::stringFromColumnIndex(5 + $day);
            }
        }

        // Styling Dasar Cell
        $styles = [
            1 => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF0F172A']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF475569']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            3 => [
                'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF64748B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            // Header Row (Row 5)
            $headerRow => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']], // Dark Navy
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
            // Bottom Row (Row N+1)
            $bottomRow => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF0F172A'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2E8F0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];

        // Format Alignment Data Table
        if ($totalEmp > 0) {
            // Kolom No, NIP, Station -> Center
            $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$firstDataRow}:D{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Kolom Nama & Role -> Left
            $sheet->getStyle("B{$firstDataRow}:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$firstDataRow}:E{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Kolom Tanggal -> Center
            $sheet->getStyle("F{$firstDataRow}:{$lastDateColLetter}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Kolom Ringkasan -> Center & Bold
            $sheet->getStyle("{$firstSummaryColLetter}{$firstDataRow}:{$lastColLetter}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("{$firstSummaryColLetter}{$firstDataRow}:{$lastColLetter}{$lastDataRow}")->getFont()->setBold(true);

            // Set font bold dan center untuk seluruh kolom tanggal
            $sheet->getStyle("F{$firstDataRow}:{$lastDateColLetter}{$lastDataRow}")->getFont()->setBold(true);
            $sheet->getStyle("F{$firstDataRow}:{$lastDateColLetter}{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Beri warna latar belakang lembut untuk kolom akhir pekan (Sabtu/Minggu)
            foreach ($weekendColLetters as $wCol) {
                // Header weekend
                $sheet->getStyle("{$wCol}{$headerRow}")->getFill()->applyFromArray([
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF334155'], // Slate lebih terang
                ]);

                // Data weekend column
                $sheet->getStyle("{$wCol}{$firstDataRow}:{$wCol}{$lastDataRow}")->getFill()->applyFromArray([
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF1F5F9'], // Light gray-blue tint
                ]);
            }

            // Garis Border Tabel
            $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$bottomRow}")->getBorders()->getAllBorders()->applyFromArray([
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FFCBD5E1'],
            ]);

            // Border tebal pemisah kolom identitas dan tanggal
            $sheet->getStyle("E{$headerRow}:E{$bottomRow}")->getBorders()->getRight()->applyFromArray([
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['argb' => 'FF94A3B8'],
            ]);

            // Border tebal pemisah kolom tanggal dan ringkasan
            $sheet->getStyle("{$lastDateColLetter}{$headerRow}:{$lastDateColLetter}{$bottomRow}")->getBorders()->getRight()->applyFromArray([
                'borderStyle' => Border::BORDER_MEDIUM,
                'color' => ['argb' => 'FF94A3B8'],
            ]);

            // Border atas baris total paling bawah
            $sheet->getStyle("A{$bottomRow}:{$lastColLetter}{$bottomRow}")->getBorders()->getTop()->applyFromArray([
                'borderStyle' => Border::BORDER_DOUBLE,
                'color' => ['argb' => 'FF475569'],
            ]);
        }

        return $styles;
    }
}

/**
 * Sheet 3: Log Detail Presensi Harian (Detail per baris per transaksi/jadwal).
 */
class AttendanceDailyDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected Collection $attendances;

    public function __construct(Collection $attendances)
    {
        $this->attendances = $attendances;
    }

    public function collection()
    {
        return $this->attendances->map(function ($row) {
            $attendance = $row->attendance ?? null;
            $schedule = $row->schedule ?? null;
            $leave = $row->leave ?? null;
            $correction = $row->correction ?? null;
            $date = Carbon::parse($row->date);
            $today = Carbon::today();

            $checkInTime = $attendance?->check_in_time ? Carbon::parse($attendance->check_in_time) : null;
            $checkOutTime = $attendance?->check_out_time ? Carbon::parse($attendance->check_out_time) : null;

            if ($correction && $correction->status === 'approved') {
                $checkInTime = Carbon::parse($correction->proposed_check_in_time);
                $checkOutTime = Carbon::parse($correction->proposed_check_out_time);
            }

            $checkIn = $checkInTime ? $checkInTime->format('H:i:s') : '-';
            $checkOut = $checkOutTime ? $checkOutTime->format('H:i:s') : '-';

            $workDuration = ($checkInTime && $checkOutTime)
                ? $checkInTime->diff($checkOutTime)->format('%H:%I:%S')
                : '-';

            // Status Telat
            $latenessStatus = '-';
            if ($checkInTime) {
                if ($schedule && !empty($schedule->start_time)) {
                    $schedStart = Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
                    $latenessStatus = $checkInTime->gt($schedStart) ? 'Telat' : 'Tepat Waktu';
                } else {
                    $latenessStatus = ($attendance && $attendance->status === 'Terlambat') ? 'Telat' : 'Tepat Waktu';
                }
            } elseif (!$leave && $date->lt($today)) {
                if ($schedule && strtolower($schedule->shift_id ?? '') !== 'off') {
                    $latenessStatus = 'Tidak Absen';
                }
            }

            // Keterangan
            $keteranganText = '-';
            if ($leave) {
                $keteranganText = 'Cuti (' . ($leave->leave_type ?? 'Cuti') . ')';
            } elseif ($correction) {
                $keteranganText = 'Koreksi Absen (' . ucfirst($correction->status) . ')';
            } elseif ($attendance) {
                $keteranganText = 'Hadir';
            } elseif ($date->lt($today)) {
                if ($schedule && strtolower($schedule->shift_id ?? '') !== 'off') {
                    $keteranganText = 'Tidak Hadir';
                } else {
                    $keteranganText = 'Libur';
                }
            }

            $lokasiText = '-';
            if ($checkInTime) {
                if ($attendance) {
                    $lokasiText = $attendance->station?->code ?? $row->user->station ?? '-';
                } elseif ($correction && $correction->status === 'approved') {
                    $lokasiText = $correction->station?->code ?? $row->user->station ?? '-';
                }
            }

            $shiftText = '-';
            if ($schedule) {
                $shiftDesc = $schedule->shift_description ?? ($schedule->shift?->description ?? 'Shift');
                $start = $schedule->start_time ? substr($schedule->start_time, 0, 5) : '';
                $end = $schedule->end_time ? substr($schedule->end_time, 0, 5) : '';
                $shiftText = $start ? "{$shiftDesc} ({$start} - {$end})" : $shiftDesc;
            }

            return [
                'Tanggal'        => $date->translatedFormat('d M Y'),
                'Nama Karyawan'  => $row->user->fullname ?? '-',
                'NIP'            => (string) ($row->user->id ?? '-'),
                'Station'        => $row->user->station ?? '-',
                'Role'           => $row->user->role ?? '-',
                'Shift / Jadwal' => $shiftText,
                'Check-in'       => $checkIn,
                'Check-out'      => $checkOut,
                'Lokasi'         => $lokasiText,
                'Durasi Kerja'   => $workDuration,
                'Status Telat'   => $latenessStatus,
                'Keterangan'     => $keteranganText,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Nama Karyawan',
            'NIP',
            'Station',
            'Role',
            'Shift / Jadwal',
            'Check-in',
            'Check-out',
            'Lokasi',
            'Durasi Kerja',
            'Status Telat',
            'Keterangan',
        ];
    }

    public function title(): string
    {
        return 'Log Detail Presensi';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(14);
        $sheet->getColumnDimension('K')->setWidth(14);
        $sheet->getColumnDimension('L')->setWidth(18);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            ],
        ];
    }
}
