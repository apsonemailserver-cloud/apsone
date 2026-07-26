# Staff Personal Dashboard Design

## Purpose

Replace management-wide statistics on the dashboard for operational Staff with a personal, actionable view. Management roles retain the current dashboard unchanged.

## Role Scope

The management dashboard remains available to users with at least one of these roles:

- `Admin`
- `Head Of Airport Service`
- `SPV Bge`
- `SPV Apron`
- `Leader Bge`
- `Leader Apron`
- `Ass Leader Bge`
- `Ass Leader Apron`
- `Leader Aircraft Interior Exterior Cleaning`
- `Leader Porter Apron`
- `Finance`
- `HSE`
- `Controller`
- `Quality Control`

All other roles use the Staff personal dashboard. Role detection must support the application's comma-separated multi-role values through `User::hasRole()`.

## Staff Dashboard

The existing greeting, station scope, attendance action, navigation, and theme behavior remain visible.

### KPI Cards

1. **Pengerjaan Hari Ini**
   - Count distinct flights assigned to the authenticated user today.
   - Assignment is resolved through `flight_details.schedule_id` to `schedules.user_id`.
2. **Persentase Kehadiran Anda**
   - Calculate month-to-date attendance through today.
   - Denominator: authenticated user's scheduled dates from the first day of the current month through today.
   - Numerator: those scheduled dates with at least one attendance record containing `check_in_time`.
   - Return `0%` when no scheduled date exists.
3. **Penerbangan Selesai**
   - Count the authenticated user's assigned flights today whose `flights.status` is true.

### Riwayat Presensi Anda

Show the authenticated user's seven most recent attendance records with:

- date;
- office, preferring attendance station and falling back to the user's station;
- scheduled shift time for the attendance date;
- check-in time;
- check-out time;
- status derived from the record: `Hadir`, `Belum Check-out`, or `Tidak Lengkap`.

The table shows a personal empty state when no attendance records exist.

### Data Pengerjaan Hari Ini

Show only today's flights assigned to the authenticated user. Columns:

- airline;
- flight number;
- registration;
- type;
- arrival;
- countdown;
- status.

This table is read-only for operational Staff and shows a personal empty state when the user has no assignment.

### Data Penerbangan Hari Ini

Keep the existing station-scoped list of today's flights so Staff can see current station operations. Existing action authorization remains unchanged.

## Management Dashboard

The current dashboard remains unchanged for management roles, including:

- station monitoring for Admin;
- global KPI cards;
- aircraft performance chart;
- staff role distribution;
- seven-day absence chart;
- today's attendance statistics;
- today's flight table.

## Data and Security

- The controller determines the dashboard mode; the client cannot select it.
- Staff requests do not run management-only aggregate queries.
- Management-only data and chart datasets are not embedded in Staff HTML or JavaScript.
- Every personal query is scoped by the authenticated user's ID.
- Station-wide flight data is restricted to the authenticated user's station.

## Implementation Structure

`HomeController::index()` determines the dashboard mode. Management keeps the existing data path. Staff data is prepared by a focused private method and returned to the existing `home` view with a `showManagementDashboard` flag.

The Blade view renders separate management and Staff sections. Management chart initialization is rendered only when the management section is active, preventing missing-canvas errors and preventing management datasets from being sent to Staff.

## Testing

Feature tests verify:

- operational roles receive the Staff dashboard;
- each management role, including a comma-separated multi-role user, retains the management dashboard;
- management-only labels and data are absent from Staff responses;
- Staff KPI counts use only the authenticated user's assigned flights;
- attendance percentage uses scheduled month-to-date dates and authenticated-user attendance;
- attendance history contains only the authenticated user's records;
- Staff assignment data contains only the authenticated user's assigned flights;
- station flight data excludes flights from other stations;
- Staff dashboard renders valid empty states.

## Compatibility

No database migration is required. Existing flights, flight details, schedules, attendances, users, and stations remain unchanged.
