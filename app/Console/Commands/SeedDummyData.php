<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Flights;
use App\Models\Flight_details;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\Attendance;
use App\Models\Station;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SeedDummyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:seed-dummy-records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed dummy flight schedules and attendance records for the last 7 days and next 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to seed dummy records...');

        // 1. Get CGK Station ID
        $cgkStation = Station::where('code', 'CGK')->first();
        if (!$cgkStation) {
            $this->error('CGK Station not found. Please run migrations/seeders first.');
            return 1;
        }
        $stationId = $cgkStation->id;

        // 2. Define target users
        $targetUserIds = [
            '2207004',    // UMAR MARUF M (Admin)
            '102240228',  // HERMAN SUTANTO ALIF SYAHBANA
            '10240444',   // ANGGI SAPUTRA
            '10240445',   // AHMAD SUHAEMI
            '10240448',   // NAHWAN
            '10240449',   // ABDUL HAMID
            '10240450',   // KUSYANTO
            '10240452',   // SUMANTRI
            '10240453',   // ROBBIKAL AKSAL RAMADHAN
            '10240454',   // ALAMSYAH
            '10240455',   // KEFIN
        ];

        $users = User::whereIn('id', $targetUserIds)->get();
        if ($users->isEmpty()) {
            $this->error('Target users not found in users table.');
            return 1;
        }

        // 3. Define shifts mapping
        $shifts = ['pagi', 'siang', 'malam', 'off'];
        $shiftModels = Shift::all()->keyBy('id');

        // 4. Time parameters
        $today = Carbon::today();
        $todayString = $today->toDateString();
        $startDate = $today->copy()->subDays(7);
        $endDate = $today->copy()->addDays(7);

        $dayCount = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->toDateString();
            $this->line("Processing date: {$dateString}");

            // --- A. Schedules ---
            foreach ($users as $index => $user) {
                // Determine a shift for this user on this day
                // We use a formula to distribute shifts nicely
                $shiftIndex = (crc32($user->id . $dateString) % count($shifts));
                $shiftId = $shifts[$shiftIndex];

                Schedule::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $dateString],
                    ['shift_id' => $shiftId, 'is_active' => true]
                );
            }

            // --- B. Flights ---
            // Create 3 flights for CGK station on this date
            $flightsData = [
                [
                    'airline' => 'Garuda Indonesia',
                    'flight_number' => 'GA-' . (100 + ($dayCount * 3)),
                    'registasi' => 'PK-GA' . chr(65 + ($dayCount % 26)),
                    'type' => 'Widebody',
                    'arrival' => '08:00:00',
                    'time_count' => '09:00:00',
                ],
                [
                    'airline' => 'Singapore Airlines',
                    'flight_number' => 'SQ-' . (200 + ($dayCount * 3)),
                    'registasi' => '9V-SM' . chr(65 + ($dayCount % 26)),
                    'type' => 'Widebody',
                    'arrival' => '14:30:00',
                    'time_count' => '15:30:00',
                ],
                [
                    'airline' => 'Qantas',
                    'flight_number' => 'QF-' . (300 + ($dayCount * 3)),
                    'registasi' => 'VH-OQ' . chr(65 + ($dayCount % 26)),
                    'type' => 'Widebody',
                    'arrival' => '21:15:00',
                    'time_count' => '22:15:00',
                ]
            ];

            $createdFlights = [];
            foreach ($flightsData as $flightVal) {
                $flightCreatedAt = $dateString . ' ' . $flightVal['arrival'];
                $status = ($dateString < $todayString) ? 1 : 0;

                // Find or instantiate model manually to bypass fillable limitations on 'type' and 'station'
                $flight = Flights::where([
                    'flight_number' => $flightVal['flight_number'],
                    'station' => 'CGK',
                    'created_at' => $flightCreatedAt
                ])->first();

                if (!$flight) {
                    $flight = new Flights();
                    $flight->flight_number = $flightVal['flight_number'];
                    $flight->station = 'CGK';
                    $flight->created_at = $flightCreatedAt;
                }

                $flight->airline = $flightVal['airline'];
                $flight->registasi = $flightVal['registasi'];
                $flight->type = $flightVal['type'];
                $flight->arrival = $flightVal['arrival'];
                $flight->time_count = $flightVal['time_count'];
                $flight->status = $status;
                $flight->updated_at = $flightCreatedAt;
                $flight->save();

                $createdFlights[] = $flight;
            }

            // --- C. Flight Details ---
            // For each flight, assign 4 Porter schedules of this day (excluding off shifts)
            $schedulesForDate = Schedule::where('date', $dateString)
                ->where('shift_id', '!=', 'off')
                ->whereIn('user_id', $users->pluck('id'))
                ->get();

            foreach ($createdFlights as $flight) {
                // Link schedules
                foreach ($schedulesForDate->take(4) as $sch) {
                    Flight_details::firstOrCreate([
                        'flight_id' => $flight->id,
                        'schedule_id' => $sch->id,
                    ]);
                }
            }

            // --- D. Attendances ---
            if ($dateString < $todayString) {
                // Past days: Full attendances for scheduled users
                foreach ($schedulesForDate as $sch) {
                    $user = $users->firstWhere('id', $sch->user_id);
                    if (!$user) continue;

                    $shift = $shiftModels->get($sch->shift_id);
                    if (!$shift) continue;

                    $shiftStart = Carbon::parse($dateString . ' ' . $shift->start_time);
                    $shiftEnd = Carbon::parse($dateString . ' ' . $shift->end_time);
                    if ($shift->start_time > $shift->end_time) {
                        $shiftEnd->addDay();
                    }

                    // Check-in: random 1-15 min early, or 1-5 min late (10% chance)
                    $checkInTime = $shiftStart->copy()->subMinutes(rand(1, 15));
                    $status = 'Tepat Waktu';
                    if (rand(1, 10) === 1) {
                        $checkInTime = $shiftStart->copy()->addMinutes(rand(1, 5));
                        $status = 'Terlambat';
                    }

                    // Check-out: random 1-15 min late
                    $checkOutTime = $shiftEnd->copy()->addMinutes(rand(1, 15));

                    Attendance::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'check_in_time' => $checkInTime->toDateTimeString()
                        ],
                        [
                            'station_id' => $stationId,
                            'check_out_time' => $checkOutTime->toDateTimeString(),
                            'status' => $status,
                            'check_in_latitude' => '-6.12560000',
                            'check_in_longitude' => '106.65580000',
                            'check_out_latitude' => '-6.12560000',
                            'check_out_longitude' => '106.65580000',
                            'check_in_photo' => 'attendance_' . $user->id . '_ci.jpg',
                            'check_out_photo' => 'attendance_' . $user->id . '_co.jpg',
                        ]
                    );
                }
            } elseif ($dateString === $todayString) {
                // Today: Check-in based on current time
                $nowTime = Carbon::now();
                foreach ($schedulesForDate as $sch) {
                    $user = $users->firstWhere('id', $sch->user_id);
                    if (!$user) continue;

                    $shift = $shiftModels->get($sch->shift_id);
                    if (!$shift) continue;

                    $shiftStart = Carbon::parse($dateString . ' ' . $shift->start_time);
                    $shiftEnd = Carbon::parse($dateString . ' ' . $shift->end_time);
                    if ($shift->start_time > $shift->end_time) {
                        $shiftEnd->addDay();
                    }

                    if ($nowTime->isAfter($shiftStart)) {
                        // Checked in
                        $checkInTime = $shiftStart->copy()->subMinutes(rand(1, 15));
                        $status = 'Tepat Waktu';
                        if (rand(1, 10) === 1) {
                            $checkInTime = $shiftStart->copy()->addMinutes(rand(1, 5));
                            $status = 'Terlambat';
                        }

                        $checkOutTime = null;
                        if ($nowTime->isAfter($shiftEnd)) {
                            // Also checked out
                            $checkOutTime = $shiftEnd->copy()->addMinutes(rand(1, 15));
                        }

                        Attendance::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'check_in_time' => $checkInTime->toDateTimeString()
                            ],
                            [
                                'station_id' => $stationId,
                                'check_out_time' => $checkOutTime ? $checkOutTime->toDateTimeString() : null,
                                'status' => $status,
                                'check_in_latitude' => '-6.12560000',
                                'check_in_longitude' => '106.65580000',
                                'check_out_latitude' => $checkOutTime ? '-6.12560000' : null,
                                'check_out_longitude' => $checkOutTime ? '106.65580000' : null,
                                'check_in_photo' => 'attendance_' . $user->id . '_ci.jpg',
                                'check_out_photo' => $checkOutTime ? 'attendance_' . $user->id . '_co.jpg' : null,
                            ]
                        );
                    }
                }
            }

            $dayCount++;
        }

        $this->info('Dummy flight schedules and attendance records seeded successfully!');
        return 0;
    }
}
