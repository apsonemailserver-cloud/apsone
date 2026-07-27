<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceReportExport implements FromCollection, WithHeadings
{
    protected $attendances;

    public function __construct(Collection $attendances)
    {
        $this->attendances = $attendances;
    }

    // Kirim data ke Excel
    public function collection()
    {
        return $this->attendances->map(function ($row) {

            $attendance = $row->attendance ?? null;
            $schedule = $row->schedule ?? null;
            $leave = $row->leave ?? null;
            $correction = $row->correction ?? null;
            $date = \Carbon\Carbon::parse($row->date);
            $today = \Carbon\Carbon::today();

            $checkInTime = $attendance?->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time) : null;
            $checkOutTime = $attendance?->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time) : null;

            $checkIn = $checkInTime ? $checkInTime->format('H:i:s') : '-';
            $checkOut = $checkOutTime ? $checkOutTime->format('H:i:s') : '-';

            $workDuration = ($checkInTime && $checkOutTime)
                ? $checkInTime->diff($checkOutTime)->format('%H:%I:%S')
                : '-';

            // Status Telat
            $latenessStatus = '-';
            if ($checkInTime) {
                if ($schedule && $schedule->start_time) {
                    $schedStart = \Carbon\Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
                    $latenessStatus = $checkInTime->gt($schedStart) ? 'Telat' : 'Tepat Waktu';
                } else {
                    $latenessStatus = ($attendance->status === 'Terlambat') ? 'Telat' : 'Tepat Waktu';
                }
            } elseif (!$leave && $date->lt($today)) {
                if ($schedule) {
                    $latenessStatus = 'Tidak Absen';
                }
            }

            // Keterangan / Status Koreksi / Cuti
            $keteranganText = '-';
            if ($leave) {
                $keteranganText = 'Cuti (' . ($leave->leave_type ?? 'Cuti') . ')';
            } elseif ($correction) {
                $keteranganText = 'Koreksi Absen (' . ucfirst($correction->status) . ')';
            } elseif ($attendance) {
                $keteranganText = 'Hadir';
            } elseif ($date->lt($today)) {
                $keteranganText = $schedule ? 'Tidak Hadir' : 'Libur';
            }

            return [
                'Tanggal' => $date->translatedFormat('d M Y'),
                'Nama' => $row->user->fullname ?? '-',
                'NIP' => $row->user->id ?? '-',
                'Check-in' => $checkIn,
                'Check-out' => $checkOut,
                'Lokasi' => $attendance ? ($attendance->station?->code ?? $row->user->station) : '-',
                'Durasi Kerja' => $workDuration,
                'Status Telat' => $latenessStatus,
                'Keterangan' => $keteranganText,
            ];
        });
    }

    // Heading kolom
    public function headings(): array
    {
        return ['Tanggal', 'Nama', 'NIP', 'Check-in', 'Check-out', 'Lokasi', 'Durasi Kerja', 'Status Telat', 'Keterangan'];
    }
}
