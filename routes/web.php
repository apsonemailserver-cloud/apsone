<?php

use Illuminate\Support\Facades\Route;

// --- DAFTAR SEMUA CONTROLLER DI SINI ---
use App\Http\Controllers\OvertimeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\BlacklistController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\AdminTrainingCertificateController;
use App\Http\Controllers\AdminDocumentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\RoleController;
// Master Data Controllers (per table)
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SubUnitController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\ClusterController;
// Master Leave Controllers (per table)
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeaveRuleController;
use App\Http\Controllers\FaceSampleController;
use App\Models\Blacklist;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =================================================================
// 1. GUEST ROUTES (LOGIN, OTP, LUPA PASSWORD)
// =================================================================
Route::get('/', [LoginController::class, 'login'])->name('login');
Route::post('actionlogin', [LoginController::class, 'actionlogin'])->name('actionlogin');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// OTP
Route::get('/verify-otp', [LoginController::class, 'showOtpForm'])->name('verify.otp.form');
Route::post('/verify-otp', [LoginController::class, 'verifyOtp'])->name('verify.otp');
Route::post('/resend-otp', [LoginController::class, 'resendOtp'])->name('resend.otp');

// Forgot Password
Route::get('/forgot-password', [LoginController::class, 'showForgotPasswordForm'])->name('forgot.password.form');
Route::post('/forgot-password', [LoginController::class, 'sendForgotPassword'])->name('forgot.password.send');
Route::post('/forgot-password/verify', [LoginController::class, 'verifyForgotPassword'])->name('forgot.password.verify');
Route::get('/forgot-password/change', [LoginController::class, 'showChangePasswordForm'])->name('change.password.form');
Route::post('/forgot-password/change', [LoginController::class, 'changePassword'])->name('forgot.password.update');


// =================================================================
// 2. AUTH ROUTES (HARUS LOGIN DULU)
// =================================================================
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD & UMUM ---
    Route::get('home', [HomeController::class, 'index'])->name('home');
    Route::get('document', [HomeController::class, 'document'])->name('document');
    Route::get('document/{document}/download', [HomeController::class, 'downloadDocument'])->name('document.download');
    Route::get('admin/documents', [AdminDocumentController::class, 'index'])->name('admin.documents.index');
    Route::get('admin/documents/create', [AdminDocumentController::class, 'create'])->name('admin.documents.create');
    Route::post('admin/documents', [AdminDocumentController::class, 'store'])->name('admin.documents.store');
    Route::get('admin/documents/{document}/edit', [AdminDocumentController::class, 'edit'])->name('admin.documents.edit');
    Route::put('admin/documents/{document}', [AdminDocumentController::class, 'update'])->name('admin.documents.update');
    Route::delete('admin/documents/{document}', [AdminDocumentController::class, 'destroy'])->name('admin.documents.destroy');
    Route::get('/surat', [HomeController::class, 'generatePDF'])->name('surat.generate');
    Route::get('actionlogout', [LoginController::class, 'actionlogout'])->name('actionlogout');

    // Change Password & Profile Picture
    Route::get('/change-password', [LoginController::class, 'showChangePasswordForm'])->name('password.change.show');
    Route::post('/update-password', [LoginController::class, 'updatePassword'])->name('update.password');
    Route::post('/update-photo/{userId}', [UserController::class, 'updatePhoto'])->name('user.updatePhoto');
    Route::get('/profile/{id}', [UserController::class, 'profile'])->name('users.profile');
    Route::get('/user/profile/{id}', [UserController::class, 'userProfile'])->name('users.userProfile');

    // --- USER MANAGEMENT (CRUD) ---
    Route::get('/get-superiors-by-station', [UserController::class, 'getSuperiorsByStation'])->name('users.superiors');
    Route::get('/users/apron', [UserController::class, 'indexApron'])->name('users.apron');
    Route::get('/users/bge', [UserController::class, 'indexBGE'])->name('users.bge');
    Route::get('/users/office', [UserController::class, 'indexOffice'])->name('users.office');
    Route::resource('users', UserController::class);
    Route::put('/reset-password/{id}', [UserController::class, 'resetPassword'])->name('user.resetPassword');

    // Face Recognition Sample Management (Admin)
    Route::get('/users/{user}/face-samples', [FaceSampleController::class, 'index'])->name('users.face-samples.index');
    Route::post('/users/{user}/face-samples', [FaceSampleController::class, 'store'])->name('users.face-samples.store');
    Route::post('/users/{user}/face-samples/upload', [FaceSampleController::class, 'storeFile'])->name('users.face-samples.store-file');
    Route::delete('/users/{user}/face-samples', [FaceSampleController::class, 'destroy'])->name('users.face-samples.destroy');

    // Kontrak User
    Route::get('/kontrak', [UserController::class, 'kontrak'])->name('users.kontrak');
    Route::get('/kontrak/edit/{id}', [UserController::class, 'KontrakEdit'])->name('users.KontrakEdit');
    Route::put('/kontrak/update/{user}', [UserController::class, 'KontrakUpdate'])->name('users.KontrakUpdate');

    // PAS Bandara User
    Route::get('/pas', [UserController::class, 'pas'])->name('users.pas');
    Route::get('/pas/edit/{id}', [UserController::class, 'PASEdit'])->name('users.PASEdit');
    Route::put('/pas/update/{user}', [UserController::class, 'PASUpdate'])->name('users.PASUpdate');

    // --- MONITORING STAFF (IMPORT/EXPORT) ---
    // Pastikan StaffController sudah dibuat dan di-import di atas
    Route::get('/staff-data', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/export', [StaffController::class, 'export'])->name('staff.export');
    Route::post('/staff/import', [StaffController::class, 'import'])->name('staff.import');
    Route::get('/staff/template', [StaffController::class, 'template'])->name('staff.template');

    Route::get('/blacklist-data', [BlacklistController::class, 'index'])->name('blacklist.data');

    // switch on off staff
    Route::post('/staff/toggle/{id}', [StaffController::class, 'toggleStatus'])->name('staff.toggle');

    // --- STATION MANAGEMENT (KILL SWITCH) ---
    Route::get('/stations', [StationController::class, 'index'])->name('stations.index');
    Route::get('/stations/create', [StationController::class, 'create'])->name('stations.create');
    Route::post('/stations/store', [StationController::class, 'store'])->name('stations.store');
    Route::post('/stations/toggle/{id}', [StationController::class, 'toggleStatus'])->name('stations.toggle');
    Route::get('/stations/{id}/edit', [StationController::class, 'edit'])->name('stations.edit');
    Route::match(['post', 'put'], '/stations/{station}/update', [StationController::class, 'update'])->name('stations.update');
    Route::delete('/stations/{id}', [StationController::class, 'destroy'])->name('stations.destroy');

    // --- MASTER DATA (per table, masing-masing controller sendiri) ---
    Route::prefix('master-data')->name('master_data.')->group(function () {
        // Units
        Route::resource('units', UnitController::class)->except(['show']);
        // Sub Units
        Route::resource('sub-units', SubUnitController::class)
            ->parameters(['sub-units' => 'subUnit'])
            ->names([
                'index'   => 'sub_units.index',
                'create'  => 'sub_units.create',
                'store'   => 'sub_units.store',
                'edit'    => 'sub_units.edit',
                'update'  => 'sub_units.update',
                'destroy' => 'sub_units.destroy',
            ])
            ->except(['show']);
        // Job Titles
        Route::resource('job-titles', JobTitleController::class)
            ->parameters(['job-titles' => 'jobTitle'])
            ->names([
                'index'   => 'job_titles.index',
                'create'  => 'job_titles.create',
                'store'   => 'job_titles.store',
                'edit'    => 'job_titles.edit',
                'update'  => 'job_titles.update',
                'destroy' => 'job_titles.destroy',
            ])
            ->except(['show']);
        // Clusters
        Route::resource('clusters', ClusterController::class)->except(['show']);
    });

    // --- FLIGHTS ---
    Route::resource('flights', FlightController::class)->only(['index', 'store', 'update']);
    Route::get('/flights/{id}/details', [FlightController::class, 'getDetails'])->name('flights.details');
    Route::get('/flight/{id}/users', [HomeController::class, 'getFlightUsers']);

    // --- SCHEDULES ---
    Route::post('/schedule/auto-create', [ScheduleController::class, 'autoCreate'])->name('schedule.autoCreate');
    Route::post('/schedule/import', [ScheduleController::class, 'import'])->name('schedule.import');
    Route::post('/schedule/update/{userId}/{date}', [ScheduleController::class, 'update'])->name('schedule.update_details');
    Route::post('/schedules/update-active', [ScheduleController::class, 'updateActive'])->name('schedule.updateActive');
    Route::get('/schedule-now', [ScheduleController::class, 'now'])->name('schedule.now');
    Route::get('/schedule/show', [ScheduleController::class, 'show'])->name('schedule.view');
    Route::resource('schedule', ScheduleController::class)->only(['index', 'edit']);

    // --- SHIFTS ---
    Route::get('/shift/next-id', [ShiftController::class, 'getNextId'])->name('shift.next-id');
    Route::resource('shift', ShiftController::class)->except(['show']);

    // --- ATTENDANCE (ABSENSI) ---
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/camera', [AttendanceController::class, 'camera'])->name('attendance.camera'); // Jika pakai kamera
    Route::get('/attendance/face-samples/api', [FaceSampleController::class, 'apiShow'])->name('attendance.face-samples.api');
    Route::post('/attendance/face-samples/save-self', [FaceSampleController::class, 'storeSelf'])->name('attendance.face-samples.save-self');
    Route::post('/attendance/face-verify', [FaceSampleController::class, 'verifyFace'])->name('attendance.face-verify');
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkIn');
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkOut');
    Route::post('/attendance/process', [AttendanceController::class, 'process'])->name('attendance.process'); // Alternatif proses
    Route::get('/attendance/history', [AttendanceController::class, 'history'])->name('attendance.history');
    Route::get('/attendance/history/{date}/correction', [AttendanceCorrectionController::class, 'create'])
        ->name('attendance.corrections.create');
    Route::post('/attendance/history/{date}/correction', [AttendanceCorrectionController::class, 'store'])
        ->name('attendance.corrections.store');
    Route::get('/attendance/corrections/approval', [AttendanceCorrectionController::class, 'approval'])
        ->name('attendance.corrections.approval');
    Route::post('/attendance/corrections/{correction}/approve', [AttendanceCorrectionController::class, 'approve'])
        ->name('attendance.corrections.approve');
    Route::post('/attendance/corrections/{correction}/reject', [AttendanceCorrectionController::class, 'reject'])
        ->name('attendance.corrections.reject');

    // Laporan Absensi
    Route::get('/attendance/reports', [AttendanceController::class, 'reportsIndex'])->name('attendance.reports');
    Route::get('/attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');

    // --- LEAVES (CUTI) ---
    Route::get('/leaves/apply', [LeaveController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::get('/my-leaves', [LeaveController::class, 'myLeaves'])->name('leaves.myLeaves');
    Route::get('/leaves/pengajuan', [LeaveController::class, 'pengajuan'])->name('leaves.pengajuan');
    Route::get('/leaves/approval', [LeaveController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/laporan', [LeaveController::class, 'laporan'])->name('leaves.laporan');
    Route::get('/leaves/balances', [LeaveController::class, 'balances'])->name('leaves.balances');
    Route::patch('leaves/{leave}/status', [LeaveController::class, 'updateStatus'])->name('leaves.updateStatus');
    Route::patch('leaves/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leaves.cancel');
    Route::get('/leaves/export', [LeaveController::class, 'export'])->name('leaves.export');

    // --- MASTER LEAVES (MANAGEMENT) ---
    // --- MASTER LEAVE (per table: LeaveType & LeaveRule) ---
    Route::prefix('master-leaves')->name('master_leaves.')->group(function () {
        // Leave Types
        Route::resource('/', LeaveTypeController::class)
            ->parameters(['' => 'leaveType'])
            ->except(['show'])
            ->names([
                'index'   => 'index',
                'create'  => 'create',
                'store'   => 'store',
                'edit'    => 'edit',
                'update'  => 'update',
                'destroy' => 'destroy',
            ]);
        Route::post('/sync', [LeaveTypeController::class, 'syncBalances'])->name('sync');

        // Leave Rules (nested under leave type)
        Route::prefix('{leaveType}/rules')->name('rules.')->group(function () {
            Route::get('/', [LeaveRuleController::class, 'index'])->name('index');
            Route::get('/create', [LeaveRuleController::class, 'create'])->name('create');
            Route::post('/', [LeaveRuleController::class, 'store'])->name('store');
        });
        Route::prefix('rules')->name('rules.')->group(function () {
            Route::get('/{leaveRule}/edit', [LeaveRuleController::class, 'edit'])->name('edit');
            Route::put('/{leaveRule}', [LeaveRuleController::class, 'update'])->name('update');
            Route::delete('/{leaveRule}', [LeaveRuleController::class, 'destroy'])->name('destroy');
        });
    });

    // --- TRAINING & CERTIFICATES ---
    // User View
    Route::get('/my-certificates', [TrainingController::class, 'myCertificates'])->name('my.certificates');
    Route::get('/training/certificates/create', [TrainingController::class, 'create'])->name('training.certificates.create');
    Route::post('/training/certificates/store', [TrainingController::class, 'store'])->name('training.certificates.store');
    Route::get('/training/approval', [TrainingController::class, 'approval'])->name('training.approval');
    Route::post('/training/{id}/approve', [TrainingController::class, 'approve'])->name('training.approve');
    Route::post('/training/{id}/reject', [TrainingController::class, 'reject'])->name('training.reject');

    // Admin View
    Route::get('/training', [AdminTrainingCertificateController::class, 'index'])->name('admin.training.certificates.index');
    Route::get('/training/create', [AdminTrainingCertificateController::class, 'create'])->name('admin.training.certificates.create');
    Route::get('/training/edit/{certificate}', [AdminTrainingCertificateController::class, 'edit'])->name('admin.training.certificates.edit');
    Route::delete('/training/destroy/{certificate}', [AdminTrainingCertificateController::class, 'destroy'])->name('admin.training.certificates.destroy');

    // Admin Resource (Prefix: admin/training)
    // Note: Anda punya route manual dan resource yg tumpang tindih, saya rapikan sedikit
    Route::post('admin/training/certificates', [AdminTrainingCertificateController::class, 'store'])->name('admin.training.certificates.store');
    Route::put('admin/training/certificates/{certificate}', [AdminTrainingCertificateController::class, 'update'])->name('admin.training.certificates.update');

    // blacklist user yang udh ga kepake atau di buang
    Route::get('/blacklist', [BlacklistController::class, 'index'])->name('blacklist.index');
    Route::post('/blacklist', [BlacklistController::class, 'store'])->name('blacklist.store');
    Route::delete('/blacklist/{id}', [BlacklistController::class, 'destroy'])->name('blacklist.destroy');

    // Manajemen TIM Bandara
    Route::get('/tim', [UserController::class, 'tim'])->name('users.tim');
    Route::get('/tim/edit/{id}', [UserController::class, 'TIMEdit'])->name('users.TIMEdit');
    Route::put('/tim/update/{user}', [UserController::class, 'TIMUpdate'])->name('users.TIMUpdate');
    // --- MODUL LEMBUR (OVERTIME) ---
    Route::controller(OvertimeController::class)->group(function () {
        // Staff
        Route::get('/overtime', 'index')->name('overtime.index');
        Route::get('/overtime/create', 'create')->name('overtime.create');
        Route::get('/overtime/calculate-duration', 'calculateDuration')->name('overtime.calculate_duration');
        Route::post('/overtime/store', 'store')->name('overtime.store');

        // Leader Approval
        Route::get('/overtime/approval', 'approvalList')->name('overtime.approval');
        Route::post('/overtime/{id}/approve', 'approve')->name('overtime.approve');
        Route::post('/overtime/{id}/reject', 'reject')->name('overtime.reject');

        // Admin Report
        Route::get('/overtime/report', 'report')->name('overtime.report');
        Route::get('/overtime/export', 'exportExcel')->name('overtime.export');
    });

    // --- ANNOUNCEMENTS (PENGUMUMAN) ---
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/announcements/{id}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    Route::post('/announcements/{id}/read', [AnnouncementController::class, 'markAsRead'])->name('announcements.read');
    Route::post('/announcements/mark-all-read', [AnnouncementController::class, 'markAllRead'])->name('announcements.mark_all_read');

    // --- ASSIGNMENTS ---
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::get('/assignments/template', [AssignmentController::class, 'downloadTemplate'])->name('assignments.template');
    Route::post('/assignments/store', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/assignments/fetch-flight-data', [AssignmentController::class, 'fetchFlightData'])->name('assignments.fetch_flight_data');
    Route::post('/assignments/import', [AssignmentController::class, 'import'])->name('assignments.import');
    Route::get('/assignments/export/pdf', [AssignmentController::class, 'exportPdf'])->name('assignments.export.pdf');
    Route::get('/assignments/{id}/export-pdf', [AssignmentController::class, 'exportSinglePdf'])->name('assignments.export_single_pdf');
    Route::post('/assignments/{id}/upload-photo', [AssignmentController::class, 'uploadPhoto'])->name('assignments.upload_photo');
    Route::get('/assignments/{id}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // Legacy Aliases for /work-orders and /work-results -> redirect to AssignmentController
    Route::get('/work-orders', [AssignmentController::class, 'index'])->name('work_orders.index');
    Route::get('/work-orders/create', [AssignmentController::class, 'create'])->name('work_orders.create');
    Route::get('/work-orders/template', [AssignmentController::class, 'downloadTemplate'])->name('work_orders.template');
    Route::post('/work-orders/store', [AssignmentController::class, 'store'])->name('work_orders.store');
    Route::post('/work-orders/fetch-flight-data', [AssignmentController::class, 'fetchFlightData'])->name('work_orders.fetch_flight_data');
    Route::post('/work-orders/import', [AssignmentController::class, 'import'])->name('work_orders.import');
    Route::get('/work-orders/export/pdf', [AssignmentController::class, 'exportPdf'])->name('work_orders.export.pdf');
    Route::get('/work-orders/{id}/export-pdf', [AssignmentController::class, 'exportSinglePdf'])->name('work_orders.export_single_pdf');
    Route::post('/work-orders/{id}/upload-photo', [AssignmentController::class, 'uploadPhoto'])->name('work_orders.upload_photo');
    Route::get('/work-orders/{id}', [AssignmentController::class, 'show'])->name('work_orders.show');
    Route::delete('/work-orders/{id}', [AssignmentController::class, 'destroy'])->name('work_orders.destroy');

    Route::get('/work-results', [AssignmentController::class, 'index'])->name('work_results.index');
    Route::get('/work-results/create', [AssignmentController::class, 'create'])->name('work_results.create');
    Route::get('/work-results/template', [AssignmentController::class, 'downloadTemplate'])->name('work_results.template');
    Route::post('/work-results/store', [AssignmentController::class, 'store'])->name('work_results.store');
    Route::post('/work-results/fetch-flight-data', [AssignmentController::class, 'fetchFlightData'])->name('work_results.fetch_flight_data');
    Route::post('/work-results/import', [AssignmentController::class, 'import'])->name('work_results.import');
    Route::get('/work-results/export/pdf', [AssignmentController::class, 'exportPdf'])->name('work_results.export.pdf');
    Route::get('/work-results/{id}/export-pdf', [AssignmentController::class, 'exportSinglePdf'])->name('work_results.export_single_pdf');
    Route::post('/work-results/{id}/upload-photo', [AssignmentController::class, 'uploadPhoto'])->name('work_results.upload_photo');
    Route::get('/work-results/{id}', [AssignmentController::class, 'show'])->name('work_results.show');

    // --- MANAJEMEN ROLE & HAK AKSES ---
    Route::get('/roles/{id}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
    Route::put('/roles/{id}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.update-permissions');
    Route::post('/roles/{id}/toggle-user', [RoleController::class, 'toggleUserRole'])->name('roles.toggle-user');
    Route::resource('roles', RoleController::class)->except(['show']);

    // (Master Data routes sudah terdaftar di atas, di bagian /master-data prefix)

    // --- BANTUAN & LAINNYA ---
    Route::view('/faq', 'faq')->name('faq');
    Route::view('/kebijakan-privasi', 'kebijakan')->name('kebijakan');
});

// --- STORAGE FILE FALLBACK (Fix 404 on production server if php artisan storage:link is missing) ---
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        $fullPath = public_path('storage/' . $path);
    }
    if (!file_exists($fullPath)) {
        abort(404);
    }
    $mime = mime_content_type($fullPath) ?: 'image/jpeg';
    return response()->file($fullPath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*')->name('storage.fallback');
