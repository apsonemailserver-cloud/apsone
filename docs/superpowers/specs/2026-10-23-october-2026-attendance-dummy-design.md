# October 2026 Attendance Dummy Data

## Goal

Populate the attendance report export with plausible sample attendance records for October 2026.

## Design

Add a dedicated Laravel seeder for October 2026. It will use active employees and their existing schedules, create records only for scheduled workdays from October 1 through October 23, and leave non-workdays and days without a schedule untouched. Generated records will contain check-in and check-out timestamps, punctual or late status, station, and plausible station coordinates, matching the fields consumed by the attendance report export.

The seeder will be repeatable: it will skip dates that already have an attendance record for that employee, so it does not overwrite real or previously generated entries. It will not truncate attendance tables or alter schedules. The existing export remains unchanged and can be run for `2026-10` to view the generated rows.

## Verification

Review the seeder's date bounds, schedule filtering, duplicate protection, and the attendance export's expected columns. Do not run the seeder against an unknown or production database. No automated tests are required for this data-only change.

## Scope

Only the new October 2026 attendance seeder and this design note are in scope. The data range ends on October 23, 2026, the current date in the project environment.
