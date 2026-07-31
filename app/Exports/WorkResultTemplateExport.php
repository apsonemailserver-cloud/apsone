<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkResultTemplateExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return collect([
            [
                '2026-07-31',
                'CGK',
                'PK-LGH',
                'JT 371',
                'JT 202',
                'A12',
                'WO-2026-001',
                '08:00',
                '09:30',
                'DCI',
                '101240001, 2412040316'
            ],
            [
                '2026-07-31',
                'CGK',
                'PK-GFA',
                'GA 142',
                'GA 145',
                'B05',
                'WO-2026-002',
                '10:00',
                '11:45',
                'DCE',
                '101240001, 2510040361'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Tanggal (YYYY-MM-DD)',
            'Station (Kode)',
            'Aircraft Reg (Contoh: PK-LGH)',
            'Ex Flight',
            'To Flight',
            'Parking Stand',
            'No WO',
            'Start Time (HH:MM)',
            'End Time (HH:MM)',
            'Type (DCI / DCE)',
            'NIK Staff Members (Pisahkan Koma, Min 2 Staff)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2F80ED']
                ]
            ],
        ];
    }
}
