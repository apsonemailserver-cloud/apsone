# Staff Dashboard History Periods Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show Staff attendance and station flights for seven days, and Staff assignment data plus all three KPI values for the last 30 calendar days.

**Architecture:** Keep management queries unchanged. In the Staff branch, derive rolling period boundaries from `Carbon::today()`, scope every personal query by authenticated user, and scope station flights by both the Staff station and seven-day period. Blade headings state each period explicitly.

**Tech Stack:** PHP 8.2, Laravel 12, Blade, Eloquent, Carbon, PHPUnit 11, SQLite-compatible feature tests.

## Global Constraints

- Seven days means today plus the previous six calendar days.
- One month means a rolling 30-calendar-day period: today plus the previous 29 days.
- The three Staff KPI cards and Staff assignment table use the same 30-day period.
- Staff attendance history and station-flight table use the same seven-day period.
- Management dashboard data, labels, and query periods remain unchanged.
- All personal data remains scoped to the authenticated user.
- Station-flight data remains scoped to the authenticated user's station.
- Preserve every unrelated dirty-worktree change.

---

### Task 1: Lock Period and Scope Behavior with Feature Tests

**Files:**
- Modify: `tests/Feature/StaffDashboardTest.php`

**Interfaces:**
- Consumes: `HomeController::index(Request): View`.
- Verifies view data: `assignedFlights`, `flights`, `personalAttendanceHistory`, `personalAttendancePercentage`, `personalAssignmentsLastMonth`, and `personalCompletedFlightsLastMonth`.

- [ ] **Step 1: Extend date-aware fixtures**

Change `flightRow()` to accept a creation date:

```php
private function flightRow(
    int $id,
    string $number,
    string $station,
    bool $status,
    string $createdAt = '2026-07-26 09:00:00'
): array {
    return [
        // existing fields
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ];
}
```

Add authenticated-user assignments on 5, 20, and 31 days ago; add station flights on 5 and 7 days ago. Add attendance on 5 and 7 days ago. Ensure an assignment from 31 days ago, a station flight from 7 days ago, and attendance from 7 days ago are excluded.

- [ ] **Step 2: Assert exact rolling periods and labels**

Assert the Staff response contains:

```php
->assertSeeText('Pengerjaan 1 Bulan Terakhir')
->assertSeeText('Persentase Kehadiran Anda (1 Bulan Terakhir)')
->assertSeeText('Penerbangan Selesai (1 Bulan Terakhir)')
->assertSeeText('Riwayat Presensi Anda (7 Hari Terakhir)')
->assertSeeText('Data Pengerjaan (1 Bulan Terakhir)')
->assertSeeText('Data Penerbangan (7 Hari Terakhir)');
```

Assert:

```php
$this->assertSame(4, $response->viewData('personalAssignmentsLastMonth'));
$this->assertSame(2, $response->viewData('personalCompletedFlightsLastMonth'));
$this->assertSame(75.0, $response->viewData('personalAttendancePercentage'));
```

Assert `assignedFlights` includes only authenticated-user flights from today through 29 days ago, `flights` includes only same-station flights from today through 6 days ago, and `personalAttendanceHistory` includes only authenticated-user records from today through 6 days ago.

- [ ] **Step 3: Run tests and confirm RED**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/StaffDashboardTest.php
```

Expected: FAIL because current queries use today/month-to-date, attendance uses a seven-record limit, and the new labels/view variables do not exist.

### Task 2: Implement Rolling Server-Side Periods

**Files:**
- Modify: `app/Http/Controllers/HomeController.php`

**Interfaces:**
- Produces Staff view variables: `assignedFlights`, `personalAttendanceHistory`, `personalSchedules`, `personalAttendancePercentage`, `personalAssignmentsLastMonth`, and `personalCompletedFlightsLastMonth`.
- Keeps all current management view variables unchanged.

- [ ] **Step 1: Scope the Staff station-flight query to seven days**

Before building `$flightsQuery`, derive:

```php
$today = Carbon::today();
$staffWeekStart = $today->copy()->subDays(6)->startOfDay();
```

Use `whereBetween('created_at', [$staffWeekStart, $today->copy()->endOfDay()])` only when `! $showManagementDashboard`; retain `whereDate('created_at', $today)` for management.

- [ ] **Step 2: Apply the rolling 30-day personal period**

Inside `staffDashboardData()` derive:

```php
$today = Carbon::today();
$monthStart = $today->copy()->subDays(29);
$monthStartOfDay = $monthStart->copy()->startOfDay();
$todayEndOfDay = $today->copy()->endOfDay();
```

Use this range for assigned-flight `created_at`, scheduled dates, and attended dates. Rename the KPI return values:

```php
'personalAssignmentsLastMonth' => $assignedFlights->count(),
'personalCompletedFlightsLastMonth' => $assignedFlights
    ->where('status', true)
    ->count(),
```

- [ ] **Step 3: Apply the rolling seven-day attendance period**

Replace the latest-seven-record limit with:

```php
$weekStart = $today->copy()->subDays(6)->startOfDay();

$personalAttendanceHistory = Attendance::with('station')
    ->where('user_id', $user->id)
    ->whereBetween('check_in_time', [$weekStart, $todayEndOfDay])
    ->orderByDesc('check_in_time')
    ->get();
```

Keep schedule lookup keyed only to dates found in this filtered collection.

- [ ] **Step 4: Run tests and confirm controller behavior**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/StaffDashboardTest.php
```

Expected: data assertions pass; label assertions remain RED until Task 3.

### Task 3: Update Staff Dashboard Copy and Bindings

**Files:**
- Modify: `resources/views/home.blade.php`

**Interfaces:**
- Consumes: `personalAssignmentsLastMonth`, `personalCompletedFlightsLastMonth`, `personalAttendancePercentage`, `assignedFlights`, `personalAttendanceHistory`, and `flights`.

- [ ] **Step 1: Update KPI bindings and labels**

Render:

```blade
<div class="stat-title">Pengerjaan 1 Bulan Terakhir</div>
<div class="stat-value">{{ $personalAssignmentsLastMonth }}</div>

<div class="stat-title">Persentase Kehadiran Anda (1 Bulan Terakhir)</div>

<div class="stat-title">Penerbangan Selesai (1 Bulan Terakhir)</div>
<div class="stat-value">{{ $personalCompletedFlightsLastMonth }}</div>
```

- [ ] **Step 2: Update table periods and empty states**

Use these headings:

```blade
Riwayat Presensi Anda <span class="text-muted fw-normal">(7 Hari Terakhir)</span>
Data Pengerjaan <span class="text-muted fw-normal">(1 Bulan Terakhir)</span>
Data Penerbangan <span class="text-muted fw-normal">(7 Hari Terakhir)</span>
```

Update empty states to say no records exist in the relevant seven-day or one-month period. Keep the management flight heading as `Data Penerbangan Hari Ini`.

- [ ] **Step 3: Run tests and confirm GREEN**

Run:

```bash
php artisan view:clear
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/StaffDashboardTest.php
```

Expected: all Staff dashboard tests pass.

### Task 4: Regression Verification and Commit

**Files:**
- Verify/modify only if a test exposes a defect:
  - `app/Http/Controllers/HomeController.php`
  - `resources/views/home.blade.php`
  - `tests/Feature/StaffDashboardTest.php`

**Interfaces:**
- Verifies date boundaries, role isolation, Blade compilation, and code style.

- [ ] **Step 1: Run focused regression**

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/StaffDashboardTest.php tests/Feature/AttendanceCorrectionTest.php
php vendor/bin/pint --test app/Http/Controllers/HomeController.php tests/Feature/StaffDashboardTest.php
php artisan view:cache
```

Expected: focused tests, Pint, and Blade compilation pass.

- [ ] **Step 2: Run full test suite**

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
```

Expected: no new failures. Report the previously known `AdminMenuSmokeTest` missing-`users`-table failure separately if it remains.

- [ ] **Step 3: Review only in-scope diff**

```bash
git diff --check
git diff -- app/Http/Controllers/HomeController.php resources/views/home.blade.php tests/Feature/StaffDashboardTest.php
```

Confirm no unrelated user-owned edits are staged.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/HomeController.php resources/views/home.blade.php tests/Feature/StaffDashboardTest.php
git commit -m "feat: add staff dashboard history periods"
```
