# Attendance Correction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a one-time attendance correction request that a user's configured manager or an Admin can approve or reject.

**Architecture:** Store proposals in a dedicated `attendance_corrections` audit table and leave operational attendance unchanged while a request is pending. A focused controller owns submission and decision authorization; approval applies the proposed times and station to `attendances` inside one database transaction.

**Tech Stack:** PHP 8.2, Laravel 12, Blade, Eloquent, PHPUnit 11, MySQL/SQLite-compatible migrations.

## Global Constraints

- One correction per user and attendance date for the lifetime of the record.
- Corrections are allowed only for today and past dates.
- Check-in belongs to the correction date; check-out is later and may be on the following day.
- Office comes from an active `stations` record.
- Only the applicant's configured `manager` by exact `fullname`, or an Admin, can decide a request.
- Rejection and approval are final.
- Correction check-in and check-out fields accept `HH:mm` only; the attendance date comes from the selected history row.
- If check-out is earlier than or equal to check-in, it is interpreted as the following day.
- Attendance History shows the correction reason in `Note Koreksi`, or `-` when no request exists.
- Preserve all unrelated dirty-worktree changes.

---

### Task 1: Persistence and Domain Model

**Files:**
- Create: `database/migrations/2026_07_26_120000_add_station_id_to_attendances_table.php`
- Create: `database/migrations/2026_07_26_120100_create_attendance_corrections_table.php`
- Create: `app/Models/AttendanceCorrection.php`
- Modify: `app/Models/Attendance.php`
- Modify: `app/Models/Station.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/AttendanceCorrectionTest.php`

**Interfaces:**
- Produces: `AttendanceCorrection` relationships `user()`, `attendance()`, `station()`, `decider()`.
- Produces: `AttendanceCorrection::STATUS_PENDING`, `STATUS_APPROVED`, and `STATUS_REJECTED`.
- Produces: `Attendance::station()` and `Attendance::corrections()`.

- [ ] **Step 1: Write a failing persistence test**

```php
public function test_correction_persists_string_user_key_station_and_unique_date(): void
{
    [$user, $station] = $this->makeUserAndStation();
    AttendanceCorrection::create([
        'user_id' => $user->id,
        'station_id' => $station->id,
        'attendance_date' => '2026-07-20',
        'proposed_check_in_time' => '2026-07-20 08:00:00',
        'proposed_check_out_time' => '2026-07-20 17:00:00',
        'reason' => 'Mesin absensi tidak mencatat.',
        'status' => AttendanceCorrection::STATUS_PENDING,
    ]);

    $this->assertDatabaseHas('attendance_corrections', [
        'user_id' => $user->id,
        'station_id' => $station->id,
        'attendance_date' => '2026-07-20',
    ]);
}
```

- [ ] **Step 2: Run the test and confirm RED**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php --filter=persists`

Expected: FAIL because the table and model do not exist.

- [ ] **Step 3: Add migrations and models**

Use string foreign keys for `user_id` and `decided_by`, bigint foreign keys for attendance and station, and a database unique key:

```php
$table->string('user_id');
$table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
$table->foreignId('station_id')->constrained('stations');
$table->date('attendance_date');
$table->dateTime('proposed_check_in_time');
$table->dateTime('proposed_check_out_time');
$table->text('reason');
$table->string('status')->default('pending');
$table->string('decided_by')->nullable();
$table->timestamp('decided_at')->nullable();
$table->unique(['user_id', 'attendance_date']);
$table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
$table->foreign('decided_by')->references('id')->on('users')->nullOnDelete();
```

Cast correction dates/times and add mass-assignable fields plus relationships. Add nullable `station_id` to `attendances` and include it in `Attendance::$fillable`.

- [ ] **Step 4: Run the persistence test and confirm GREEN**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php --filter=persists`

Expected: PASS.

### Task 2: Submission and Applicant History

**Files:**
- Create: `app/Http/Controllers/AttendanceCorrectionController.php`
- Modify: `app/Http/Controllers/AttendanceController.php`
- Modify: `routes/web.php`
- Create: `resources/views/attendance/corrections/create.blade.php`
- Modify: `resources/views/attendance/history.blade.php`
- Test: `tests/Feature/AttendanceCorrectionTest.php`

**Interfaces:**
- Produces: named routes `attendance.corrections.create` and `attendance.corrections.store`.
- Consumes: active `Station` records and `AttendanceCorrection` unique date rule.

- [ ] **Step 1: Write failing submission tests**

```php
public function test_user_can_submit_one_valid_correction_for_own_date(): void
{
    [$user, $station] = $this->makeUserAndStation();
    $this->actingAs($user)->post(route('attendance.corrections.store', '2026-07-20'), [
        'check_in_time' => '2026-07-20T08:00',
        'check_out_time' => '2026-07-20T17:00',
        'station_id' => $station->id,
        'reason' => 'Mesin absensi tidak mencatat.',
    ])->assertRedirect(route('attendance.history'));

    $this->assertDatabaseHas('attendance_corrections', [
        'user_id' => $user->id,
        'attendance_date' => '2026-07-20',
        'status' => 'pending',
    ]);
}
```

Add focused tests for future dates, inactive stations, invalid time ordering, check-in on a different date, and duplicate requests after every status.

- [ ] **Step 2: Run submission tests and confirm RED**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php --filter=submit`

Expected: FAIL because routes/controller do not exist.

- [ ] **Step 3: Implement submission and history UI**

Validate route date ownership implicitly from `Auth::id()`, then validate:

```php
'check_in_time' => ['required', 'date'],
'check_out_time' => ['required', 'date', 'after:check_in_time'],
'station_id' => ['required', Rule::exists('stations', 'id')->where('is_active', true)],
'reason' => ['required', 'string', 'max:2000'],
```

Reject future dates, require the check-in date to equal the route date, and use `firstOrCreate` semantics guarded by the unique key. Load corrections keyed by `attendance_date` in `AttendanceController::history()`. Add an Action column with an Edit/Koreksi button or final status badge.

- [ ] **Step 4: Run submission tests and confirm GREEN**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php --filter=submit`

Expected: PASS.

### Task 3: Manager/Admin Approval

**Files:**
- Modify: `app/Http/Controllers/AttendanceCorrectionController.php`
- Modify: `routes/web.php`
- Create: `resources/views/attendance/corrections/approval.blade.php`
- Modify: `resources/views/layout/admin.blade.php`
- Test: `tests/Feature/AttendanceCorrectionTest.php`

**Interfaces:**
- Produces: routes `attendance.corrections.approval`, `.approve`, and `.reject`.
- Produces: controller authorization predicate that accepts Admin or exact applicant manager match.

- [ ] **Step 1: Write failing authorization and decision tests**

```php
public function test_configured_manager_can_approve_and_create_missing_attendance(): void
{
    [$manager, $applicant, $station, $correction] = $this->makePendingCorrection();

    $this->actingAs($manager)
        ->post(route('attendance.corrections.approve', $correction))
        ->assertRedirect();

    $this->assertDatabaseHas('attendances', [
        'user_id' => $applicant->id,
        'station_id' => $station->id,
        'check_in_time' => '2026-07-20 08:00:00',
        'check_out_time' => '2026-07-20 17:00:00',
    ]);
    $this->assertDatabaseHas('attendance_corrections', [
        'id' => $correction->id,
        'status' => 'approved',
        'decided_by' => $manager->id,
    ]);
}
```

Add tests for updating an existing attendance, rejection leaving attendance unchanged, unrelated-user 403, Admin access, scoped approval list, and a second decision attempt.

- [ ] **Step 2: Run approval tests and confirm RED**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php --filter=approve`

Expected: FAIL because approval actions do not exist.

- [ ] **Step 3: Implement approval transaction and menu**

Scope non-Admin queries with:

```php
$query->whereHas('user', fn ($builder) =>
    $builder->where('manager', Auth::user()->fullname)
);
```

Inside `DB::transaction()`, lock the correction, require pending state, re-check authorization, locate attendance by applicant and the date portion of `check_in_time`, then create or update it. Save `attendance_id`, `status`, `decided_by`, and `decided_at`. Reject only updates decision fields.

Show the approval menu only for Admin or a user referenced by at least one employee's `manager`.

- [ ] **Step 4: Run approval tests and confirm GREEN**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php --filter='approve|reject|unrelated|admin'`

Expected: PASS.

### Task 4: Station Capture, Regression, and Visual Verification

**Files:**
- Modify: `app/Http/Controllers/AttendanceController.php`
- Modify: `resources/views/attendance/report.blade.php`
- Test: `tests/Feature/AttendanceCorrectionTest.php`

**Interfaces:**
- Consumes: `Attendance::$fillable` station ID.
- Produces: new check-ins retain their station and legacy rows retain a user-station fallback.

- [ ] **Step 1: Write a failing station capture test**

Verify a physical check-in saves the resolved station ID and an approved correction displays the correction station in History.

- [ ] **Step 2: Run focused tests and confirm RED**

Run: `DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php --filter=station`

Expected: FAIL because current check-in does not save `station_id`.

- [ ] **Step 3: Save and display attendance station**

Add `'station_id' => $station->id` to both physical check-in creation paths. In History and reports, prefer `$attendance->station->code`, falling back to `$user->station` for legacy null station IDs.

- [ ] **Step 4: Run focused and full tests**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
vendor/bin/pint --test app/Models/Attendance.php app/Models/AttendanceCorrection.php app/Http/Controllers/AttendanceCorrectionController.php tests/Feature/AttendanceCorrectionTest.php
php artisan route:list --name=attendance.corrections
```

Expected: all correction tests pass, the full suite has no new failures, Pint passes, and five correction routes are listed.

- [ ] **Step 5: Perform browser QA**

Open Attendance History, verify the Edit/Koreksi form and status states, then sign in as a configured manager and verify the approval table and approve/reject confirmation flow. Confirm desktop and narrow viewport layouts remain usable.

### Task 5: Time-Only Correction Inputs and History Note

**Files:**
- Modify: `tests/Feature/AttendanceCorrectionTest.php`
- Modify: `app/Http/Controllers/AttendanceCorrectionController.php`
- Modify: `resources/views/attendance/corrections/create.blade.php`
- Modify: `resources/views/attendance/history.blade.php`

**Interfaces:**
- Consumes: route parameter `$attendanceDate` in `Y-m-d` format.
- Consumes: request fields `check_in_time` and `check_out_time` in `H:i` format.
- Produces: full `proposed_check_in_time` and `proposed_check_out_time` datetimes.

- [ ] **Step 1: Write failing time-only and note tests**

Submit:

```php
[
    'check_in_time' => '22:00',
    'check_out_time' => '06:00',
    'station_id' => $station->id,
    'reason' => 'Mesin absensi tidak mencatat.',
]
```

Assert that the proposal stores `2026-07-20 22:00:00` and `2026-07-21 06:00:00`. Assert the form renders `type="time"` fields without `datetime-local`. Assert History renders `Note Koreksi`, the request reason, and `-` for a date without a request.

- [ ] **Step 2: Run focused tests and confirm RED**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php --filter='time_only|overnight|correction_note'
```

Expected: FAIL because the controller currently parses full datetimes, the form uses `datetime-local`, and History lacks the note column.

- [ ] **Step 3: Implement time-only composition**

Validate both request fields with `date_format:H:i`. Compose full datetimes from the route date:

```php
$checkIn = Carbon::createFromFormat('Y-m-d H:i', "{$attendanceDate} {$validated['check_in_time']}");
$checkOut = Carbon::createFromFormat('Y-m-d H:i', "{$attendanceDate} {$validated['check_out_time']}");

if ($checkOut->lessThanOrEqualTo($checkIn)) {
    $checkOut->addDay();
}
```

Store the composed values in the correction record.

- [ ] **Step 4: Update the form and History**

Render `Jam In` and `Jam Out` as `type="time"` inputs. Prefill existing attendance values with `H:i`. Add `Note Koreksi` between `Out` and `Action`, displaying `{{ $correction?->reason ?? '-' }}`.

- [ ] **Step 5: Run focused and regression verification**

Run:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/AttendanceCorrectionTest.php
vendor/bin/pint --test app/Http/Controllers/AttendanceCorrectionController.php tests/Feature/AttendanceCorrectionTest.php
php artisan view:cache
```

Expected: all correction tests pass, Pint passes, and Blade views compile.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/AttendanceCorrectionTest.php app/Http/Controllers/AttendanceCorrectionController.php resources/views/attendance/corrections/create.blade.php resources/views/attendance/history.blade.php
git commit -m "feat: use time-only attendance corrections"
```
