# Staff Personal Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give operational Staff a personal dashboard with assignment, attendance, and station-flight data while preserving the existing management dashboard.

**Architecture:** `HomeController` selects one of two server-side data paths from the authenticated user's comma-separated roles. The existing `home` Blade view keeps management markup and chart scripts behind the management flag, and renders the user's approved personal-dashboard concept for operational roles.

**Tech Stack:** PHP 8.2, Laravel 12, Blade, Eloquent/Query Builder, PHPUnit 11, MySQL/SQLite-compatible tests.

## Global Constraints

- Management roles are `Admin`, `Head Of Airport Service`, `SPV Bge`, `SPV Apron`, `Leader Bge`, `Leader Apron`, `Ass Leader Bge`, `Ass Leader Apron`, `Leader Aircraft Interior Exterior Cleaning`, `Leader Porter Apron`, `Finance`, `HSE`, `Controller`, and `Quality Control`.
- Role detection uses `User::hasRole()` and supports comma-separated multi-role values.
- Operational Staff never receive management aggregate values or chart datasets.
- Every personal query is scoped to the authenticated user.
- The station-flight table is scoped to the authenticated user's station.
- Preserve and refine the user's existing uncommitted concept in `resources/views/home.blade.php`.
- Preserve every unrelated dirty-worktree change.

---

### Task 1: Dashboard Mode and Personal Data

**Files:**
- Create: `tests/Feature/StaffDashboardTest.php`
- Modify: `app/Http/Controllers/HomeController.php`

**Interfaces:**
- Produces: view flag `showManagementDashboard: bool`.
- Produces: `assignedFlights`, `personalAttendanceHistory`, `personalSchedules`, `personalAttendancePercentage`, `personalAssignmentsToday`, and `personalCompletedFlightsToday`.
- Consumes: `User::hasRole(array $roles): bool`.

- [ ] **Step 1: Write failing feature tests**

Create the minimum SQLite schema for users, stations, attendances, shifts, schedules, flights, flight details, and leaves. Add fixtures for two Staff users at different stations and flights assigned through `flight_details.schedule_id`.

Assert an operational user response contains:

```php
$response
    ->assertOk()
    ->assertSeeText('Pengerjaan Hari Ini')
    ->assertSeeText('Persentase Kehadiran Anda')
    ->assertSeeText('Riwayat Presensi Anda')
    ->assertDontSeeText('Total Staff GLOBAL')
    ->assertDontSeeText('Distribusi Staff by Role');
```

Assert a user with role `Porter Bge, Leader Bge` receives the management dashboard. Assert personal assignment counts exclude another user, attendance percentage is based on scheduled month-to-date dates, attendance history excludes another user, and station flights exclude another station.

- [ ] **Step 2: Run tests and confirm RED**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/StaffDashboardTest.php
```

Expected: FAIL because the controller still sends management data and the personal values do not exist.

- [ ] **Step 3: Add server-side role selection**

Add a private controller constant containing the exact management-role list. In `index()`:

```php
$showManagementDashboard = $user->hasRole(self::MANAGEMENT_ROLES);
```

Build today's station-scoped `$flights` before branching. When the flag is false, return `home` immediately with the personal dataset. This prevents execution of global staff, absence, contract, PAS, and management chart queries.

- [ ] **Step 4: Build personal data**

Resolve assignments with:

```php
$assignedFlights = Flights::query()
    ->whereDate('created_at', Carbon::today())
    ->whereHas('details.schedule', fn ($query) => $query->where('user_id', $user->id))
    ->orderBy('arrival')
    ->get();
```

Calculate scheduled month-to-date dates and intersect them with the authenticated user's check-in dates. Return `0` for no schedules and otherwise round the percentage to two decimals.

Load the seven latest authenticated-user attendance records with station, then load their matching schedules with shift keyed by date.

- [ ] **Step 5: Run controller tests and confirm GREEN**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/StaffDashboardTest.php
```

Expected: controller/data assertions pass; UI assertions may remain RED until Task 2.

### Task 2: Personal Dashboard UI and Management Isolation

**Files:**
- Modify: `resources/views/home.blade.php`
- Modify: `tests/Feature/StaffDashboardTest.php`

**Interfaces:**
- Consumes: Task 1 view variables.
- Produces: separate Staff and management dashboard sections.

- [ ] **Step 1: Complete failing UI assertions**

Assert the Staff response contains the three personal KPI labels and correct values, the three table headings, the user's attendance/assignment data, and the station flight. Assert it excludes another user's attendance/assignment, other-station flights, management labels, and serialized management chart variable declarations.

- [ ] **Step 2: Refine the user's Staff concept**

Replace the duplicated placeholder flight tables inside the user's current Porter block:

- KPI values use `personalAssignmentsToday`, `personalAttendancePercentage`, and `personalCompletedFlightsToday`;
- `Riwayat Presensi Anda` columns are Tanggal, Office, Shift, In, Out, and Status;
- `Data Pengerjaan Hari Ini` uses `assignedFlights` and columns Airline, Flight No., Registrasi, Tipe, Kedatangan, Hitung Mundur, and Status;
- `Data Penerbangan Hari Ini` continues to use station-scoped `flights`.

Use `showManagementDashboard` instead of hard-coded Porter/Admin role comparisons so every operational role and multi-role management user follows the server decision.

- [ ] **Step 3: Isolate management markup and JavaScript**

Wrap the existing global KPI/charts in:

```blade
@if ($showManagementDashboard)
    {{-- existing management dashboard --}}
@else
    {{-- Staff personal dashboard --}}
@endif
```

Render Chart.js and management chart dataset declarations only when `showManagementDashboard` is true. Keep shared countdown and row-interaction JavaScript available to both dashboard modes.

- [ ] **Step 4: Run feature tests and confirm GREEN**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/StaffDashboardTest.php
```

Expected: all Staff dashboard tests pass.

### Task 3: Regression and Visual Verification

**Files:**
- Modify if required by verified defects only:
  - `app/Http/Controllers/HomeController.php`
  - `resources/views/home.blade.php`
  - `tests/Feature/StaffDashboardTest.php`

**Interfaces:**
- Verifies the complete Staff/management dashboard boundary.

- [ ] **Step 1: Run focused regression tests**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/StaffDashboardTest.php tests/Feature/AttendanceCorrectionTest.php
php vendor/bin/pint --test app/Http/Controllers/HomeController.php tests/Feature/StaffDashboardTest.php
php artisan view:cache
```

Expected: focused tests pass, Pint passes, and Blade templates compile.

- [ ] **Step 2: Run the full suite**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
```

Expected: no new failures. The previously documented `AdminMenuSmokeTest` missing-`users`-table failure may remain and must be reported separately.

- [ ] **Step 3: Perform browser QA**

Sign in as an operational Staff fixture and verify:

- desktop and narrow viewport layout;
- three personal KPIs;
- personal attendance and assignment scoping;
- station flight table;
- no management cards/charts;
- no browser console errors.

Sign in as a multi-role management fixture and verify the existing management dashboard remains visible.

- [ ] **Step 4: Commit only in-scope hunks**

Stage the new test and `HomeController` fully. Stage `resources/views/home.blade.php` as the integrated refinement of the user's in-scope concept, after confirming its full diff. Do not stage any unrelated dirty file.

```bash
git add app/Http/Controllers/HomeController.php resources/views/home.blade.php tests/Feature/StaffDashboardTest.php
git commit -m "feat: add personal dashboard for staff"
```
