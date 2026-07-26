# Attendance Correction Design

## Purpose

Add a one-time attendance correction workflow. A user can propose corrected check-in and check-out times for a date from Attendance History. The user's manager or an Admin reviews the proposal from a dedicated approval menu. Attendance data changes only after approval.

## Scope

The feature includes:

- an Edit/Correction action in Attendance History;
- a correction form with check-in, check-out, office, and reason;
- office options sourced from active records in `stations`;
- a dedicated Attendance Correction Approval menu;
- manager- and Admin-scoped approval access;
- approval that creates a missing attendance record or updates an existing record;
- final Pending, Approved, and Rejected states shown in Attendance History.

The feature does not include repeat submissions, multi-level approval, notifications, attachments, or reopening rejected requests.

## Business Rules

1. A user may submit at most one attendance correction for a calendar date for the lifetime of that date's correction record.
2. Approved and rejected corrections are final. A rejected correction cannot be resubmitted.
3. Corrections are allowed for today and past dates only. Future dates are not eligible.
4. Check-in must fall on the selected attendance date.
5. Check-out must be later than check-in. It may fall on the following day to support overnight shifts.
6. Office must reference an active station from the database.
7. Submitting a correction does not modify `attendances`.
8. The authorized approver is:
   - an Admin; or
   - the authenticated user whose `fullname` exactly matches the applicant's `manager` field.
9. Approval applies the proposal atomically:
   - create an attendance record when none exists for the applicant and correction date; or
   - update the existing attendance record's check-in, check-out, and office.
10. Rejection changes only the correction status and decision audit fields.

## Data Model

### `attendance_corrections`

Create a table with:

- `id`;
- `user_id`, string foreign key to the applicant because `users.id` is a string key;
- `attendance_id`, nullable foreign key to the attendance record known at submission or applied at approval;
- `station_id`, foreign key to the selected office;
- `attendance_date`;
- `proposed_check_in_time`;
- `proposed_check_out_time`;
- `reason`;
- `status`, using `pending`, `approved`, or `rejected`;
- `decided_by`, nullable string foreign key to the deciding user;
- `decided_at`, nullable timestamp;
- timestamps.

Add a unique constraint on `(user_id, attendance_date)` to enforce the one-submission rule at the database level.

### `attendances`

Add nullable `station_id`, foreign keyed to `stations`. Existing rows remain nullable and may display the user's current station as a compatibility fallback. New physical check-ins should save the resolved station ID. Approved corrections always save the correction's station ID.

## Application Structure

Create an `AttendanceCorrection` model with relationships to applicant, attendance, station, and approver. Keep correction submission and approval behavior in a dedicated controller so the existing attendance capture controller remains focused on physical check-in/check-out.

Routes under authenticated access provide:

- correction form for a history date;
- correction submission;
- manager/Admin approval list;
- approve action;
- reject action.

Every controller action performs ownership or approver authorization on the server. UI visibility is not treated as authorization.

## User Flow

### Applicant

Attendance History gains an Action column.

- Eligible date with no prior correction: show `Edit/Koreksi`.
- Pending correction: show a Pending badge and no new submission action.
- Approved correction: show an Approved badge.
- Rejected correction: show a Rejected badge.
- Future date: show no correction action.

Attendance History also gains a `Note Koreksi` column between `Out` and `Action`. It displays the submitted correction reason for Pending, Approved, and Rejected requests, and displays `-` when the date has no correction request.

The correction form is prefilled from the current attendance record when one exists. Otherwise the date is fixed and the time fields start empty. The user selects an active office and supplies a required reason.

### Approver

The Attendance menu gains `Approval Koreksi Absensi` for Admin users and users who are referenced as a manager by at least one employee.

The page lists pending requests with applicant, date, office, proposed times, current times, and reason. A non-Admin sees only requests from applicants whose `manager` exactly equals the approver's `fullname`. An Admin can see all pending requests and may filter them.

Approve and Reject are POST actions. Both re-check authorization and ensure the request is still pending to prevent repeated decisions.

## Error Handling and Consistency

- Validation failures return to the form with old input and field errors.
- Duplicate submissions are rejected by validation and protected by the database unique constraint.
- Approval runs in a database transaction and locks the correction row before checking its status.
- Attendance lookup uses the applicant and calendar date, not a client-provided attendance ID.
- A stale second approval or rejection attempt is rejected without modifying attendance.
- Missing manager configuration does not expose a request to unrelated users; Admin remains able to process it.

## Testing

Feature tests will verify:

- a user can open and submit a valid correction for their own eligible date;
- office options come from active stations;
- future dates and invalid time ordering are rejected;
- a second correction for the same user and date is rejected after pending, approval, or rejection;
- users cannot submit on behalf of another user;
- the configured manager sees and can decide the request;
- unrelated users cannot view or decide it;
- Admin can view and decide any request;
- approval creates attendance when absent;
- approval updates attendance when present;
- approval saves `station_id`;
- rejection leaves attendance unchanged;
- a decided request cannot be decided again;
- Attendance History renders the correct action or status.

## Migration and Compatibility

The migrations are additive. Existing attendance rows remain valid because `station_id` is nullable. History and reports use the attendance station when present and fall back to the user's station for legacy rows. No existing attendance data is rewritten during migration.
