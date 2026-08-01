# QC Security Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menutup celah authorization, menyelesaikan fitur nomor WO opsional, mengamankan error/integrasi, dan memperbarui dependency rentan tanpa push atau perubahan UI yang tidak terkait.

**Architecture:** Pemeriksaan authorization tetap mengikuti sistem role/permission yang sudah ada. Aturan visibilitas Assignment dipusatkan pada model query scope agar listing dan endpoint per-record konsisten; controller melakukan authorization sebelum validasi atau mutasi. Presentasi nomor WO kosong dipusatkan pada accessor model, sedangkan dependency diperbarui hanya dalam constraint major version yang sekarang.

**Tech Stack:** PHP 8.2+, Laravel 12, PHPUnit 11, SQLite testing, MySQL production, Blade, Composer, Vite 6, npm.

## Global Constraints

- Jangan push atau deploy.
- Jangan meredesain UI atau menghapus legacy Work Result routes.
- Jangan mengganti sistem role string dengan package authorization baru.
- Jangan memformat seluruh codebase; jalankan Pint hanya pada file yang disentuh.
- Pertahankan perubahan working tree yang sudah ada dan jangan memasukkan perubahan yang tidak terkait ke commit lokal.
- Setiap perubahan perilaku wajib mengikuti red-green-refactor.

---

## File Map

- `tests/Feature/AuthorizationHardeningTest.php`: regression test endpoint administratif dan Shift.
- `tests/Feature/WorkOrderTest.php`: regression test visibilitas, mutation authorization, dan nomor WO opsional.
- `tests/Unit/AssignmentTest.php`: kontrak label dan referensi Assignment.
- `tests/Feature/OperationalHardeningTest.php`: guard untuk error import dan TLS verification.
- `app/Http/Controllers/RoleController.php`: Admin-only role management.
- `app/Http/Controllers/UserController.php`: role gate dan validated-only update.
- `app/Http/Controllers/AdminTrainingCertificateController.php`: Admin-only certificate management.
- `app/Http/Controllers/ShiftController.php`: permission gate untuk setiap mutation action.
- `app/Models/Assignment.php`: visibility scope dan accessor presentasi/reference.
- `app/Http/Controllers/WorkOrderController.php`: scoped record lookup dan operation gates.
- `resources/views/work_order/*.blade.php`, `resources/views/home.blade.php`: gunakan label/reference terpusat.
- `database/migrations/2026_08_01_190206_make_wo_number_nullable_in_assignments.php`: kompatibilitas nama tabel lama/baru.
- `app/Http/Controllers/StaffController.php`: safe import failure handling.
- `composer.lock`, `package-lock.json`: dependency security/build updates.

---

### Task 1: Protect administrative and Shift mutations

**Files:**
- Create: `tests/Feature/AuthorizationHardeningTest.php`
- Modify: `app/Http/Controllers/RoleController.php`
- Modify: `app/Http/Controllers/UserController.php`
- Modify: `app/Http/Controllers/AdminTrainingCertificateController.php`
- Modify: `app/Http/Controllers/ShiftController.php`

**Interfaces:**
- Consumes: `User::isAdmin()`, `User::canAccess(string $module, string $action)`.
- Produces: private controller guards that abort with HTTP 403 before reads or writes.

- [ ] **Step 1: Add failing authorization tests**

Create a `RefreshDatabase` test class, seed `RolePermissionSeeder`, and add these concrete cases:

```php
public function test_staff_cannot_manage_roles(): void
{
    $staff = User::factory()->create(['role' => 'Staff']);
    $role = Role::where('name', 'Staff')->firstOrFail();

    $this->actingAs($staff)->get(route('roles.index'))->assertForbidden();
    $this->actingAs($staff)->post(route('roles.store'), [
        'name' => 'Unauthorized Role',
        'label' => 'Unauthorized Role',
    ])->assertForbidden();
    $this->actingAs($staff)->put(route('roles.update', $role), [
        'label' => 'Changed',
    ])->assertForbidden();
}

public function test_staff_cannot_assign_admin_role(): void
{
    $staff = User::factory()->create(['role' => 'Staff']);
    $target = User::factory()->create(['role' => 'Staff']);
    $adminRole = Role::where('name', 'Admin')->firstOrFail();

    $this->actingAs($staff)->postJson(route('roles.toggle-user', $adminRole), [
        'user_id' => $target->id,
    ])->assertForbidden();
    $this->assertSame('Staff', $target->fresh()->role);
}

public function test_staff_cannot_update_another_user(): void
{
    $staff = User::factory()->create(['role' => 'Staff']);
    $target = User::factory()->create(['role' => 'Staff', 'salary' => 1000]);

    $this->actingAs($staff)->put(route('users.update', $target), [
        'fullname' => $target->fullname,
        'email' => $target->email,
        'role' => 'Admin',
        'station' => $target->station,
        'gender' => $target->gender,
        'job_title' => 'Staff',
        'cluster' => 'OPS',
        'unit' => 'OPS',
        'sub_unit' => 'OPS',
        'manager' => 'Manager',
        'is_qantas' => false,
        'join_date' => now()->toDateString(),
        'salary' => 999999,
    ])->assertForbidden();
    $this->assertSame('Staff', $target->fresh()->role);
}

public function test_staff_cannot_manage_admin_certificates(): void
{
    $staff = User::factory()->create(['role' => 'Staff']);
    $this->actingAs($staff)
        ->get(route('admin.training.certificates.index'))
        ->assertForbidden();
}

public function test_shift_mutations_require_shift_create_permission(): void
{
    $staff = User::factory()->create(['role' => 'Staff']);
    $shift = Shift::create([
        'id' => 'QC1', 'name' => 'QC', 'description' => 'QC',
        'start_time' => '08:00', 'end_time' => '16:00', 'use_manpower' => 1,
    ]);
    $payload = [
        'id' => 'QC2', 'name' => 'Blocked', 'description' => 'Blocked',
        'start_time' => '09:00', 'end_time' => '17:00', 'use_manpower' => 1,
    ];

    $this->actingAs($staff)->post(route('shift.store'), $payload)->assertForbidden();
    $this->actingAs($staff)->put(route('shift.update', $shift), $payload)->assertForbidden();
    $this->assertSame('QC', $shift->fresh()->name);
}
```

- [ ] **Step 2: Run the new class and confirm RED**

Run: `php artisan test tests/Feature/AuthorizationHardeningTest.php`

Expected: failures because RoleController, UserController update, certificate controller, and Shift mutation endpoints currently accept staff requests.

- [ ] **Step 3: Add minimal controller guards and validated-only update**

At the start of every public `RoleController` action, call:

```php
private function ensureAdmin(): void
{
    abort_unless(auth()->user()?->isAdmin(), 403);
}
```

Add the same Admin guard to all `AdminTrainingCertificateController` actions. In `UserController::update`, reuse the exact management-role condition from `edit()` before validation, assign the validation result to `$validated`, normalize only `$validated['role']`, and call `$user->update($validated)` instead of `$request->all()`.

Add this guard at the first line of `ShiftController::store()` and `update()`; retain the existing guard on `destroy()`:

```php
abort_unless(Auth::user()->canAccess('shift', 'create'), 403, 'Anda tidak memiliki akses ke halaman ini.');
```

- [ ] **Step 4: Run focused tests and confirm GREEN**

Run: `php artisan test tests/Feature/AuthorizationHardeningTest.php tests/Feature/RolePermissionTest.php tests/Feature/AdminMenuSmokeTest.php`

Expected: all tests pass; unauthorized mutations return 403 and Admin smoke pages remain below HTTP 500.

- [ ] **Step 5: Format only Task 1 files**

Run: `php vendor/bin/pint tests/Feature/AuthorizationHardeningTest.php app/Http/Controllers/RoleController.php app/Http/Controllers/UserController.php app/Http/Controllers/AdminTrainingCertificateController.php app/Http/Controllers/ShiftController.php`

- [ ] **Step 6: Create a local commit without push if no unrelated hunks overlap**

Run: `git diff --check` and inspect `git diff` for the five Task 1 files. Stage only owned hunks; commit message: `fix: enforce administrative authorization`. If an owned file contains pre-existing overlapping edits, leave the task uncommitted and preserve those edits.

---

### Task 2: Centralize Assignment visibility and mutation authorization

**Files:**
- Modify: `tests/Feature/WorkOrderTest.php`
- Modify: `app/Models/Assignment.php`
- Modify: `app/Http/Controllers/WorkOrderController.php`

**Interfaces:**
- Produces: `Assignment::scopeVisibleTo(Builder $query, User $user): Builder`.
- Produces: `WorkOrderController::findVisibleOrFail(int|string $id): WorkOrder`.
- Consumes: Admin sees all; leader sees `submitted_by = user.id`; staff sees records related through `assignment_user`.

- [ ] **Step 1: Add failing Work Order access tests**

Add a leader, unrelated leader, and unrelated staff in `WorkOrderTest`, then add:

```php
public function test_staff_cannot_view_an_unrelated_assignment(): void
{
    $workOrder = $this->assignmentSubmittedBy($this->user);

    $this->actingAs($this->staff1)
        ->get(route('work_orders.show', $workOrder->id))
        ->assertNotFound();
}

public function test_assigned_staff_can_view_and_upload_assignment_photo(): void
{
    Storage::fake('public');
    $workOrder = $this->assignmentSubmittedBy($this->user);
    $workOrder->users()->attach($this->staff1);

    $this->actingAs($this->staff1)
        ->get(route('work_orders.show', $workOrder->id))
        ->assertOk();
    $this->actingAs($this->staff1)
        ->post(route('work_orders.upload_photo', $workOrder->id), [
            'photo' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertRedirect();
}

public function test_leader_cannot_export_or_upload_another_leaders_assignment(): void
{
    Storage::fake('public');
    $owner = User::factory()->create(['role' => 'Leader Bge']);
    $other = User::factory()->create(['role' => 'Leader Bge']);
    $workOrder = $this->assignmentSubmittedBy($owner, 'work_orders/proof.jpg');
    Storage::disk('public')->put('work_orders/proof.jpg', 'proof');

    $this->actingAs($other)
        ->get(route('work_orders.export_single_pdf', $workOrder->id))
        ->assertNotFound();
    $this->actingAs($other)
        ->post(route('work_orders.upload_photo', $workOrder->id), [
            'photo' => UploadedFile::fake()->image('replacement.jpg'),
        ])->assertNotFound();
}

public function test_non_admin_cannot_delete_assignment(): void
{
    $leader = User::factory()->create(['role' => 'Leader Bge']);
    $workOrder = $this->assignmentSubmittedBy($leader);

    $this->actingAs($leader)
        ->delete(route('work_orders.destroy', $workOrder->id))
        ->assertForbidden();
    $this->assertDatabaseHas('assignments', ['id' => $workOrder->id]);
}

public function test_staff_cannot_import_assignments(): void
{
    $this->actingAs($this->staff1)
        ->post(route('work_orders.import'), [])
        ->assertForbidden();
}
```

Add this complete factory helper to the same test class so every access test creates the same valid record:

```php
private function assignmentSubmittedBy(User $submitter, ?string $photoPath = null): WorkOrder
{
    return WorkOrder::create([
        'date' => '2026-07-31',
        'station' => 'CGK',
        'aircraft_reg' => 'PK-LGH',
        'parking_stand' => 'A12',
        'wo_number' => 'WO-QC-001',
        'start_time' => '08:00',
        'end_time' => '09:30',
        'type' => 'DCI',
        'photo_path' => $photoPath,
        'submitted_by' => $submitter->id,
    ]);
}
```

- [ ] **Step 2: Run focused tests and confirm RED**

Run: `php artisan test tests/Feature/WorkOrderTest.php --filter='unrelated|assigned_staff|non_admin|staff_cannot_import'`

Expected: current endpoints return success/validation redirects instead of 403/404.

- [ ] **Step 3: Implement the visibility scope and scoped lookup**

Add to `Assignment`:

```php
public function scopeVisibleTo(Builder $query, User $user): Builder
{
    if ($user->isAdmin()) {
        return $query;
    }

    if ($user->hasRole(self::LEADER_ROLES)) {
        return $query->where('submitted_by', $user->id);
    }

    return $query->whereHas('users', fn (Builder $users) => $users->where('users.id', $user->id));
}
```

Use `WorkOrder::query()->visibleTo(auth()->user())` in `index`, `show`, `exportPdf`, `exportSinglePdf`, and `uploadPhoto`. Add `abort_unless(auth()->user()->isAdmin(), 403)` before delete, and `abort_unless(auth()->user()->hasRole(WorkOrder::LEADER_ROLES), 403)` before import. Authorization must occur before request validation.

- [ ] **Step 4: Run all Work Order tests and confirm GREEN**

Run: `php artisan test tests/Feature/WorkOrderTest.php`

Expected: all existing and new Work Order tests pass.

- [ ] **Step 5: Format and inspect**

Run: `php vendor/bin/pint tests/Feature/WorkOrderTest.php app/Models/Assignment.php app/Http/Controllers/WorkOrderController.php`

- [ ] **Step 6: Create a local commit without push if hunks are isolated**

Commit message: `fix: scope assignment access by user`. Do not stage unrelated changes.

---

### Task 3: Complete optional Work Order behavior and migration compatibility

**Files:**
- Create: `tests/Unit/AssignmentTest.php`
- Modify: `tests/Feature/WorkOrderTest.php`
- Modify: `app/Models/Assignment.php`
- Modify: `app/Http/Controllers/WorkOrderController.php`
- Modify: `resources/views/work_order/index.blade.php`
- Modify: `resources/views/work_order/show.blade.php`
- Modify: `resources/views/work_order/pdf.blade.php`
- Modify: `resources/views/work_order/single_pdf.blade.php`
- Modify: `resources/views/home.blade.php`
- Modify: `database/migrations/2026_08_01_190206_make_wo_number_nullable_in_assignments.php`

**Interfaces:**
- Produces: `$assignment->wo_number_label: string`.
- Produces: `$assignment->document_reference: string`.

- [ ] **Step 1: Add failing model and feature tests**

```php
public function test_wo_number_label_uses_dash_when_missing(): void
{
    $assignment = new Assignment(['wo_number' => null]);
    $assignment->id = 42;

    $this->assertSame('-', $assignment->wo_number_label);
    $this->assertSame('Assignment-42', $assignment->document_reference);
}

public function test_wo_number_label_and_reference_use_trimmed_number(): void
{
    $assignment = new Assignment(['wo_number' => 'WO-EXT-007']);
    $assignment->id = 42;

    $this->assertSame('WO-EXT-007', $assignment->wo_number_label);
    $this->assertSame('WO-EXT-007', $assignment->document_reference);
}
```

Add feature tests posting the standard valid payload with `wo_number` omitted and with `'   '`, asserting `assignments.wo_number` is `null`. Post `'  wo-ext-007  '` and assert storage is `WO-EXT-007`. Render index/show and assert `WO: -` or the detail badge contains `-`.

- [ ] **Step 2: Run new tests and confirm RED**

Run: `php artisan test tests/Unit/AssignmentTest.php tests/Feature/WorkOrderTest.php --filter='wo_number|without_a_wo'`

Expected: accessor/reference tests fail and raw views do not show the required fallback.

- [ ] **Step 3: Add accessors and use them at presentation boundaries**

```php
public function getWoNumberLabelAttribute(): string
{
    return filled($this->wo_number) ? $this->wo_number : '-';
}

public function getDocumentReferenceAttribute(): string
{
    return filled($this->wo_number) ? $this->wo_number : 'Assignment-'.$this->getKey();
}
```

Replace visible raw `wo_number` expressions with `wo_number_label`. Use `document_reference` in PDF titles, document references, alerts, and filenames. Keep raw `wo_number` only in form/database logic and search predicates.

- [ ] **Step 4: Make the nullable migration table-name aware**

Use one resolver in `up()` and `down()`:

```php
$tableName = Schema::hasTable('assignments') ? 'assignments' : 'work_orders';
if (! Schema::hasTable($tableName)) {
    return;
}
```

Apply `change()` to `$tableName`. In `down()`, run `DB::table($tableName)->whereNull('wo_number')->update(['wo_number' => '']);` before setting non-nullable. Import `Illuminate\Support\Facades\DB` explicitly.

- [ ] **Step 5: Verify migration and behavior GREEN**

Run a fresh temporary SQLite database:

```bash
qc_db_path=$(mktemp /tmp/apsone-qc.XXXXXX.sqlite)
DB_CONNECTION=sqlite DB_DATABASE="$qc_db_path" php artisan migrate:fresh --force --no-interaction
```

Then run: `php artisan test tests/Unit/AssignmentTest.php tests/Feature/WorkOrderTest.php`

Expected: migrations and all focused tests pass.

- [ ] **Step 6: Format PHP files and create a local commit if isolated**

Run Pint on `Assignment.php`, `WorkOrderController.php`, the migration, and the two test files. Commit message: `fix: complete optional assignment number handling`. Do not push.

---

### Task 4: Remove debug leakage and TLS bypass

**Files:**
- Create: `tests/Feature/OperationalHardeningTest.php`
- Modify: `app/Http/Controllers/StaffController.php`
- Modify: `app/Http/Controllers/WorkOrderController.php`

**Interfaces:**
- Import failure returns back with an error alert and writes exception context to the application log.
- Flightradar24 HTTPS requests use peer and hostname verification.

- [ ] **Step 1: Add safe failing source guards**

The existing `dd()` terminates PHPUnit itself, so use deterministic source guards for these two operational invariants:

```php
public function test_staff_import_does_not_contain_debug_dump(): void
{
    $source = file_get_contents(app_path('Http/Controllers/StaffController.php'));
    $this->assertStringNotContainsString('dd(', $source);
    $this->assertStringContainsString("Log::error('Staff import failed'", $source);
}

public function test_flight_fetch_does_not_disable_tls_verification(): void
{
    $source = file_get_contents(app_path('Http/Controllers/WorkOrderController.php'));
    $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYPEER => false', $source);
    $this->assertStringNotContainsString("'verify_peer' => false", $source);
    $this->assertStringNotContainsString("'verify_peer_name' => false", $source);
}
```

- [ ] **Step 2: Run tests and confirm RED**

Run: `php artisan test tests/Feature/OperationalHardeningTest.php`

Expected: failures identify `dd()` and disabled TLS verification.

- [ ] **Step 3: Implement safe error handling and verified TLS**

Replace the staff import catch block with:

```php
Log::error('Staff import failed', [
    'exception' => $e,
    'user_id' => Auth::id(),
]);
Alert::error('Gagal', 'Import staff gagal. Periksa format file dan coba kembali.');
```

Set cURL peer verification to `true` and hostname verification to `2`. Set stream context `verify_peer` and `verify_peer_name` to `true`. Preserve timeouts and existing fallback behavior.

- [ ] **Step 4: Run tests and confirm GREEN**

Run: `php artisan test tests/Feature/OperationalHardeningTest.php tests/Feature/WorkOrderTest.php`

Expected: hardening guards and Work Order integration fallback tests pass.

- [ ] **Step 5: Format only affected files and create a local commit if isolated**

Commit message: `fix: harden imports and flight transport`. Do not push.

---

### Task 5: Update vulnerable PHP dependencies

**Files:**
- Modify: `composer.lock`

**Interfaces:**
- Retains Laravel major version 12 and all `composer.json` major constraints.
- Produces a lockfile without fixable critical/high advisories.

- [ ] **Step 1: Capture the failing security baseline**

Run: `composer audit --locked --format=summary`

Expected before update: 45 advisories, including 2 critical and 9 high.

- [ ] **Step 2: Update within declared constraints**

Run:

```bash
composer update laravel/framework maatwebsite/excel phpoffice/phpspreadsheet barryvdh/laravel-dompdf dompdf/dompdf guzzlehttp/guzzle guzzlehttp/psr7 symfony/http-foundation symfony/mailer symfony/mime symfony/routing --with-all-dependencies --no-interaction
```

If Composer reports a constraint conflict, use `composer why-not vendor/package safe-version` and update only the minimum direct constraint needed while retaining the same major framework version.

- [ ] **Step 3: Verify dependency security and regression suite**

Run: `composer validate --no-check-publish`, `composer audit --locked`, and `php artisan test`.

Expected: Composer configuration valid; no fixable critical/high advisory remains; all tests pass. Report any advisory that cannot be resolved within current compatible constraints instead of suppressing it.

- [ ] **Step 4: Inspect lockfile and create a local commit**

Confirm `composer.json` changes, if any, stay within existing majors. Commit message: `chore: update vulnerable PHP dependencies`. Do not push.

---

### Task 6: Repair reproducible frontend build

**Files:**
- Modify only if npm changes it: `package-lock.json`

**Interfaces:**
- Produces a platform-correct Rollup installation and successful Vite production build.

- [ ] **Step 1: Confirm the failing build baseline**

Run: `npm run build`

Expected before reinstall: missing `@rollup/rollup-darwin-arm64` on Apple Silicon.

- [ ] **Step 2: Reinstall from the lockfile reproducibly**

Run:

```bash
qc_node_backup=$(mktemp -d /tmp/apsone-node-modules.XXXXXX)
mv node_modules "$qc_node_backup/node_modules"
npm ci
```

Retain the backup until build verification succeeds. Do not delete project source or the lockfile.

- [ ] **Step 3: Verify frontend security and build GREEN**

Run: `npm audit --omit=dev`, then `npm run build`.

Expected: zero production advisories and Vite exits 0 with assets written to `public/build`.

- [ ] **Step 4: Inspect generated changes**

Do not commit `node_modules` or generated `public/build` unless they were already intentionally tracked. Commit `package-lock.json` only if npm made a meaningful reproducibility change. Commit message: `chore: repair frontend dependency lock`.

---

### Task 7: Full verification and handoff

**Files:**
- Verify all changed files; do not add new behavior.

- [ ] **Step 1: Review acceptance criteria against the diff**

Run `git diff --check`, inspect `git diff --stat`, and confirm no unrelated working-tree changes were overwritten or staged.

- [ ] **Step 2: Run complete backend verification**

Run:

```bash
php artisan test
find app bootstrap config database routes tests -type f -name '*.php' -print0 | xargs -0 -n1 php -l
php artisan route:list --except-vendor
```

Expected: all tests pass, no PHP syntax errors, and route discovery exits 0.

- [ ] **Step 3: Run scoped style verification**

Run `php vendor/bin/pint --test` with the explicit list of PHP files changed by this plan.

Expected: zero style issues in owned files. Existing unrelated repository-wide issues are out of scope.

- [ ] **Step 4: Run dependency and build verification**

Run:

```bash
composer validate --no-check-publish
composer audit --locked
npm audit --omit=dev
npm run build
```

Expected: valid Composer metadata, no unresolved fixable critical/high PHP advisory, zero npm production advisories, and successful Vite build.

- [ ] **Step 5: Report exact results without pushing**

Report test/assertion counts, audit counts, build status, migrations exercised, local commit IDs if created, and any preserved unrelated changes. Explicitly state that no push or deployment occurred.
