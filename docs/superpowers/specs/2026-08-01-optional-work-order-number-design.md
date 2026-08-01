# Optional Work Order Number Design

## Goal

Allow an assignment to be created either with or without a work order number. The work order number must be visible on the Assignment form, but it must not be required.

## User Experience

- Replace the hidden, automatically populated `wo_number` input with a visible text field labelled **WO Number (Opsional)**.
- Leave the field empty by default.
- Preserve the submitted value when validation returns the user to the form.
- Do not block submission when the field is empty.
- Where an assignment without a work order number is displayed, show `-` instead of an empty label or a generated number.

## Data and Application Flow

1. The user may enter a work order number or leave the field empty.
2. The controller trims a submitted number. An empty or whitespace-only value becomes `null`.
3. The `work_orders.wo_number` database column accepts `null`.
4. The assignment is saved without generating a replacement number.
5. List, dashboard, detail, and PDF views render the stored number when present and `-` when absent.

Existing work order numbers remain unchanged. The change does not add uniqueness rules or alter imported work order numbering.

## Validation and Error Handling

- `wo_number` remains optional.
- A supplied value must be a string with a maximum length of 100 characters.
- Existing validation and authorization behavior for all other fields remains unchanged.
- The schema migration is reversible and restores the original non-nullable column on rollback after replacing existing null values with an empty string, so rollback does not fail.

## Affected Components

- Assignment create form
- Work order storage controller
- `work_orders` database schema
- Assignment list and dashboard summaries
- Assignment detail view
- Work order PDF views and filenames/captions where an absent number would otherwise render awkwardly

## Testing

Automated feature tests will verify that:

- the create form contains a visible optional `wo_number` input;
- an assignment with a supplied work order number stores that number;
- an assignment without a work order number stores `null` and is accepted;
- whitespace-only input is normalized to `null`;
- relevant views use `-` for an absent work order number.

Implementation will follow a test-first red-green-refactor cycle.
