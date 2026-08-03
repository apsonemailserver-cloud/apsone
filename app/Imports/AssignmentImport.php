<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class AssignmentImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $rows->shift(); // skip header

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Parse date
                $dateStr = $this->formatDate($row[0]);
                if (!$dateStr) continue;

                $station = trim($row[1] ?? '');
                $aircraftReg = trim($row[2] ?? '');
                $exFlight = trim($row[3] ?? '');
                $toFlight = trim($row[4] ?? '');
                $parkingStand = trim($row[5] ?? '');
                $woNumber = trim($row[6] ?? '');
                $startTime = $this->formatTime($row[7]);
                $endTime = $this->formatTime($row[8]);
                $type = strtoupper(trim($row[9] ?? ''));

                if (empty($station) || empty($aircraftReg) || empty($parkingStand) || empty($woNumber) || !$startTime || !$endTime) {
                    continue; // Skip invalid row
                }

                if ($type !== 'DCI' && $type !== 'DCE') {
                    continue; // Skip invalid type
                }

                if ($startTime >= $endTime) {
                    continue; // Invalid times (End time must be after start time)
                }

                // Parse staff members
                $staffInput = $row[10] ?? '';
                $staffIds = array_map('trim', explode(',', (string) $staffInput));
                $staffIds = array_filter($staffIds); // remove empty elements

                if (count($staffIds) < 2 || count($staffIds) > 10) {
                    continue; // Needs to be between 2 and 10 members
                }

                // Find valid user IDs in database
                $userIds = User::whereIn('id', $staffIds)->pluck('id')->toArray();
                if (count($userIds) < 2) {
                    continue; // Must be at least 2 valid staff members
                }

                // Create assignment
                $assignment = Assignment::create([
                    'date'          => $dateStr,
                    'station'       => strtoupper($station),
                    'aircraft_reg'  => strtoupper($aircraftReg),
                    'ex_flight'     => strtoupper($exFlight) ?: '-',
                    'to_flight'     => strtoupper($toFlight) ?: '-',
                    'parking_stand' => strtoupper($parkingStand),
                    'wo_number'     => strtoupper($woNumber),
                    'start_time'    => $startTime,
                    'end_time'      => $endTime,
                    'type'          => $type,
                    'submitted_by'  => auth()->id(),
                ]);

                // Attach staff members
                $assignment->users()->sync($userIds);
            }
        });
    }

    private function formatDate($val)
    {
        if (empty($val)) return null;
        if (is_numeric($val)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
        }
        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatTime($val)
    {
        if (empty($val)) return null;
        if (is_numeric($val)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('H:i');
        }
        try {
            return Carbon::parse($val)->format('H:i');
        } catch (\Exception $e) {
            return null;
        }
    }
}
