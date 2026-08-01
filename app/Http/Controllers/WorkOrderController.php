<?php

namespace App\Http\Controllers;

use App\Models\Station;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Flights;
use App\Models\Flight_details;
use App\Models\Schedule;
use App\Imports\WorkOrderImport;
use App\Exports\WorkOrderTemplateExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Maatwebsite\Excel\Facades\Excel;

class WorkOrderController extends Controller
{
    /**
     * Display a listing of the work orders.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $stations = Station::select('id', 'code', 'name')->where('is_active', true)->get();

        $query = WorkOrder::with([
            'users:id,fullname,station',
            'submittedBy:id,fullname'
        ]);

        // Role-based visibility
        if ($user->hasRole('Admin')) {
            if ($request->filled('station') && $request->station !== 'All') {
                $query->where('station', $request->station);
            }
        } elseif ($user->hasRole(WorkOrder::LEADER_ROLES)) {
            $query->where('submitted_by', $user->id);
        } else {
            $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
        }

        // Type Filter (DCI / DCE)
        if ($request->filled('type') && in_array($request->type, ['DCI', 'DCE'])) {
            $query->where('type', $request->type);
        }

        // Date Range Filter
        $dateFrom = $request->input('date_from', $request->input('date', now()->startOfMonth()->toDateString()));
        $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('aircraft_reg', 'like', "%{$search}%")
                  ->orWhere('wo_number', 'like', "%{$search}%")
                  ->orWhere('ex_flight', 'like', "%{$search}%")
                  ->orWhere('to_flight', 'like', "%{$search}%");
            });
        }

        $workOrders = $query
            ->orderByRaw("CASE WHEN photo_path IS NULL OR photo_path = '' THEN 0 ELSE 1 END ASC")
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $workResults = $workOrders;

        return view('work_order.index', compact('workOrders', 'workResults', 'stations', 'dateFrom', 'dateTo'));
    }

    /**
     * Show the form for creating a new work order.
     */
    public function create()
    {
        $user = auth()->user();
        if (!$user->hasRole(WorkOrder::LEADER_ROLES)) {
            abort(403, 'Akses ditolak. Hanya Admin dan Leader yang dapat menginput pekerjaan.');
        }
        $stations = Station::where('is_active', true)->get();

        $staffQuery = User::where('is_active', true);
        if (!$user->isAdmin() && $user->station) {
            $staffQuery->where('station', $user->station);
        }

        $scheduledUserIds = Schedule::whereDate('date', '>=', now()->subDays(7)->toDateString())
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $allStaffs = $staffQuery->orderBy('fullname')->get(['id', 'fullname', 'station']);

        $staffs = $allStaffs->map(function ($s) use ($scheduledUserIds) {
            $s->is_scheduled = in_array($s->id, $scheduledUserIds);
            return $s;
        });

        $flightQuery = Flights::with(['details.schedule.user']);
        if (!$user->isAdmin() && $user->station) {
            $flightQuery->where('station', $user->station);
        }
        $availableFlights = $flightQuery->orderBy('created_at', 'desc')->take(30)->get();

        return view('work_order.create', compact('stations', 'staffs', 'availableFlights'));
    }

    /**
     * Download sample Excel template for importing work orders.
     */
    public function downloadTemplate()
    {
        return Excel::download(new WorkOrderTemplateExport, 'Template_Import_Pekerjaan_APS.xlsx');
    }

    /**
     * Store a newly created work order in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole(WorkOrder::LEADER_ROLES)) {
            abort(403, 'Akses ditolak. Hanya Admin dan Leader yang dapat menginput pekerjaan.');
        }

        $request->validate([
            'date'           => 'required|date',
            'station'        => 'required|string',
            'aircraft_reg'   => 'required|string|max:50',
            'ex_flight'      => 'nullable|string|max:50',
            'to_flight'      => 'nullable|string|max:50',
            'parking_stand'  => 'required|string|max:50',
            'wo_number'      => 'nullable|string|max:100',
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i|after:start_time',
            'photo'          => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'staff_members'  => 'required|array|min:2|max:10',
            'staff_members.*'=> 'required|exists:users,id',
            'action'         => 'required|in:DCI,DCE',
        ], [
            'end_time.after'     => 'End Time harus setelah Start Time.',
            'staff_members.min'  => 'Staff pendukung minimal harus 2 orang.',
            'staff_members.max'  => 'Staff pendukung maksimal 10 orang.',
            'photo.max'          => 'Foto bukti maksimal berukuran 2MB.',
            'photo.image'        => 'File harus berupa gambar (jpg, jpeg, png).'
        ]);

        try {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('work_orders', 'public');
            }

            // WO number is user-provided and optional; null if left blank
            $woNumber = !empty(trim((string) $request->wo_number))
                ? strtoupper(trim($request->wo_number))
                : null;

            $workOrder = WorkOrder::create([
                'date'         => $request->date,
                'station'      => $request->station,
                'aircraft_reg' => strtoupper($request->aircraft_reg),
                'ex_flight'    => $request->ex_flight ?: '-',
                'to_flight'    => $request->to_flight ?: '-',
                'parking_stand'=> $request->parking_stand,
                'wo_number'    => $woNumber,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'photo_path'   => $photoPath,
                'type'         => $request->action,
                'submitted_by' => auth()->id(),
            ]);

            $workOrder->users()->sync($request->staff_members);

            try {
                $exFlight = trim((string)$request->ex_flight);
                $toFlight = trim((string)$request->to_flight);
                $reg = strtoupper(trim((string)$request->aircraft_reg));
                
                $flightNum = ($exFlight !== '' && $exFlight !== '-') 
                    ? strtoupper($exFlight) 
                    : (($toFlight !== '' && $toFlight !== '-') ? strtoupper($toFlight) : $reg);

                $station = $request->station;
                $flightDate = $request->date;
                $startTime = strlen($request->start_time) === 5 ? $request->start_time . ':00' : $request->start_time;
                $endTime = strlen($request->end_time) === 5 ? $request->end_time . ':00' : $request->end_time;
                $createdDateTime = \Carbon\Carbon::parse($flightDate . ' ' . $startTime);

                $airline = 'Airlines';
                if (preg_match('/^(MH|MAS)/i', $flightNum)) $airline = 'Malaysia Airlines';
                elseif (preg_match('/^(QZ|AK|FD|D7)/i', $flightNum)) $airline = 'AirAsia';
                elseif (preg_match('/^(JT|LNI)/i', $flightNum)) $airline = 'Lion Air';
                elseif (preg_match('/^(GA|GIA)/i', $flightNum)) $airline = 'Garuda Indonesia';
                elseif (preg_match('/^(QG|CTV)/i', $flightNum)) $airline = 'Citilink';
                elseif (preg_match('/^(ID|BTK)/i', $flightNum)) $airline = 'Batik Air';
                elseif (preg_match('/^(SJ|IN)/i', $flightNum)) $airline = 'Sriwijaya Air';

                $existingFlight = Flights::where('station', $station)
                    ->where(function($q) use ($flightNum, $reg) {
                        $q->where('flight_number', $flightNum)
                          ->orWhere('registasi', $reg);
                    })
                    ->whereDate('created_at', $flightDate)
                    ->first();

                if ($existingFlight) {
                    $existingFlight->update([
                        'airline'     => $airline !== 'Airlines' ? $airline : $existingFlight->airline,
                        'flight_number' => $flightNum,
                        'registasi'   => $reg,
                        'type'        => $request->action,
                        'arrival'     => $startTime,
                        'time_count'  => $endTime,
                        'status'      => 1,
                    ]);
                    $flight = $existingFlight;
                } else {
                    $flight = Flights::create([
                        'airline'       => $airline,
                        'flight_number' => $flightNum,
                        'registasi'     => $reg,
                        'type'          => $request->action,
                        'arrival'       => $startTime,
                        'time_count'    => $endTime,
                        'station'       => $station,
                        'status'        => 1,
                        'created_at'    => $createdDateTime,
                    ]);
                }

                if ($flight && $request->has('staff_members')) {
                    foreach ($request->staff_members as $staffId) {
                        $sch = Schedule::where('user_id', $staffId)
                            ->whereDate('date', $flightDate)
                            ->first();

                        if ($sch) {
                            Flight_details::firstOrCreate([
                                'flight_id'   => $flight->id,
                                'schedule_id' => $sch->id,
                            ]);
                        }
                    }
                }
            } catch (\Exception $fe) {
                Log::warning('Auto sync flight error: ' . $fe->getMessage());
            }

            Alert::success('Berhasil', 'Data Work Order (' . ($request->action === 'DCI' ? 'Deep Cleaning Interior' : 'Deep Cleaning Exterior') . ') berhasil disimpan.');
            return redirect()->route('work_orders.index');
        } catch (\Exception $e) {
            Log::error('Error saat menyimpan work order: ' . $e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Display detail of a single work order.
     */
    public function show($id)
    {
        $workOrder = WorkOrder::with(['users', 'submittedBy'])->findOrFail($id);
        $workResult = $workOrder;
        return view('work_order.show', compact('workOrder', 'workResult'));
    }

    /**
     * Export work orders to PDF report.
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole(WorkOrder::LEADER_ROLES)) {
            abort(403, 'Akses ditolak. Staff hanya memiliki hak akses untuk melihat data pekerjaan.');
        }

        $query = WorkOrder::with(['users', 'submittedBy']);

        if ($user->hasRole('Admin')) {
            if ($request->filled('station') && $request->station !== 'All') {
                $query->where('station', $request->station);
            }
        } elseif ($user->hasRole(WorkOrder::LEADER_ROLES)) {
            $query->where('submitted_by', $user->id);
        } else {
            $query->whereHas('users', fn($q) => $q->where('users.id', $user->id));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('type') && in_array($request->type, ['DCI', 'DCE'])) {
            $query->where('type', $request->type);
        }

        $workOrders = $query->orderBy('date', 'asc')->orderBy('start_time', 'asc')->get();
        $workResults = $workOrders;

        $stationLabel = $request->filled('station') && $request->station !== 'All'
            ? $request->station
            : 'Semua Station';

        $periodLabel = '';
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $periodLabel = \Carbon\Carbon::parse($request->date_from)->translatedFormat('d M Y')
                . ' s/d ' . \Carbon\Carbon::parse($request->date_to)->translatedFormat('d M Y');
        } elseif ($request->filled('date_from')) {
            $periodLabel = 'Mulai ' . \Carbon\Carbon::parse($request->date_from)->translatedFormat('d M Y');
        } else {
            $periodLabel = 'Semua Periode';
        }

        $logoPath = public_path('storage/photo/JAS Airport Services.png');
        $base64Logo = '';
        if (file_exists($logoPath)) {
            $base64Logo = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdfView = view()->exists('work_order.pdf') ? 'work_order.pdf' : 'work_result.pdf';
        $pdf = Pdf::loadView($pdfView, compact(
            'workOrders',
            'workResults',
            'stationLabel',
            'periodLabel',
            'base64Logo',
            'user'
        ))->setPaper('a4', 'landscape');

        $filename = 'Laporan-WO-' . str_replace(' ', '-', $stationLabel) . '-' . now()->format('Ymd-His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export a single work order to formal PDF report.
     */
    public function exportSinglePdf($id)
    {
        $user = auth()->user();
        if (!$user->hasRole(WorkOrder::LEADER_ROLES)) {
            abort(403, 'Akses ditolak. Staff hanya memiliki hak akses untuk melihat data pekerjaan.');
        }

        $workOrder = WorkOrder::with(['users', 'submittedBy'])->findOrFail($id);
        $workResult = $workOrder;

        if (!$workOrder->photo_path) {
            Alert::error('Cetak Gagal', 'Laporan PDF tidak dapat dicetak karena foto bukti pekerjaan belum diunggah.');
            return redirect()->back();
        }

        $logoPath = public_path('storage/aps_mini.png');
        if (!file_exists($logoPath)) {
            $logoPath = storage_path('app/public/aps_mini.png');
        }
        $base64Logo = '';
        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $base64Logo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $base64Photo = '';
        if ($workOrder->photo_path) {
            $fullPhotoPath = storage_path('app/public/' . $workOrder->photo_path);
            if (!file_exists($fullPhotoPath)) {
                $fullPhotoPath = public_path('storage/' . $workOrder->photo_path);
            }
            if (file_exists($fullPhotoPath)) {
                $mime = mime_content_type($fullPhotoPath) ?: 'image/jpeg';
                $base64Photo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPhotoPath));
            }
        }

        $singlePdfView = view()->exists('work_order.single_pdf') ? 'work_order.single_pdf' : 'work_result.single_pdf';
        $pdf = Pdf::loadView($singlePdfView, compact('workOrder', 'workResult', 'base64Logo', 'base64Photo'))
            ->setPaper('a4', 'portrait');

        $filename = 'Hardcopy-WO-' . $workOrder->wo_number . '-' . str_replace(' ', '-', $workOrder->aircraft_reg) . '.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Upload or update evidence photo for a Work Order.
     */
    public function uploadPhoto(Request $request, $id)
    {
        $workOrder = WorkOrder::findOrFail($id);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ], [
            'photo.required' => 'Foto bukti wajib dipilih.',
            'photo.image' => 'File harus berupa gambar (JPG, JPEG, PNG).',
            'photo.max' => 'Ukuran foto bukti tidak boleh melebihi 2MB.',
        ]);

        if ($request->hasFile('photo')) {
            if ($workOrder->photo_path && Storage::disk('public')->exists($workOrder->photo_path)) {
                Storage::disk('public')->delete($workOrder->photo_path);
            }
            $path = $request->file('photo')->store('work_orders', 'public');
            $workOrder->update(['photo_path' => $path]);
        }

        Alert::success('Berhasil', 'Foto bukti pekerjaan WO ' . $workOrder->wo_number . ' berhasil diunggah.');
        return redirect()->back();
    }

    /**
     * Import work orders from an Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:5120',
        ], [
            'file.required' => 'Silakan pilih file Excel terlebih dahulu.',
            'file.mimes' => 'Format file meharus berupa Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran file Excel maksimal 5MB.'
        ]);

        try {
            Excel::import(new WorkOrderImport, $request->file('file'));
            Alert::success('Berhasil', 'Data Work Order berhasil diimpor secara bulk.');
        } catch (\Exception $e) {
            Log::error('Error saat import excel work orders: ' . $e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan saat mengimpor data. Pastikan format kolom Excel sesuai.');
        }

        return redirect()->route('work_orders.index');
    }

    /**
     * Remove the specified work order from storage.
     */
    public function destroy($id)
    {
        try {
            $workOrder = WorkOrder::findOrFail($id);

            if (!empty($workOrder->photo_path)) {
                Alert::error('Gagal Hapus', 'Data pekerjaan yang sudah berstatus SELESAI tidak dapat dihapus.');
                return redirect()->route('work_orders.index');
            }

            if ($workOrder->photo_path && Storage::disk('public')->exists($workOrder->photo_path)) {
                Storage::disk('public')->delete($workOrder->photo_path);
            }
            $workOrder->users()->detach();
            $workOrder->delete();

            Alert::success('Berhasil', 'Data Work Order berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Error saat menghapus work order: ' . $e->getMessage());
            Alert::error('Gagal', 'Terjadi kesalahan saat menghapus data.');
        }

        return redirect()->route('work_orders.index');
    }

    /**
     * Fetch live flight arrivals from Flightradar24 API for selected station & aircraft search.
     */
    public function fetchFlightData(Request $request)
    {
        $query = trim($request->input('aircraft_reg') ?: $request->input('query_str') ?: '');
        $station = strtoupper(trim($request->input('station') ?: 'CGK'));

        if (!$query && !$station) {
            return response()->json(['success' => false, 'message' => 'Pilih station atau ketik registrasi pesawat.'], 400);
        }

        $cleanQuery = $query ? strtoupper(str_replace([' ', '-'], '', $query)) : '';
        $frFlights = [];

        try {
            $stationLower = strtolower($station);

            $fetchUrl = function($url) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
                    CURLOPT_HTTPHEADER     => [
                        'Accept: application/json, text/plain, */*',
                        'Accept-Language: en-US,en;q=0.9',
                        'Cache-Control: no-cache',
                        'Origin: https://www.flightradar24.com',
                        'Referer: https://www.flightradar24.com/'
                    ]
                ]);
                $output = curl_exec($ch);
                curl_close($ch);

                if (!$output) {
                    $opts = [
                        'http' => [
                            'method' => 'GET',
                            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\nAccept: application/json\r\nReferer: https://www.flightradar24.com/\r\nOrigin: https://www.flightradar24.com\r\n",
                            'timeout' => 10
                        ],
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false
                        ]
                    ];
                    $context = stream_context_create($opts);
                    $output = @file_get_contents($url, false, $context);
                }
                return $output;
            };

            $responseArr = $fetchUrl("https://api.flightradar24.com/common/v1/airport.json?code={$stationLower}&plugin[]=&plugin-setting[schedule][mode]=arrivals&limit=100");
            $responseDep = $fetchUrl("https://api.flightradar24.com/common/v1/airport.json?code={$stationLower}&plugin[]=&plugin-setting[schedule][mode]=departures&limit=100");

            $depMap = [];
            if ($responseDep) {
                $jsonDep = json_decode($responseDep, true);
                $departuresData = $jsonDep['result']['response']['airport']['pluginData']['schedule']['departures']['data'] ?? [];
                foreach ($departuresData as $depItem) {
                    $dFl = $depItem['flight'] ?? [];
                    $dReg = $dFl['aircraft']['registration'] ?? '';
                    $dFlightNo = $dFl['identification']['number']['default'] ?? ($dFl['identification']['callsign'] ?? '');
                    if ($dReg && $dFlightNo) {
                        $cleanDReg = strtoupper(str_replace([' ', '-'], '', $dReg));
                        $depMap[$cleanDReg] = strtoupper($dFlightNo);
                    }
                }
            }

            if ($responseArr) {
                $json = json_decode($responseArr, true);
                $arrivalsData = $json['result']['response']['airport']['pluginData']['schedule']['arrivals']['data'] ?? [];

                foreach ($arrivalsData as $item) {
                    $fl = $item['flight'] ?? [];
                    $reg = $fl['aircraft']['registration'] ?? '';
                    $flightNo = $fl['identification']['number']['default'] ?? ($fl['identification']['callsign'] ?? '-');
                    $ts = $fl['time']['real']['arrival'] ?? $fl['time']['estimated']['arrival'] ?? $fl['time']['scheduled']['arrival'] ?? null;
                    
                    $cleanReg = strtoupper(str_replace([' ', '-'], '', $reg));
                    $toFlightNo = ($cleanReg && isset($depMap[$cleanReg])) ? $depMap[$cleanReg] : '-';

                    $tz = new \DateTimeZone('Asia/Jakarta');
                    if ($ts) {
                        $dt = new \DateTime('@' . $ts);
                        $dt->setTimezone($tz);
                        $startStr = $dt->format('H:i');
                        $dtEnd = clone $dt;
                        $dtEnd->modify('+30 minutes');
                        $endStr = $dtEnd->format('H:i');
                    } else {
                        $dt = new \DateTime('now', $tz);
                        $startStr = $dt->format('H:i');
                        $dtEnd = clone $dt;
                        $dtEnd->modify('+30 minutes');
                        $endStr = $dtEnd->format('H:i');
                    }
                    $originName = $fl['airport']['origin']['position']['region']['city'] 
                               ?? $fl['airport']['origin']['name'] 
                               ?? ($fl['airport']['origin']['code']['iata'] ?? '');

                    if ($cleanQuery) {
                        $cleanFlight = strtoupper(str_replace([' ', '-'], '', $flightNo));
                        if (!str_contains($cleanReg, $cleanQuery) && !str_contains($cleanFlight, $cleanQuery)) {
                            continue;
                        }
                    }

                    $frFlights[] = [
                        'aircraft_reg' => strtoupper($reg ?: ''),
                        'ex_flight' => strtoupper($flightNo),
                        'to_flight' => $toFlightNo,
                        'station' => $station,
                        'start_time' => $startStr,
                        'end_time' => $endStr,
                        'origin' => $originName !== '-' ? $originName : '',
                        'airline' => ($fl['airline']['name'] ?? '') !== 'Airlines' ? ($fl['airline']['name'] ?? '') : '',
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Flightradar24 Live Arrivals/Departures API warning: ' . $e->getMessage());
        }

        if (count($frFlights) > 0) {
            return response()->json([
                'success' => true,
                'source' => 'Flightradar24 Live Arrivals',
                'flights' => $frFlights,
                'data' => $frFlights[0]
            ]);
        }

        $flightsQuery = \App\Models\Flights::query();

        if ($station && \Illuminate\Support\Facades\Schema::hasColumn('flights', 'station')) {
            $flightsQuery->whereRaw("UPPER(station) = ?", [$station]);
        }

        if ($cleanQuery) {
            $flightsQuery->where(function($q) use ($cleanQuery) {
                $q->whereRaw("REPLACE(REPLACE(UPPER(registasi), '-', ''), ' ', '') = ?", [$cleanQuery])
                  ->orWhereRaw("REPLACE(REPLACE(UPPER(flight_number), '-', ''), ' ', '') = ?", [$cleanQuery]);
            });
        }

        $matchedFlights = $flightsQuery->orderBy('created_at', 'desc')->take(100)->get();

        $dbFlightList = [];

        foreach ($matchedFlights as $f) {
            $arrivalTime = $f->arrival ? substr($f->arrival, 0, 5) : '';
            $startH = 14;
            $startM = 30;
            if ($arrivalTime) {
                $parts = explode(':', $arrivalTime);
                $startH = isset($parts[0]) ? (int)$parts[0] : 14;
                $startM = isset($parts[1]) ? (int)$parts[1] : 30;
            }

            $totalMin = $startH * 60 + $startM + 30;
            $endH = sprintf('%02d', floor($totalMin / 60) % 24);
            $endM = sprintf('%02d', $totalMin % 60);

            $staffIds = [];
            if ($f->details) {
                foreach ($f->details as $detail) {
                    if ($detail->schedule && $detail->schedule->user) {
                        $staffIds[] = $detail->schedule->user->id;
                    }
                }
            }

            $dbFlightList[] = [
                'aircraft_reg' => strtoupper($f->registasi ?: ''),
                'ex_flight' => $f->flight_number ?: '',
                'to_flight' => $f->to_flight ?: '',
                'station' => $f->station ?: ($station ?: ''),
                'arrival' => $f->arrival ?: '',
                'start_time' => $arrivalTime ?: sprintf('%02d:%02d', $startH, $startM),
                'end_time' => sprintf('%s:%s', $endH, $endM),
                'airline' => ($f->airline && $f->airline !== 'Airlines') ? $f->airline : '',
                'origin' => ($f->origin && $f->origin !== '-') ? $f->origin : '',
                'staff_ids' => $staffIds
            ];
        }

        if (count($dbFlightList) > 0) {
            return response()->json([
                'success' => true,
                'source' => 'Database Sistem',
                'flights' => $dbFlightList,
                'data' => $dbFlightList[0]
            ]);
        }

        $fallbackData = [
            'aircraft_reg' => strtoupper($query ?: 'PK-LGH'),
            'ex_flight' => $query ? strtoupper($query) : '-',
            'to_flight' => '-',
            'station' => $station ?: 'CGK',
            'start_time' => date('H:i'),
            'end_time' => date('H:i', strtotime('+30 minutes')),
        ];

        return response()->json([
            'success' => true,
            'source' => 'Manual Input',
            'flights' => [$fallbackData],
            'data' => $fallbackData
        ]);
    }
}
