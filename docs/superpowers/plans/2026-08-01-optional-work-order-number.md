# Optional Work Order Number Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show an optional WO Number field on Assignment creation and persist assignments without inventing a number when none is supplied.

**Architecture:** Keep `wo_number` as the existing WorkOrder attribute, but make its database column nullable and normalize blank HTTP input to `null` in the controller. Add one display accessor on `WorkOrder` so web and PDF views consistently render `-`, while controller-generated filenames use a safe assignment fallback.

**Tech Stack:** PHP 8.2, Laravel 12, Blade, Eloquent migrations, PHPUnit 11.

## Global Constraints

- `WO Number` is visible and labelled `WO Number (Opsional)` on the Assignment form.
- The field is empty by default and is never required.
- Empty and whitespace-only input is stored as `null`; no replacement WO number is generated.
- Supplied values remain strings with a maximum length of 100 characters and are trimmed before storage.
- Existing stored and imported work order numbers remain unchanged.
- User-facing list, dashboard, detail, and PDF output renders `-` when no number exists.
- Preserve unrelated working-tree changes and stage only the files listed in each task.

---

## File Structure

- Create `database/migrations/2026_08_01_200000_make_wo_number_nullable_on_work_orders_table.php`: safely change `work_orders.wo_number` to nullable and reverse it.
- Modify `app/Http/Controllers/WorkOrderController.php`: stop auto-generating form/store numbers, normalize optional input, and produce a safe PDF filename and upload message.
- Modify `app/Models/WorkOrder.php`: provide the shared `wo_number_label` presentation accessor.
- Modify `resources/views/work_order/create.blade.php`: render the visible optional input.
- Modify `resources/views/work_order/index.blade.php`, `resources/views/work_order/show.blade.php`, `resources/views/work_order/pdf.blade.php`, `resources/views/work_order/single_pdf.blade.php`, and `resources/views/home.blade.php`: use the shared label instead of raw nullable output.
- Modify `tests/Feature/WorkOrderTest.php`: cover form, persistence, normalization, index, detail, and PDF behavior.
- Create `tests/Unit/WorkOrderTest.php`: cover the model presentation contract without database or HTTP setup.

### Task 1: Persist Assignments Without a WO Number

**Files:**
- Create: `database/migrations/2026_08_01_200000_make_wo_number_nullable_on_work_orders_table.php`
- Modify: `app/Http/Controllers/WorkOrderController.php:181-197`
- Test: `tests/Feature/WorkOrderTest.php`

**Interfaces:**
- Consumes: request field `wo_number: string|null`.
- Produces: `work_orders.wo_number: string|null`, with trimmed non-empty strings and `null` for missing/blank input.

- [ ] **Step 1: Write failing feature tests for missing and whitespace-only WO numbers**

Add a reusable valid payload helper inside `WorkOrderTest`:

```php
private function validPayload(array $overrides = []): array
{
    return array_merge([
        'date' => '2026-07-31',
        'station' => 'CGK',
        'aircraft_reg' => 'PK-LGH',
        'ex_flight' => 'JT 371',
        'to_flight' => 'JT 202',
        'parking_stand' => 'A12',
        'start_time' => '08:00',
        'end_time' => '09:30',
        'action' => 'DCI',
        'staff_members' => [$this->staff1->id, $this->staff2->id],
    ], $overrides);
}
```

Add these tests:

```php
public function test_assignment_can_be_created_without_a_wo_number(): void
{
    $response = $this->actingAs($this->user)->post(
        route('work_orders.store'),
        $this->validPayload()
    );

    $response->assertRedirect(route('work_orders.index'));
    $this->assertDatabaseHas('work_orders', [
        'aircraft_reg' => 'PK-LGH',
        'wo_number' => null,
    ]);
}

public function test_whitespace_only_wo_number_is_stored_as_null(): void
{
    $response = $this->actingAs($this->user)->post(
        route('work_orders.store'),
        $this->validPayload(['wo_number' => '   '])
    );

    $response->assertRedirect(route('work_orders.index'));
    $this->assertDatabaseHas('work_orders', ['wo_number' => null]);
}

public function test_supplied_wo_number_is_trimmed_before_storage(): void
{
    $response = $this->actingAs($this->user)->post(
        route('work_orders.store'),
        $this->validPayload(['wo_number' => '  WO-EXT-007  '])
    );

    $response->assertRedirect(route('work_orders.index'));
    $this->assertDatabaseHas('work_orders', ['wo_number' => 'WO-EXT-007']);
}
```

- [ ] **Step 2: Run the new tests and verify RED**

Run:

```bash
php artisan test tests/Feature/WorkOrderTest.php --filter='assignment_can_be_created_without|whitespace_only_wo|supplied_wo_number_is_trimmed'
```

Expected: FAIL because missing/blank input is replaced by an auto-generated `WO-YYYY-NNN`, and padded input is stored untrimmed.

- [ ] **Step 3: Add the nullable schema migration**

Create the migration with this complete implementation:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('wo_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('work_orders')->whereNull('wo_number')->update(['wo_number' => '']);

        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('wo_number')->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 4: Replace automatic generation with normalization**

Keep the existing validation rule:

```php
'wo_number' => 'nullable|string|max:100',
```

Replace the automatic generation block with:

```php
$woNumber = trim((string) $request->input('wo_number'));
$woNumber = $woNumber !== '' ? $woNumber : null;
```

Continue passing `$woNumber` into `WorkOrder::create()`:

```php
'wo_number' => $woNumber,
```

- [ ] **Step 5: Run the focused tests and verify GREEN**

Run:

```bash
php artisan test tests/Feature/WorkOrderTest.php --filter='assignment_can_be_created_without|whitespace_only_wo|supplied_wo_number_is_trimmed|user_can_submit_work_result_as_dci'
```

Expected: four tests PASS, proving both optional and supplied WO paths.

- [ ] **Step 6: Commit the persistence slice**

```bash
git add app/Http/Controllers/WorkOrderController.php database/migrations/2026_08_01_200000_make_wo_number_nullable_on_work_orders_table.php tests/Feature/WorkOrderTest.php
git commit -m "feat: allow assignments without work order numbers"
```

### Task 2: Show the Optional Field on the Assignment Form

**Files:**
- Modify: `app/Http/Controllers/WorkOrderController.php:86-132`
- Modify: `resources/views/work_order/create.blade.php:58-94`
- Test: `tests/Feature/WorkOrderTest.php`

**Interfaces:**
- Consumes: Laravel `old('wo_number')` flashed form input.
- Produces: visible `<input type="text" name="wo_number">`; the create view no longer requires `$nextWoNumber`.

- [ ] **Step 1: Write a failing create-form test**

Add:

```php
public function test_create_form_shows_an_optional_wo_number_field(): void
{
    $response = $this->actingAs($this->user)->get(route('work_orders.create'));

    $response->assertOk();
    $response->assertSee('WO Number (Opsional)');
    $response->assertSee('name="wo_number"', false);
    $response->assertDontSee('type="hidden" name="wo_number"', false);
    $response->assertDontSee('name="wo_number" required', false);
}
```

- [ ] **Step 2: Run the form test and verify RED**

Run:

```bash
php artisan test tests/Feature/WorkOrderTest.php --filter=create_form_shows_an_optional_wo_number_field
```

Expected: FAIL because the label is absent and `wo_number` is still a hidden input.

- [ ] **Step 3: Render a visible, empty-by-default form control**

Delete the hidden input and add the following as the first item in the first form row. Adjust that row to `col-md-3` for WO, date, and station, and `col-md-3` for the flight selector so the layout remains a single 12-column row:

```blade
<div class="col-md-3">
    <label class="form-label fw-semibold" for="woNumberInput">WO Number (Opsional)</label>
    <input
        type="text"
        class="form-control"
        id="woNumberInput"
        name="wo_number"
        value="{{ old('wo_number') }}"
        maxlength="100"
        placeholder="e.g. WO-2026-001"
    >
</div>
```

- [ ] **Step 4: Remove unused automatic-number lookup from `create()`**

Delete `$currentYear`, `$prefix`, `$latestWorkOrder`, `$nextSequence`, and `$nextWoNumber` calculation. Return the view with only the values it uses:

```php
return view('work_order.create', compact('stations', 'staffs', 'availableFlights'));
```

- [ ] **Step 5: Run the form and persistence tests**

Run:

```bash
php artisan test tests/Feature/WorkOrderTest.php --filter='create_form_shows_an_optional|assignment_can_be_created_without|user_can_submit_work_result_as_dci'
```

Expected: three tests PASS.

- [ ] **Step 6: Commit the form slice**

```bash
git add app/Http/Controllers/WorkOrderController.php resources/views/work_order/create.blade.php tests/Feature/WorkOrderTest.php
git commit -m "feat: show optional WO number on assignments"
```

### Task 3: Render Missing WO Numbers Consistently

**Files:**
- Modify: `app/Models/WorkOrder.php`
- Modify: `app/Http/Controllers/WorkOrderController.php:413,440`
- Modify: `resources/views/work_order/index.blade.php:144-210`
- Modify: `resources/views/work_order/show.blade.php:3-168`
- Modify: `resources/views/work_order/pdf.blade.php:181`
- Modify: `resources/views/work_order/single_pdf.blade.php:5,320-334,408-451`
- Modify: `resources/views/home.blade.php:308-363`
- Create: `tests/Unit/WorkOrderTest.php`
- Test: `tests/Feature/WorkOrderTest.php`

**Interfaces:**
- Consumes: nullable `WorkOrder::$wo_number`.
- Produces: read-only Eloquent accessor `$workOrder->wo_number_label: string`, returning the stored number or `-`.

- [ ] **Step 1: Write failing unit tests for the display accessor**

Create `tests/Unit/WorkOrderTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\WorkOrder;
use PHPUnit\Framework\TestCase;

class WorkOrderTest extends TestCase
{
    public function test_wo_number_label_returns_stored_number(): void
    {
        $workOrder = new WorkOrder(['wo_number' => 'WO-2026-001']);

        $this->assertSame('WO-2026-001', $workOrder->wo_number_label);
    }

    public function test_wo_number_label_returns_dash_when_number_is_missing(): void
    {
        $workOrder = new WorkOrder(['wo_number' => null]);

        $this->assertSame('-', $workOrder->wo_number_label);
    }
}
```

- [ ] **Step 2: Run the unit tests and verify RED**

Run:

```bash
php artisan test tests/Unit/WorkOrderTest.php
```

Expected: FAIL because `wo_number_label` does not exist.

- [ ] **Step 3: Add the presentation accessor**

Add to `WorkOrder`:

```php
public function getWoNumberLabelAttribute(): string
{
    return $this->wo_number ?: '-';
}
```

- [ ] **Step 4: Run the unit tests and verify GREEN**

Run:

```bash
php artisan test tests/Unit/WorkOrderTest.php
```

Expected: two tests PASS.

- [ ] **Step 5: Write failing feature tests for index and detail output**

Add to `WorkOrderTest`:

```php
public function test_index_displays_dash_for_assignment_without_wo_number(): void
{
    WorkOrder::create([
        'date' => now()->toDateString(),
        'station' => 'CGK',
        'aircraft_reg' => 'PK-NOWO',
        'parking_stand' => 'A12',
        'wo_number' => null,
        'start_time' => '08:00',
        'end_time' => '09:30',
        'type' => 'DCI',
        'submitted_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('work_orders.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('WO: -');
}

public function test_detail_displays_dash_for_assignment_without_wo_number(): void
{
    $workOrder = WorkOrder::create([
        'date' => '2026-07-31',
        'station' => 'CGK',
        'aircraft_reg' => 'PK-NOWO',
        'parking_stand' => 'A12',
        'wo_number' => null,
        'start_time' => '08:00',
        'end_time' => '09:30',
        'type' => 'DCI',
        'submitted_by' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('work_orders.show', $workOrder))
        ->assertOk()
        ->assertSee('>-</span>', false);
}

public function test_pdf_templates_display_dash_for_assignment_without_wo_number(): void
{
    $workOrder = WorkOrder::create([
        'date' => '2026-07-31',
        'station' => 'CGK',
        'aircraft_reg' => 'PK-NOWO',
        'parking_stand' => 'A12',
        'wo_number' => null,
        'start_time' => '08:00',
        'end_time' => '09:30',
        'type' => 'DCI',
        'submitted_by' => $this->user->id,
    ])->load(['users', 'submittedBy']);

    $listHtml = view('work_order.pdf', [
        'workOrders' => collect([$workOrder]),
        'workResults' => collect([$workOrder]),
        'stationLabel' => 'CGK',
        'periodLabel' => '31 Jul 2026',
        'base64Logo' => '',
        'user' => $this->user,
    ])->render();

    $singleHtml = view('work_order.single_pdf', [
        'workOrder' => $workOrder,
        'workResult' => $workOrder,
        'base64Logo' => '',
        'base64Photo' => '',
    ])->render();

    $this->assertStringContainsString('>-</td>', $listHtml);
    $this->assertStringContainsString('wo-badge">-</div>', $singleHtml);
    $this->assertStringContainsString('DOC-REF: APS-ASSIGNMENT-' . $workOrder->id, $singleHtml);
}
```

- [ ] **Step 6: Run the view tests and verify RED**

Run:

```bash
php artisan test tests/Feature/WorkOrderTest.php --filter='index_displays_dash|detail_displays_dash|pdf_templates_display_dash'
```

Expected: FAIL because active Blade files render raw `wo_number`.

- [ ] **Step 7: Switch active web and PDF views to the shared label**

In all visible WO text and `data-wo` attributes in the listed Blade files, replace raw output:

```blade
{{ $item->wo_number }}
{{ $workResult->wo_number }}
{{ $wo->wo_number }}
```

with the matching accessor output:

```blade
{{ $item->wo_number_label }}
{{ $workResult->wo_number_label }}
{{ $wo->wo_number_label }}
```

For the single-PDF document reference, avoid `APS-WO--` with:

```blade
DOC-REF: {{ $workResult->wo_number
    ? 'APS-WO-' . $workResult->wo_number
    : 'APS-ASSIGNMENT-' . $workResult->id }} &nbsp;|&nbsp; {{ date('d/m/Y H:i') }} WIB
```

The visible badge, table cell, and photo caption continue using `wo_number_label`, so they show `-` as required.

- [ ] **Step 8: Make controller-generated text safe without a WO number**

Replace the single PDF filename construction with:

```php
$documentReference = $workOrder->wo_number ?: 'Assignment-' . $workOrder->id;
$filename = 'Hardcopy-' . $documentReference . '-' . str_replace(' ', '-', $workOrder->aircraft_reg) . '.pdf';
```

Replace the upload success text with:

```php
$assignmentReference = $workOrder->wo_number
    ? 'WO ' . $workOrder->wo_number
    : 'assignment #' . $workOrder->id;
Alert::success('Berhasil', 'Foto bukti pekerjaan ' . $assignmentReference . ' berhasil diunggah.');
```

- [ ] **Step 9: Run presentation tests and full WorkOrder feature tests**

Run:

```bash
php artisan test tests/Unit/WorkOrderTest.php tests/Feature/WorkOrderTest.php
```

Expected: all WorkOrder unit and feature tests PASS with no warnings or errors.

- [ ] **Step 10: Commit the presentation slice**

```bash
git add app/Models/WorkOrder.php app/Http/Controllers/WorkOrderController.php resources/views/work_order/index.blade.php resources/views/work_order/show.blade.php resources/views/work_order/pdf.blade.php resources/views/work_order/single_pdf.blade.php resources/views/home.blade.php tests/Unit/WorkOrderTest.php tests/Feature/WorkOrderTest.php
git commit -m "fix: display assignments without WO numbers"
```

### Task 4: Regression and Migration Verification

**Files:**
- Verify only; modify a previously listed file only if a focused test exposes a regression.

**Interfaces:**
- Consumes: completed optional-WO implementation.
- Produces: verified migration lifecycle, formatted code, and passing application suite.

- [ ] **Step 1: Verify migration rollback and re-application on the test database**

Run:

```bash
php artisan migrate:fresh --env=testing
php artisan migrate:rollback --step=1 --env=testing
php artisan migrate --env=testing
```

Expected: each command exits successfully; the optional-WO migration rolls back and reapplies without a nullability error.

- [ ] **Step 2: Format only changed PHP files**

Run:

```bash
vendor/bin/pint app/Http/Controllers/WorkOrderController.php app/Models/WorkOrder.php database/migrations/2026_08_01_200000_make_wo_number_nullable_on_work_orders_table.php tests/Feature/WorkOrderTest.php tests/Unit/WorkOrderTest.php
```

Expected: Pint exits successfully. Review formatting changes before staging.

- [ ] **Step 3: Run the full automated test suite**

Run:

```bash
php artisan test
```

Expected: all tests PASS. If an unrelated pre-existing failure occurs, record its exact test name and verify every optional-WO-focused test still passes.

- [ ] **Step 4: Check diffs and Blade/PHP syntax**

Run:

```bash
git diff --check
php artisan view:cache
php artisan view:clear
git status --short
```

Expected: no whitespace errors, Blade templates compile successfully, and the status contains no unexpected files attributable to this feature.

- [ ] **Step 5: Commit any formatter-only changes if present**

If Pint changed already committed files:

```bash
git add app/Http/Controllers/WorkOrderController.php app/Models/WorkOrder.php database/migrations/2026_08_01_200000_make_wo_number_nullable_on_work_orders_table.php tests/Feature/WorkOrderTest.php tests/Unit/WorkOrderTest.php
git commit -m "style: format optional WO implementation"
```

If there is no diff from formatting, do not create an empty commit.
