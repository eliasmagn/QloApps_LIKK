# Operations Automation Blueprint

This blueprint describes the automation layer for housekeeping, maintenance, staff notifications and data exports. The first
increment ships with the `kloperations` module, which seeds task/run tables, exposes a task generator driven by bookings and
adds a lightweight back-office console for daily housekeeping.

## Housekeeping & Maintenance Tasks
- Generate arrival/departure task lists per day based on confirmed bookings and residency timelines.
- Classify tasks by resource kind (room turnarounds, atelier resets, gastronomy prep) and assign to staff teams.
- Track statuses (`pending`, `in_progress`, `clean`, `needs_repair`) with timestamped employee updates.
- Capture maintenance notes, photos and follow-up reminders for tasks flagged as `needs_repair`.
- Provide printable and mobile-friendly checklists with offline caching.

### Data Model Additions
| Table | Purpose |
| --- | --- |
| `ps_kl_operation_run` | Captures generator executions (type, timestamps, timezone, metadata payloads).
| `ps_kl_operation_task` | Master task table storing resource reference, type (`housekeeping`, `maintenance`), status, due window.
| `ps_kl_operation_task_assignment` | Pivot linking tasks to employees or teams with priority and acknowledgement metadata.
| `ps_kl_operation_task_note` | Logbook for status transitions, notes and attachments.

The `kloperations` module installs these tables and exposes matching `ObjectModel` classes so future increments can extend the
schema without SQL duplication.

Tasks are generated via a cron job (`hookActionCronJob`) and operations staff can now author manual entries directly from the
**Operations → Tasks** console; future increments may surface shortcuts from the timeline or inquiry board.

## Internal Notifications
- Daily cron runs compile a digest of housekeeping and maintenance workload (HTML + plain text) and email it to the comma/space separated recipients configured in `KLOPERATIONS_DIGEST_RECIPIENTS`.
- Overdue tasks trigger reminder emails when `due_end` has passed and `last_reminded_at` is older than 12 hours so teams are nudged without being spammed.
- Future increments can layer in-app inboxes, push delivery and per-employee quiet hours once subscription tables are introduced.

### Data Model
| Table | Purpose |
| --- | --- |
| `ps_kl_notification_subscription` | Employee preferences for event types and delivery channels. |
| `ps_kl_notification_event` | Canonical record of events emitted by the system (subject, payload, reference IDs). |
| `ps_kl_notification_delivery` | Tracks channel-specific deliveries (email, digest, calendar) and acknowledgement status. |

## ICS/CSV Exports
- Pending tasks for the next N days (default 7) can be exported from **Operations → Tasks** via toolbar buttons that produce CSV spreadsheets and ICS feeds.
- ICS events include per-task timezone data and fall back to the shop timezone when the task does not specify one.
- Future iterations may widen filters (per team/resource kind) and expose authenticated feeds once notification preference tables arrive.

### Implementation Notes
- Build export services that accept filters (resource kinds, programme tags, status) and return standardised DTOs.
- Use ICS generation library (e.g. `eluceo/ical`) and native PHP CSV functions.
- Cache export payloads for quick regeneration but invalidate on booking/inquiry changes.

## Integrations & UI
- Add a **Operations → Tasks** admin screen with Kanban/list toggle, filters by resource and status, and bulk actions.
- Embed housekeeping summary widgets in the timeline view (today/tomorrow tasks, overdue items).
- Allow inquiry board to trigger maintenance tasks directly when issues are reported by guests.

## Module Implementation (Increment 1)
The initial delivery focuses on automated housekeeping scaffolding:

1. **Database scaffolding** – the module installs `kl_operation_run`, `kl_operation_task`, `kl_operation_task_assignment` and
   `kl_operation_task_note` tables with audit fields, status enums and unique keys for deduplication.
2. **Task generator** – `KlOperationTaskGenerator` runs daily via `hookActionCronJob`, creating arrival/pre-arrival housekeeping
   tasks based on `HotelBookingDetail` rows. It respects cancellation/refund flags, deduplicates via `unique_key` hashes and logs
   generator metadata in `kl_operation_run` for auditing.
3. **Lifecycle sync** – booking add/update hooks mark arrival tasks `in_progress` on check-in and complete checkout tasks when
   stays close, keeping the task list aligned with booking statuses.
4. **Admin console** – `AdminKlOperationTasks` lists generated tasks, allows bulk completion, renders payload/notes in a detail view (`views/templates/admin/task_view.tpl`) and exposes CSV/ICS export buttons for downstream scheduling.

Follow-up increments extend the module with richer filters, assignment tooling and lightweight mobile checklists now that maintenance tasks, notifications, exports and manual task creation are in place.

## Deliverables
1. ✅ Database tables and corresponding `ObjectModel` classes for runs, tasks, assignments and notes ship with `kloperations`.
2. ✅ A cron-driven generator produces housekeeping arrival/checkout tasks and records execution metadata.
3. ✅ The back office now exposes an **Operations → Tasks** console with bulk completion and detail views.
4. ✅ Notification dispatcher services send daily digests and overdue reminders (HTML/text) to configurable recipients, throttled by `last_reminded_at`.
5. ✅ Export controllers for CSV/ICS cover pending tasks for configurable ranges via the Operations console toolbar.
6. 🚧 Manual task authoring now lives inside the Operations console; assignment workflows and lightweight mobile/taskboard surfaces remain.

