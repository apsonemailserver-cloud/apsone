<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Leave;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\Station;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class September2026AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai seeding data dummy presensi realtime September 2026...');

        $month = '2026-09';
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();
        $daysInMonth = $startDate->daysInMonth; // 30 hari

        // Hari ini: 2026-09-29, Jam 21:57 WIB
        $today = Carbon::parse('2026-09-29');
        $currentTime = Carbon::parse('2026-09-29 21:57:00');

        // 1. Bersihkan data lama pada bulan September 2026 agar bersih dan tidak duplikat
        $this->command->info('1. Membersihkan data September 2026 yang sudah ada...');
        Schedule::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->delete();
        Attendance::whereBetween('check_in_time', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])->delete();
        Leave::whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])->delete();
        AttendanceCorrection::whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])->delete();

        // 2. Ambil user aktif dan referensi station
        $users = User::with(['employee', 'roleRelation'])->where('is_active', 1)->get();
        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user aktif ditemukan.');
            return;
        }

        $approverUser = User::whereHas('roleRelation', fn ($q) => $q->where('name', 'Admin'))->first() ?: $users->first();
        $approverId = $approverUser->id;

        $this->command->info("2. Ditemukan {$users->count()} karyawan aktif untuk di-generate jadwal & absensi.");

        // Koordinat GPS Station
        $stationCoords = [
            'CGK' => ['lat' => -6.1256, 'lng' => 106.6558],
            'SUB' => ['lat' => -7.3818, 'lng' => 112.7706],
        ];

        // Shift templates
        $shiftTimes = [
            'P2' => ['start' => '05:00:00', 'end' => '15:00:00', 'cross' => false],
            'P3' => ['start' => '06:30:00', 'end' => '16:30:00', 'cross' => false],
            'P4' => ['start' => '08:00:00', 'end' => '18:00:00', 'cross' => false],
            'P5' => ['start' => '09:30:00', 'end' => '19:30:00', 'cross' => false],
            'S1' => ['start' => '13:30:00', 'end' => '23:30:00', 'cross' => false],
            'S2' => ['start' => '16:00:00', 'end' => '02:00:00', 'cross' => true],
            'M1' => ['start' => '20:00:00', 'end' => '06:00:00', 'cross' => true],
            'off' => ['start' => '00:00:00', 'end' => '00:00:00', 'cross' => false],
        ];

        // 3. Siapkan Cuti Terjadwal (Approved Leaves) untuk beberapa karyawan
        $leavePlans = [];
        $sampleLeaveUsers = $users->slice(5, 5); // 5 karyawan terpilih
        $leaveDatesList = [
            ['start' => '2026-09-08', 'end' => '2026-09-10', 'type' => 'Cuti Tahunan', 'reason' => 'Keperluan keluarga di luar kota'],
            ['start' => '2026-09-15', 'end' => '2026-09-16', 'type' => 'Cuti Sakit', 'reason' => 'Sakit demam surat dokter terlampir'],
            ['start' => '2026-09-21', 'end' => '2026-09-23', 'type' => 'Cuti Tahunan', 'reason' => 'Menghadiri pernikahan saudara'],
            ['start' => '2026-09-03', 'end' => '2026-09-05', 'type' => 'Cuti Tahunan', 'reason' => 'Cuti tahunan'],
            ['start' => '2026-09-17', 'end' => '2026-09-18', 'type' => 'Cuti Melahirkan/Keluarga', 'reason' => 'Istri melahirkan'],
        ];

        $leaveIndex = 0;
        foreach ($sampleLeaveUsers as $lu) {
            if (isset($leaveDatesList[$leaveIndex])) {
                $ld = $leaveDatesList[$leaveIndex++];
                $start = Carbon::parse($ld['start']);
                $end = Carbon::parse($ld['end']);
                $totalDays = $start->diffInDays($end) + 1;

                $stCode = $lu->employee->station_id ?? $lu->station ?? 'CGK';

                Leave::create([
                    'user_id'     => $lu->id,
                    'leave_type'  => $ld['type'],
                    'start_date'  => $ld['start'],
                    'end_date'    => $ld['end'],
                    'total_days'  => $totalDays,
                    'reason'      => $ld['reason'],
                    'status'      => 'approved',
                    'approved_by' => $approverId,
                    'approved_at' => $start->copy()->subDays(2)->format('Y-m-d 10:00:00'),
                ]);

                // Tandai tanggal cuti user ini
                $c = $start->copy();
                while ($c->lte($end)) {
                    $leavePlans[$lu->id . '_' . $c->toDateString()] = true;
                    $c->addDay();
                }
            }
        }

        // 4. Siapkan Koreksi Presensi Terjadwal (Approved Attendance Corrections) untuk 3 karyawan
        $correctionPlans = [];
        $sampleCorrUsers = $users->slice(12, 3);
        $corrDates = ['2026-09-07', '2026-09-14', '2026-09-22'];
        $corrIdx = 0;
        foreach ($sampleCorrUsers as $cu) {
            $tglCorr = $corrDates[$corrIdx++] ?? '2026-09-11';
            $correctionPlans[$cu->id . '_' . $tglCorr] = [
                'user_id' => $cu->id,
                'date'    => $tglCorr,
                'station' => $cu->employee->station_id ?? $cu->station ?? 'CGK',
            ];
        }

        // 5. Siapkan Alpa Terjadwal untuk 3 karyawan (1 hari saja per orang)
        $alpaPlans = [];
        $sampleAlpaUsers = $users->slice(20, 3);
        $alpaDates = ['2026-09-11', '2026-09-18', '2026-09-25'];
        $alpaIdx = 0;
        foreach ($sampleAlpaUsers as $au) {
            $tglAlpa = $alpaDates[$alpaIdx++] ?? '2026-09-12';
            $alpaPlans[$au->id . '_' . $tglAlpa] = true;
        }

        // 6. Buat Jadwal Shift untuk Seluruh Karyawan (1 s/d 30 September 2026)
        $this->command->info('3. Membuat jadwal shift sebulan penuh (1 s/d 30 September 2026)...');
        $schedulesBatch = [];
        $opShiftRotations = [
            ['P2', 'P3', 'S1', 'S2', 'off', 'off'],
            ['P3', 'P4', 'S1', 'M1', 'off', 'off'],
            ['P4', 'P5', 'S2', 'M1', 'off', 'off'],
            ['P2', 'P4', 'S1', 'off', 'P3', 'off'],
        ];

        foreach ($users as $uIndex => $user) {
            $roleName = $user->roleRelation->name ?? $user->role ?? '';
            $isOfficeRole = in_array($roleName, ['Admin', 'Finance', 'SPV Bge', 'Head Of Airport Service', 'Controller', 'Quality Control']);
            $rotPattern = $opShiftRotations[$uIndex % count($opShiftRotations)];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $curDate = $startDate->copy()->day($d);
                $curDateStr = $curDate->toDateString();
                $dayOfWeek = $curDate->dayOfWeek; // 0=Sun, 6=Sat

                if ($isOfficeRole) {
                    // Senin - Jumat kerja shift P4 (08:00 - 18:00), Sabtu & Minggu off
                    $shiftId = in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ? 'off' : 'P4';
                } else {
                    // Operasional: Rotasi dinamis 6 hari
                    $rotIdx = ($d + ($uIndex * 2)) % count($rotPattern);
                    $shiftId = $rotPattern[$rotIdx];
                }

                $schedulesBatch[] = [
                    'user_id'   => $user->id,
                    'date'      => $curDateStr,
                    'shift_id'  => $shiftId,
                    'is_active' => 1,
                ];
            }
        }

        foreach (array_chunk($schedulesBatch, 500) as $chunk) {
            Schedule::insert($chunk);
        }

        // 7. Buat Data Presensi Realtime (1 s/d 29 September 2026)
        $this->command->info('4. Membuat data absensi check-in & check-out realtime (1 s/d 29 September)...');
        $attendancesBatch = [];

        foreach ($users as $uIndex => $user) {
            $stationCode = $user->employee->station_id ?? $user->station ?? 'CGK';
            $coords = $stationCoords[$stationCode] ?? $stationCoords['CGK'];
            $roleName = $user->roleRelation->name ?? $user->role ?? '';
            $isOfficeRole = in_array($roleName, ['Admin', 'Finance', 'SPV Bge', 'Head Of Airport Service', 'Controller', 'Quality Control']);
            $rotPattern = $opShiftRotations[$uIndex % count($opShiftRotations)];

            // Loop tanggal 1 s/d 29 (hari ini)
            for ($d = 1; $d <= 29; $d++) {
                $curDate = $startDate->copy()->day($d);
                $curDateStr = $curDate->toDateString();
                $isToday = ($d === 29);
                $key = $user->id . '_' . $curDateStr;

                // Tentukan shift hari ini
                if ($isOfficeRole) {
                    $shiftId = in_array($curDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]) ? 'off' : 'P4';
                } else {
                    $rotIdx = ($d + ($uIndex * 2)) % count($rotPattern);
                    $shiftId = $rotPattern[$rotIdx];
                }

                // Jika hari libur, lewati absensi
                if ($shiftId === 'off') {
                    continue;
                }

                // Jika karyawan sedang cuti, lewati absensi
                if (isset($leavePlans[$key])) {
                    continue;
                }

                // Jika karyawan alpa (tidak hadir), lewati absensi
                if (isset($alpaPlans[$key])) {
                    continue;
                }

                $shiftInfo = $shiftTimes[$shiftId] ?? $shiftTimes['P4'];
                $shiftStart = Carbon::parse($curDateStr . ' ' . $shiftInfo['start']);
                $shiftEnd = Carbon::parse($curDateStr . ' ' . $shiftInfo['end']);
                if ($shiftInfo['cross']) {
                    $shiftEnd->addDay();
                }

                // Variasi koordinat GPS wajar di area bandara (+- 0.0010)
                $latOffset = (mt_rand(-12, 12) / 10000);
                $lngOffset = (mt_rand(-12, 12) / 10000);
                $inLat = number_format($coords['lat'] + $latOffset, 7, '.', '');
                $inLng = number_format($coords['lng'] + $lngOffset, 7, '.', '');
                $outLat = number_format($coords['lat'] + (mt_rand(-12, 12) / 10000), 7, '.', '');
                $outLng = number_format($coords['lng'] + (mt_rand(-12, 12) / 10000), 7, '.', '');

                // Tentukan tepat waktu atau telat (~12% telat)
                $isLate = (($uIndex + $d * 7) % 8 === 0);

                if ($isLate) {
                    $lateMinutes = mt_rand(4, 28);
                    $checkInTime = $shiftStart->copy()->addMinutes($lateMinutes)->addSeconds(mt_rand(1, 59));
                    $status = 'Terlambat';
                    $lateNotes = ['Lalu lintas padat', 'Pemeriksaan keamanan bandara antre', 'Cuaca hujan deras di jalan', null][mt_rand(0, 3)];
                } else {
                    $earlyMinutes = mt_rand(5, 25);
                    $checkInTime = $shiftStart->copy()->subMinutes($earlyMinutes)->addSeconds(mt_rand(1, 59));
                    $status = 'Tepat Waktu';
                    $lateNotes = null;
                }

                // Tentukan Check-out Time
                $checkOutTime = null;
                if (!$isToday) {
                    // Hari kemarin dan sebelumnya: sudah check-out (pulang lebih 5 s/d 30 menit setelah shift selesai)
                    $checkOutTime = $shiftEnd->copy()->addMinutes(mt_rand(5, 30))->addSeconds(mt_rand(1, 59));
                } else {
                    // Hari ini (29 September 2026, Jam 21:57 WIB):
                    if ($shiftEnd->lte($currentTime)) {
                        // Shift sudah selesai sebelum 21:57 -> sudah check-out
                        $checkOutTime = $shiftEnd->copy()->addMinutes(mt_rand(5, 25))->addSeconds(mt_rand(1, 59));
                    } elseif ($shiftStart->lte($currentTime)) {
                        // Shift sedang berlangsung sekarang (misal S1 pulang 23:30, S2 pulang 02:00, M1 pulang 06:00)
                        // Karyawan sudah masuk, tapi BELUM check-out (check_out_time = null)
                        $checkOutTime = null;
                    } else {
                        // Shift belum mulai (misal M2 masuk 23:00) -> belum absen
                        continue;
                    }
                }

                $cinTimestamp = $checkInTime->timestamp;
                $coutTimestamp = $checkOutTime ? $checkOutTime->timestamp : null;

                $attendancesBatch[] = [
                    'user_id'             => $user->id,
                    'station'             => $stationCode,
                    'check_in_time'       => $checkInTime->format('Y-m-d H:i:s'),
                    'check_out_time'      => $checkOutTime ? $checkOutTime->format('Y-m-d H:i:s') : null,
                    'status'              => $status,
                    'check_in_latitude'   => $inLat,
                    'check_in_longitude'  => $inLng,
                    'check_out_latitude'  => $checkOutTime ? $outLat : null,
                    'check_out_longitude' => $checkOutTime ? $outLng : null,
                    'check_in_ip'         => '192.168.1.' . mt_rand(10, 240),
                    'check_out_ip'        => $checkOutTime ? '192.168.1.' . mt_rand(10, 240) : null,
                    'check_in_notes'      => $lateNotes,
                    'check_out_notes'     => null,
                    'check_in_photo'      => 'attendance_' . $user->id . '_' . $cinTimestamp . '.png',
                    'check_out_photo'     => $coutTimestamp ? 'attendance_' . $user->id . '_' . $coutTimestamp . '.png' : null,
                    'created_at'          => $checkInTime,
                    'updated_at'          => $checkOutTime ?: $checkInTime,
                ];
            }
        }

        foreach (array_chunk($attendancesBatch, 400) as $chunk) {
            Attendance::insert($chunk);
        }

        // 8. Buat Record Koreksi Absensi yang Disetujui
        $this->command->info('5. Mendaftarkan koreksi absensi yang disetujui...');
        foreach ($correctionPlans as $cp) {
            AttendanceCorrection::create([
                'user_id'                 => $cp['user_id'],
                'station'                 => $cp['station'],
                'attendance_date'         => $cp['date'],
                'proposed_check_in_time'  => $cp['date'] . ' 07:55:00',
                'proposed_check_out_time' => $cp['date'] . ' 17:05:00',
                'reason'                  => 'Kamera presensi smartphone sempat error saat scan wajah',
                'status'                  => 'approved',
                'decided_by'              => $approverId,
                'decided_at'              => $cp['date'] . ' 18:30:00',
            ]);
        }

        $totalSched = Schedule::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->count();
        $totalAtt = Attendance::whereBetween('check_in_time', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])->count();
        $totalLeaves = Leave::whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])->count();
        $totalCorr = AttendanceCorrection::whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])->count();

        $this->command->info('========================================================');
        $this->command->info("SELESAI! Rekap Data September 2026 Berhasil Dibuat:");
        $this->command->info("- Total Jadwal Shift (1-30 Sep) : {$totalSched} baris");
        $this->command->info("- Total Presensi (1-29 Sep)     : {$totalAtt} transaksi");
        $this->command->info("- Total Cuti Disetujui          : {$totalLeaves} pengajuan");
        $this->command->info("- Total Koreksi Disetujui       : {$totalCorr} pengajuan");
        $this->command->info('========================================================');
    }
}
