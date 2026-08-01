# Role Permission Matrix Redesign

## Goal

Redesign the Role Permission Matrix as a clean, modern enterprise interface that feels native to the existing APS ONE theme in both light and dark modes. The redesign must improve hierarchy, scanning, spacing, and responsive behavior without changing how permissions or employee-role assignments are saved.

## Design Direction

Use a restrained **Clean Enterprise Matrix** direction. The interface should rely on typography, alignment, subtle borders, and controlled spacing instead of repeated blue pills, large shadows, or decorative badges. Blue remains the primary APS accent, but it is reserved for selected states and the primary save action.

## Page Structure

### Header and Toolbar

- Keep the back action, page title, role identity, and description together as a compact header block.
- Display the role name and full-access status as quiet metadata rather than competing badges.
- Place permission search and global select/clear actions in a dedicated toolbar directly above the matrix.
- Use explicit Indonesian labels throughout the redesigned interface.

### Permission Matrix

- Keep the existing Operational, Administration, and General & Compliance categories.
- Present each category as a low-elevation surface with a subtle theme-aware border and consistent radius.
- Give category headers a clear title, selected-permission count, and compact category-level selection control.
- Lay out each module row using a stable three-part grid: module identity, available permission controls, and module-level selection action.
- Replace oversized outlined pills with compact permission toggles. Inactive controls use neutral theme colors; active controls use the APS primary accent with clear contrast and a check icon.
- Reduce vertical whitespace while retaining comfortable hit targets and keyboard focus visibility.
- Searching hides unmatched modules and categories with no visible results.

### Employee Panel

- Keep the employee panel beside the permission matrix on wide screens and stack it below the matrix on smaller screens.
- Use a compact header showing the role-member count.
- Add client-side **Terpilih** and **Semua** filters, with Terpilih as the default view so assigned employees are immediately visible.
- Keep employee search and allow matching by name, ID, or station.
- Render employee rows with restrained backgrounds, consistent avatar sizing, clear metadata, and a standard switch control.
- Updating a switch continues to call the existing role-toggle endpoint and updates the count and filter state without reloading.

### Save Actions

- Replace the detached floating pill with a contained sticky action bar aligned to the main page content.
- Show the current number of selected permissions as secondary information.
- Keep a quiet cancel action and one visually dominant save action.
- On narrow screens, the bar remains usable without covering matrix or employee content.

## Theme System

- Define page-local semantic CSS variables for surface, elevated surface, border, muted background, primary-soft background, text, muted text, and shadow.
- Map those variables to existing APS light and `.dark-style` theme values.
- Avoid hardcoded white surfaces and pale-blue backgrounds that break in dark mode.
- Provide deliberate default, hover, selected, disabled, and focus-visible states for every interactive control.
- Use existing project typography and Tabler icons; do not introduce new UI dependencies.
- Keep shadows minimal and use borders to establish most hierarchy.

## Responsive Behavior

- Large desktop: permission matrix and employee panel display side by side, with the employee panel sticky within the viewport.
- Tablet: columns stack, permission rows preserve their internal grid where space allows, and toolbars wrap cleanly.
- Mobile: module identity, permission controls, and bulk action stack vertically; permission toggles remain large enough to tap; the sticky action bar becomes full-width within the content area.
- No horizontal page overflow is allowed at supported breakpoints.

## Interaction and Data Flow

- Permission checkboxes remain the source of truth submitted by the existing role update form.
- Global, category, and module bulk actions update the same checkboxes and selected-permission counters.
- Employee assignment switches retain the existing asynchronous endpoint and rollback their visual state if the request fails.
- Admin continues to receive full access according to the existing server rule; the redesigned UI clearly communicates this state without changing authorization logic.
- Search and employee filters are client-side presentation behavior and do not change persisted data.

## Accessibility

- Every permission toggle remains associated with a real checkbox and accessible label.
- Bulk actions use buttons rather than JavaScript links.
- Search inputs have visible labels or accessible names.
- Focus-visible outlines must meet the same contrast standard in light and dark modes.
- Selected state must be communicated by text/icon and not color alone.
- Motion is limited to short state transitions and respects `prefers-reduced-motion`.

## Testing and Verification

- Feature tests verify the page still renders all permission inputs, category/module bulk controls, employee search/filter controls, and save actions.
- Existing permission update and employee role-toggle tests must remain green.
- Browser verification covers permission selection, all bulk actions, search, employee filtering, employee toggling, save, and cancellation.
- Visual QA covers light and dark modes at desktop, tablet, and mobile widths.
- The final implementation must pass Blade compilation, relevant feature tests, and the full automated test suite.

## Non-Goals

- No database, permission model, authorization rule, route, or endpoint redesign.
- No new CSS or JavaScript framework.
- No unrelated sidebar, navbar, or role-index redesign.
