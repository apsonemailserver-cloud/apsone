# Role Permission Matrix Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current visually noisy role-permission editor with a clean APS ONE-native enterprise matrix that works consistently in light mode, dark mode, and responsive layouts.

**Architecture:** Keep the existing Laravel form fields, role update route, and employee-toggle endpoint unchanged. Recompose the Blade markup into semantic page, matrix, employee-panel, and sticky-action components; style them with page-local semantic theme variables; and update the existing vanilla JavaScript to control selection counts, filters, accessible state, and request rollback.

**Tech Stack:** Laravel 12, Blade, Bootstrap 5 utilities, Tabler Icons, vanilla JavaScript, PHPUnit 11.

## Global Constraints

- Preserve existing permission and employee-role persistence behavior.
- Use the existing APS ONE typography, blue accent, navbar, sidebar, and component proportions.
- Support `.dark-style` and default light mode without hardcoded white-only surfaces.
- Do not add a CSS or JavaScript dependency.
- Use Indonesian UI copy.
- Avoid oversized pills, heavy shadows, excessive blue surfaces, and detached floating controls.
- Preserve unrelated working-tree changes.

---

## File Structure

- Modify `resources/views/role/edit.blade.php`: own the redesigned markup, page-scoped theme CSS, responsive rules, and client-side interaction behavior.
- Modify `tests/Feature/RolePermissionTest.php`: verify the stable HTML and accessibility contracts used by the redesign while retaining existing server behavior tests.

### Task 1: Establish the Redesign Contract

**Files:**
- Test: `tests/Feature/RolePermissionTest.php`

**Interfaces:**
- Consumes: `GET route('roles.edit', $role->id)`.
- Produces: stable element IDs and class hooks for permission search, bulk actions, employee filters, the employee list, permission counter, and save action.

- [ ] **Step 1: Write a failing feature test**

Add this test:

```php
public function test_role_editor_renders_clean_matrix_controls(): void
{
    $admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);
    $role = Role::where('name', 'Staff')->firstOrFail();

    $response = $this->actingAs($admin)->get(route('roles.edit', $role->id));

    $response->assertOk();
    $response->assertSee('role-access-page', false);
    $response->assertSee('id="permissionSearchInput"', false);
    $response->assertSee('id="btnSelectAll"', false);
    $response->assertSee('id="btnUnselectAll"', false);
    $response->assertSee('data-employee-filter="selected"', false);
    $response->assertSee('data-employee-filter="all"', false);
    $response->assertSee('id="employeeSearchInput"', false);
    $response->assertSee('id="floatingPermCounter"', false);
    $response->assertSee('Simpan Perubahan');
    $response->assertSee('name="permissions[]"', false);
}
```

- [ ] **Step 2: Run the focused test and verify RED**

Run:

```bash
php artisan test tests/Feature/RolePermissionTest.php --filter=role_editor_renders_clean_matrix_controls
```

Expected: FAIL because the page lacks the new page hook, employee filter controls, and updated save copy.

- [ ] **Step 3: Commit the test contract after the implementation reaches green**

Stage this test together with the implementation in Task 3; do not commit a red test on `main`.

### Task 2: Recompose the Blade Interface and Theme

**Files:**
- Modify: `resources/views/role/edit.blade.php:5-295`

**Interfaces:**
- Consumes: existing `$role`, `$categories`, `$moduleActionsMap`, `$permissionsByModule`, `$assignedPermissionIds`, `$employees`, and `$activeEmployeeCount` values.
- Produces: the exact DOM hooks asserted by Task 1 and retained checkbox names/values required by `RoleController::update()`.

- [ ] **Step 1: Replace the page shell and header**

Use a `.role-access-page` root with a compact title block, quiet role metadata, and a separate `.matrix-toolbar`. Keep the form action, CSRF token, method, hidden label, and hidden description exactly as they are. The toolbar must include:

```blade
<label class="matrix-search" for="permissionSearchInput">
    <i class="ti ti-search" aria-hidden="true"></i>
    <input type="search" id="permissionSearchInput" placeholder="Cari modul atau izin" aria-label="Cari modul atau izin">
</label>
<button type="button" class="matrix-btn matrix-btn--secondary" id="btnUnselectAll">Bersihkan</button>
<button type="button" class="matrix-btn matrix-btn--primary-soft" id="btnSelectAll">Pilih semua</button>
```

- [ ] **Step 2: Replace table-heavy permission category markup**

Render each category as `.permission-section` with `data-category-card`, a header, selected counter, and a real button using `.btn-select-cat-all`. Render every module as `.permission-row.module-perm-group` with three children: `.permission-module`, `.permission-options`, and a `.btn-select-module-all` button. Retain the real hidden checkbox:

```blade
<input
    type="checkbox"
    name="permissions[]"
    value="{{ $perm->id }}"
    id="perm_input_{{ $perm->id }}"
    class="permission-checkbox perm-checkbox-hidden perm-mod-cb-{{ $modKey }} perm-cat-cb-{{ Str::slug($catName) }}"
    {{ $isChecked ? 'checked' : '' }}
>
<label for="perm_input_{{ $perm->id }}" class="permission-toggle">
    <i class="ti ti-check" aria-hidden="true"></i>
    <span>{{ $actName }}</span>
</label>
```

Hide the native checkbox visually with an accessible clipping technique rather than `display:none`, so keyboard focus reaches it.

- [ ] **Step 3: Rebuild the employee panel**

Add a `.employee-panel` with a compact header, count, selected/all segmented filter, labelled search field, and scrollable list. Each employee row keeps `.employee-item-row`, `data-has-role`, `.employee-name`, `.employee-nip`, `.user-role-toggle`, and `data-user-id`. Add:

```blade
<div class="employee-filter" role="group" aria-label="Filter karyawan">
    <button type="button" class="employee-filter__button is-active" data-employee-filter="selected" aria-pressed="true">Terpilih</button>
    <button type="button" class="employee-filter__button" data-employee-filter="all" aria-pressed="false">Semua</button>
</div>
```

- [ ] **Step 4: Replace the floating save pill**

Use a `.role-savebar` inside the form after the two-column content. Keep `id="floatingPermCounter"`, change the save copy to `Simpan Perubahan`, and use a normal text cancel link. The bar must remain sticky but contained by the page width.

- [ ] **Step 5: Replace inline hardcoded styling with semantic page variables**

Define variables under `.role-access-page`, including:

```css
.role-access-page {
    --ra-surface: var(--bs-card-bg, #fff);
    --ra-surface-muted: #f7f9fc;
    --ra-border: #e4e9f1;
    --ra-text: #26334d;
    --ra-muted: #75829a;
    --ra-primary: #2f7df4;
    --ra-primary-soft: #eaf3ff;
    --ra-shadow: 0 10px 30px rgba(35, 51, 80, 0.06);
}
.dark-style .role-access-page {
    --ra-surface: #151f33;
    --ra-surface-muted: #111a2b;
    --ra-border: #283650;
    --ra-text: #e4eaf5;
    --ra-muted: #94a3ba;
    --ra-primary: #5b9cff;
    --ra-primary-soft: rgba(64, 137, 255, 0.14);
    --ra-shadow: 0 12px 32px rgba(0, 0, 0, 0.22);
}
```

Apply these variables to every page-owned surface, border, text, hover, selected, disabled, focus-visible, and loading state. Use a maximum 14px radius and only the subtle section/panel shadow.

- [ ] **Step 6: Add responsive rules**

At widths below `1200px`, stack the employee panel beneath the matrix and remove its sticky positioning. At widths below `768px`, stack toolbar actions, permission row regions, and save actions; prevent horizontal overflow; keep permission controls at least 36px high. Add `prefers-reduced-motion: reduce` to disable page transitions.

### Task 3: Update Interaction State and Reach GREEN

**Files:**
- Modify: `resources/views/role/edit.blade.php:297-459`
- Test: `tests/Feature/RolePermissionTest.php`

**Interfaces:**
- Consumes: DOM hooks produced by Task 2 and existing `POST /roles/{id}/toggle-user` JSON contract.
- Produces: synchronized permission counts, category counts, filter state, selected styling, accessible pressed states, and AJAX rollback.

- [ ] **Step 1: Centralize permission UI refresh**

Implement `updatePermissionUi()` to count checked permissions globally and per category, write `#floatingPermCounter`, update `[data-category-count]`, set each category/module bulk button `aria-pressed`, and rely on the native checkbox `:checked + .permission-toggle` selector for toggle styling.

- [ ] **Step 2: Preserve global, category, and module bulk actions**

Each action changes its scoped `.permission-checkbox`, then dispatches `change`. `filterPermissionsList()` must match module labels, keys, and permission action text, and hide a `[data-category-card]` when all its module rows are hidden.

- [ ] **Step 3: Add selected/all employee filtering**

Track `currentEmployeeFilter = 'selected'`. `filterEmployeeList()` combines the active filter with case-insensitive name, ID, and station search. Filter buttons update `aria-pressed` and `.is-active`, then rerun filtering.

- [ ] **Step 4: Harden asynchronous employee toggles**

Disable the switch and add `.is-loading` during the request. Check `response.ok`, update `data-has-role`, active classes, avatar state, count, and current filter when successful. On failure, restore the original checked state and row state, then show the existing SweetAlert integration when available or a concise browser alert fallback. Re-enable the switch in `.finally()`.

- [ ] **Step 5: Run the focused tests and verify GREEN**

Run:

```bash
php artisan test tests/Feature/RolePermissionTest.php
```

Expected: all role permission tests PASS, including permission update and employee toggle persistence.

- [ ] **Step 6: Compile Blade views**

Run:

```bash
php artisan view:cache
php artisan view:clear
```

Expected: both commands exit successfully without a Blade syntax error.

- [ ] **Step 7: Commit the implementation**

```bash
git add resources/views/role/edit.blade.php tests/Feature/RolePermissionTest.php
git commit -m "feat: redesign role permission matrix"
```

### Task 4: Visual and Regression Verification

**Files:**
- Verify: `resources/views/role/edit.blade.php`
- Verify: `tests/Feature/RolePermissionTest.php`

**Interfaces:**
- Consumes: running local Laravel app and authenticated Admin role editor.
- Produces: verified desktop/mobile and light/dark presentation with working controls.

- [ ] **Step 1: Run the full test suite**

```bash
php artisan test
```

Expected: all tests PASS. Record any unrelated pre-existing failure separately while keeping all role tests green.

- [ ] **Step 2: Run static checks**

```bash
git diff --check
vendor/bin/pint --test tests/Feature/RolePermissionTest.php
```

Expected: no whitespace or PHP formatting errors.

- [ ] **Step 3: Verify desktop light and dark mode**

Open `/roles/1/edit` at approximately `1440x900`. Verify header hierarchy, permission scanability, sticky employee panel, contained save bar, selected/inactive contrast, searches, bulk actions, employee filters, toggle loading/rollback, and save. Repeat after switching to dark mode.

- [ ] **Step 4: Verify responsive layouts**

At approximately `768x1024` and `390x844`, verify stacked columns, wrapped toolbars, tap targets, save bar usability, and absence of horizontal page overflow in both themes.

- [ ] **Step 5: Inspect the final diff**

```bash
git status --short
git diff --stat HEAD~1
```

Expected: only intended role editor/test changes plus pre-existing user-owned changes are present.
