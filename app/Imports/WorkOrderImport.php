<?php

namespace App\Imports;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class WorkOrderImport implements ToCollection
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
                $staffIds = array_map('trim', explode(',', $staffInput));
                $staffIds = array_filter($staffIds); // remove empty elements

                if (count($staffIds) < 2 || count($staffIds) > 10) {
                    continue; // Needs to be between 2 and 10 members
                }

                // Find valid user IDs in database
                $userIds = User::whereIn('id', $staffIds)->pluck('id')->toArray();
                if (count($userIds) < 2) {
                    continue; // Must be at least 2 valid staff members
                }

                // Create the WorkOrder
                $workOrder = WorkOrder::create([
                    'date' => $dateStr,
                    'station' => $station,
                    'aircraft_reg' => $aircraftReg,
                    'ex_flight' => $exFlight ?: '-',
                    'to_flight' => $toFlight ?: '-',
                    'parking_stand' => $parkingStand,
                    'wo_number' => $woNumber,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'type' => $type,
                    'submitted_by' => auth()->id(),
                ]);

                // Sync pivot table
                $workOrder->users()->sync($userIds);
            }
        });
    }

    private function formatDate($value)
    {
        if (!$value) return null;

        if (is_numeric($value)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatTime($value)
    {
        if (!$value) return null;

        if (is_numeric($value)) {
            $totalSeconds = $value * 86400;
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            return sprintf('%02d:%02d:00', $hours, $minutes);
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
