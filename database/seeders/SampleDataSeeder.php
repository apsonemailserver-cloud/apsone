<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Station;
use App\Models\Assignment;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Leave;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate existing sample tables to avoid duplication when running the seeder multiple times
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Attendance::truncate();
        Assignment::truncate();
        Overtime::truncate();
        Leave::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $startDate = Carbon::create(2026, 7, 25);
        $endDate = Carbon::create(2026, 8, 8);
        $today = Carbon::create(2026, 8, 1);

        $users = User::all();
        $cgkStation = Station::where('code', 'CGK')->first();
        $subStation = Station::where('code', 'SUB')->first();

        $stationMap = [
            'CGK' => $cgkStation ? $cgkStation->id : 1,
            'SUB' => $subStation ? $subStation->id : 2,
        ];

        $shifts = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'off'];
        $workShifts = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        $shiftTimes = [
            'A' => ['05:00:00', '15:00:00'],
            'B' => ['06:30:00', '16:30:00'],
            'C' => ['08:00:00', '18:00:00'],
            'D' => ['09:30:00', '19:30:00'],
            'E' => ['13:30:00', '23:30:00'],
            'F' => ['16:00:00', '02:00:00'],
            'G' => ['20:00:00', '06:00:00'],
            'H' => ['23:00:00', '09:00:00'],
        ];

        $flightTemplates = [
            ['airline' => 'Garuda Indonesia', 'ex' => 'GA-182', 'to' => 'GA-183', 'reg' => 'PK-GFI', 'stands' => ['G22', 'G24', 'G18']],
            ['airline' => 'Garuda Indonesia', 'ex' => 'GA-204', 'to' => 'GA-205', 'reg' => 'PK-GMC', 'stands' => ['G12', 'G15', 'G20']],
            ['airline' => 'Garuda Indonesia', 'ex' => 'GA-310', 'to' => 'GA-311', 'reg' => 'PK-GPU', 'stands' => ['E4', 'E7', 'E10']],
            ['airline' => 'Lion Air', 'ex' => 'JT-610', 'to' => 'JT-611', 'reg' => 'PK-LQP', 'stands' => ['R14', 'R18', 'R22']],
            ['airline' => 'Lion Air', 'ex' => 'JT-532', 'to' => 'JT-533', 'reg' => 'PK-LKK', 'stands' => ['R10', 'R12', 'R15']],
            ['airline' => 'Batik Air', 'ex' => 'ID-6812', 'to' => 'ID-6813', 'reg' => 'PK-BDF', 'stands' => ['A5', 'A8', 'A12']],
            ['airline' => 'Batik Air', 'ex' => 'ID-6520', 'to' => 'ID-6521', 'reg' => 'PK-BAG', 'stands' => ['A2', 'A4', 'A6']],
            ['airline' => 'Super Air Jet', 'ex' => 'IU-770', 'to' => 'IU-771', 'reg' => 'PK-SAJ', 'stands' => ['B1', 'B3', 'B5']],
            ['airline' => 'Citilink', 'ex' => 'QG-810', 'to' => 'QG-811', 'reg' => 'PK-GQA', 'stands' => ['C3', 'C6', 'C9']],
            ['airline' => 'AirAsia', 'ex' => 'QZ-7520', 'to' => 'QZ-7521', 'reg' => 'PK-AZA', 'stands' => ['D1', 'D3', 'D5']],
            ['airline' => 'Qantas', 'ex' => 'QF-41', 'to' => 'QF-42', 'reg' => 'VH-ZHD', 'stands' => ['Gate 5', 'Gate 7']],
        ];

        $assignmentTypes = ['DCI', 'PDI', 'Transit', 'RON'];

        $this->command->info('1. Generating Schedules for 2026-07-25 to 2026-08-08...');

        $scheduleData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();
            $dayOfWeek = $currentDate->dayOfWeek;

            foreach ($users as $index => $user) {
                // Determine shift based on user ID and date for realistic consistency
                $shiftIndex = ($user->id + $dayOfWeek + $currentDate->day) % 10;
                $shiftId = ($shiftIndex === 0 || $shiftIndex === 9) ? 'off' : $workShifts[($user->id + $currentDate->day) % count($workShifts)];

                $scheduleData[] = [
                    'user_id' => $user->id,
                    'date' => $dateStr,
                    'shift_id' => $shiftId,
                    'is_active' => 1,
                ];
            }
            $currentDate->addDay();
        }

        // Batch insert schedules
        foreach (array_chunk($scheduleData, 500) as $chunk) {
            DB::table('schedules')->upsert($chunk, ['user_id', 'date'], ['shift_id', 'is_active']);
        }

        $this->command->info('2. Generating Attendances for 2026-07-25 to 2026-08-01...');

        $attendanceData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($today)) {
            $dateStr = $currentDate->toDateString();

            // Fetch schedules for this date
            $schedulesToday = Schedule::where('date', $dateStr)->where('shift_id', '!=', 'off')->get();

            foreach ($schedulesToday as $sched) {
                $u = $users->firstWhere('id', $sched->user_id);
                if (!$u) continue;

                $stId = $stationMap[$u->station] ?? 1;
                $times = $shiftTimes[$sched->shift_id] ?? ['08:00:00', '16:00:00'];

                $checkIn = Carbon::parse($dateStr . ' ' . $times[0])->addMinutes(rand(-15, 10));
                $checkOut = Carbon::parse($dateStr . ' ' . $times[1])->addMinutes(rand(-5, 25));

                if ($checkOut->lt($checkIn)) {
                    $checkOut->addDay();
                }

                $latBase = ($u->station === 'SUB') ? -7.3797 : -6.1256;
                $lngBase = ($u->station === 'SUB') ? 112.7874 : 106.6558;

                $attendanceData[] = [
                    'user_id' => $u->id,
                    'station_id' => $stId,
                    'check_in_time' => $checkIn->toDateTimeString(),
                    'check_out_time' => $checkOut->toDateTimeString(),
                    'check_in_latitude' => $latBase + (rand(-100, 100) / 100000),
                    'check_in_longitude' => $lngBase + (rand(-100, 100) / 100000),
                    'check_out_latitude' => $latBase + (rand(-100, 100) / 100000),
                    'check_out_longitude' => $lngBase + (rand(-100, 100) / 100000),
                    'check_in_ip' => '182.253.14.' . rand(1, 254),
                    'check_out_ip' => '182.253.14.' . rand(1, 254),
                    'status' => rand(1, 10) > 2 ? 'Hadir' : 'Tepat Waktu',
                    'created_at' => $checkIn,
                    'updated_at' => $checkOut,
                ];
            }

            $currentDate->addDay();
        }

        foreach (array_chunk($attendanceData, 300) as $chunk) {
            Attendance::insert($chunk);
        }

        $this->command->info('3. Generating Assignments (Work Orders) for 2026-07-25 to 2026-08-08...');

        $currentDate = $startDate->copy();
        $woCounter = 100;

        $leaders = User::whereIn('role', ['Leader Apron', 'Leader Bge', 'SPV Apron', 'SPV Bge', 'Admin', 'Ass Leader Apron', 'Ass Leader Bge'])->get();
        if ($leaders->isEmpty()) {
            $leaders = $users;
        }

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();

            foreach (['CGK', 'SUB'] as $stCode) {
                $numFlights = ($stCode === 'CGK') ? rand(5, 8) : rand(3, 5);
                $stationStaff = $users->where('station', $stCode)->pluck('id')->toArray();

                for ($i = 0; $i < $numFlights; $i++) {
                    $template = $flightTemplates[rand(0, count($flightTemplates) - 1)];
                    $type = $assignmentTypes[rand(0, count($assignmentTypes) - 1)];
                    $stand = $template['stands'][rand(0, count($template['stands']) - 1)];
                    $submitter = $leaders->where('station', $stCode)->random()->id ?? $leaders->random()->id;

                    $startHour = rand(6, 21);
                    $startTime = sprintf('%02d:%02d:00', $startHour, rand(0, 59));
                    $endTime = sprintf('%02d:%02d:00', $startHour + rand(1, 2), rand(0, 59));

                    // Test both filled WO numbers and NULL WO numbers!
                    $woNumber = (rand(1, 10) > 3) ? 'WO-' . str_replace('-', '', $dateStr) . '-' . str_pad($woCounter++, 3, '0', STR_PAD_LEFT) : null;
                    $isPast = Carbon::parse($dateStr)->lte(Carbon::today());
                    $photoPath = ($isPast && rand(1, 10) > 3) ? 'photo/sample_wo.jpg' : null;

                    $assignment = Assignment::create([
                        'date' => $dateStr,
                        'station' => $stCode,
                        'aircraft_reg' => $template['reg'],
                        'ex_flight' => $template['ex'],
                        'to_flight' => $template['to'],
                        'parking_stand' => $stand,
                        'wo_number' => $woNumber,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'photo_path' => $photoPath,
                        'type' => $type,
                        'submitted_by' => $submitter,
                        'created_at' => Carbon::parse($dateStr . ' ' . $startTime),
                        'updated_at' => Carbon::parse($dateStr . ' ' . $endTime),
                    ]);

                    // Attach 2 to 5 staff members to this assignment
                    if (!empty($stationStaff)) {
                        $numStaff = rand(2, min(5, count($stationStaff)));
                        shuffle($stationStaff);
                        $assignedUsers = array_slice($stationStaff, 0, $numStaff);
                        $assignment->users()->attach($assignedUsers);
                    }
                }
            }

            $currentDate->addDay();
        }

        $this->command->info('4. Generating Overtime requests...');

        $overtimeTitles = [
            'Lembur Loading Cargo Pesawat Widebody',
            'Lembur Penanganan High-Load Passenger Luggage',
            'Lembur Rapid Turnaround Flight Cleaning',
            'Lembur Persiapan Standby Unit Apron Heavy Traffic',
            'Lembur Assist Transfer BGC Terminal 3',
        ];

        for ($k = 0; $k < 20; $k++) {
            $randomUser = $users->random();
            $randomDate = $startDate->copy()->addDays(rand(0, 14))->toDateString();
            $status = ['Pending', 'Approved', 'Rejected'][rand(0, 2)];
            $approver = ($status !== 'Pending') ? ($leaders->random()->fullname ?? 'Admin Station') : null;

            Overtime::create([
                'user_id' => $randomUser->id,
                'date' => $randomDate,
                'duration' => rand(2, 5),
                'title' => $overtimeTitles[rand(0, count($overtimeTitles) - 1)],
                'description' => 'Membutuhkan penambahan jam kerja operasional di area apron/baggage.',
                'status' => $status,
                'approved_by' => $approver,
                'rejection_reason' => ($status === 'Rejected') ? 'Jumlah personil di shift reguler sudah mencukupi.' : null,
                'created_at' => Carbon::parse($randomDate)->subHours(rand(1, 12)),
            ]);
        }

        $this->command->info('5. Generating Leave applications...');

        $leaveTypes = ['Cuti Tahunan', 'Izin', 'Sakit'];
        $reasons = [
            'Keperluan keluarga mendesak di luar kota',
            'Kondisi fisik demam dan flu (Surat Dokter Terlampir)',
            'Mengurus administrasi keluarga',
            'Cuti tahunan yang disetujui atasan',
        ];

        for ($l = 0; $l < 15; $l++) {
            $randomUser = $users->random();
            $lStart = $startDate->copy()->addDays(rand(0, 10));
            $days = rand(1, 3);
            $lEnd = $lStart->copy()->addDays($days - 1);
            $status = ['pending', 'approved', 'rejected'][rand(0, 2)];

            $approverId = ($status === 'approved') ? ($leaders->random()->id ?? null) : null;
            $rejecterId = ($status === 'rejected') ? ($leaders->random()->id ?? null) : null;

            Leave::create([
                'user_id' => $randomUser->id,
                'leave_type' => $leaveTypes[rand(0, count($leaveTypes) - 1)],
                'start_date' => $lStart->toDateString(),
                'end_date' => $lEnd->toDateString(),
                'total_days' => $days,
                'number_of_days' => $days,
                'reason' => $reasons[rand(0, count($reasons) - 1)],
                'replacement_employee_name' => $users->where('id', '!=', $randomUser->id)->random()->fullname,
                'status' => $status,
                'approved_by' => $approverId,
                'approved_at' => ($status === 'approved') ? now() : null,
                'rejected_by' => $rejecterId,
                'created_at' => $lStart->copy()->subDays(rand(1, 5)),
            ]);
        }

        $this->command->info('6. Generating TIM Bandara records...');

        $timEligibleUsers = User::whereIn('role', ['Driver', 'Leader Apron', 'Ass Leader Apron', 'Porter Apron', 'Controller'])->get();
        if ($timEligibleUsers->isEmpty()) {
            $timEligibleUsers = $users->take(40);
        }

        foreach ($timEligibleUsers as $idx => $tUser) {
            $regDate = Carbon::create(2025, rand(1, 12), rand(1, 28));
            // Mix of upcoming expirations (within 30-90 days) and normal future dates
            $expireDays = ($idx % 3 === 0) ? rand(10, 25) : (($idx % 3 === 1) ? rand(31, 55) : rand(90, 365));
            $expDate = Carbon::today()->addDays($expireDays);

            $tUser->update([
                'tim_number' => 'TIM-' . $tUser->station . '-' . str_pad(rand(100, 9999), 5, '0', STR_PAD_LEFT),
                'tim_registered' => $regDate->toDateString(),
                'tim_expired' => $expDate->toDateString(),
            ]);
        }

        $this->command->info('Sample data generation completed successfully!');
    }
}
