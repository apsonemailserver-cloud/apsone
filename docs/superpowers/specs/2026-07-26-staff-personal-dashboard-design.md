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

1. **Pengerjaan 1 Bulan Terakhir**
   - Count distinct flights assigned to the authenticated user during the last
     30 calendar days, inclusive of today.
   - Assignment is resolved through `flight_details.schedule_id` to `schedules.user_id`.
2. **Persentase Kehadiran Anda (1 Bulan Terakhir)**
   - Calculate attendance during the last 30 calendar days, inclusive of today.
   - Denominator: authenticated user's scheduled dates during that period.
   - Numerator: those scheduled dates with at least one attendance record containing `check_in_time`.
   - Return `0%` when no scheduled date exists.
3. **Penerbangan Selesai (1 Bulan Terakhir)**
   - Count the authenticated user's assigned flights during the last 30 calendar
     days whose `flights.status` is true.

### Riwayat Presensi Anda

Show all of the authenticated user's attendance records from the last seven
calendar days, inclusive of today (today plus the previous six days), with:

- date;
- office, preferring attendance station and falling back to the user's station;
- scheduled shift time for the attendance date;
- check-in time;
- check-out time;
- status derived from the record: `Hadir`, `Belum Check-out`, or `Tidak Lengkap`.

The table shows a personal empty state when no attendance records exist in that
period. The section title includes **(7 Hari Terakhir)**.

### Data Pengerjaan

Show flights assigned to the authenticated user from the last 30 calendar days,
inclusive of today. Columns:

- airline;
- flight number;
- registration;
- type;
- arrival;
- countdown;
- status.

This table is read-only for operational Staff and shows a personal empty state
when the user has no assignment in that period. The section title includes
**(1 Bulan Terakhir)**.

### Data Penerbangan

Show the existing station-scoped flight list for the last seven calendar days,
inclusive of today (today plus the previous six days), so Staff can see recent
station operations. Existing action authorization remains unchanged. The
section title includes **(7 Hari Terakhir)**.

### Shared Monthly Period

The three KPI cards and the Staff assignment table share the same rolling
30-calendar-day period, inclusive of today. This keeps the summary values
consistent with the rows shown under **Data Pengerjaan (1 Bulan Terakhir)**.

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
- Staff KPI counts use only the authenticated user's assigned flights from the
  last 30 calendar days;
- attendance percentage uses the authenticated user's scheduled dates and
  attendance from the last 30 calendar days;
- attendance history contains only the authenticated user's records;
- attendance history includes records inside the last seven calendar days and
  excludes older records;
- Staff assignment data contains only the authenticated user's assigned flights
  from the last 30 calendar days and excludes older records;
- station flight data includes only the last seven calendar days, excludes
  older records, and excludes flights from other stations;
- the three KPI values use the same rolling 30-calendar-day period as the
  assignment table;
- Staff dashboard renders valid empty states.

## Compatibility

No database migration is required. Existing flights, flight details, schedules, attendances, users, and stations remain unchanged.
