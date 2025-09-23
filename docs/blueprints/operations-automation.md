# Operations Automation Blueprint

This blueprint describes the automation layer for housekeeping, maintenance, staff notifications and data exports.

## Housekeeping & Maintenance Tasks
- Generate arrival/departure task lists per day based on confirmed bookings and residency timelines.
- Classify tasks by resource kind (room turnarounds, atelier resets, gastronomy prep) and assign to staff teams.
- Track statuses (`pending`, `in_progress`, `clean`, `needs_repair`) with timestamped employee updates.
- Capture maintenance notes, photos and follow-up reminders for tasks flagged as `needs_repair`.
- Provide printable and mobile-friendly checklists with offline caching.

### Data Model Additions
| Table | Purpose |
| --- | --- |
| `ps_kl_task` | Master task table storing resource reference, type (`housekeeping`, `maintenance`), status, due window. |
| `ps_kl_task_assignment` | Pivot linking tasks to employees or teams with priority and acknowledgement metadata. |
| `ps_kl_task_note` | Logbook for status transitions, notes and attachments. |

Tasks are generated via a cron job and can also be created manually from the resource timeline or inquiry board.

## Internal Notifications
- Subscribe employees or teams to events (new inquiry, timeline move, overdue task, quote approval).
- Deliver notifications via in-app inbox, daily digest email and optional ICS feed subscription.
- Provide quiet hours and channel preferences per employee.

### Data Model
| Table | Purpose |
| --- | --- |
| `ps_kl_notification_subscription` | Employee preferences for event types and delivery channels. |
| `ps_kl_notification_event` | Canonical record of events emitted by the system (subject, payload, reference IDs). |
| `ps_kl_notification_delivery` | Tracks channel-specific deliveries (email, digest, calendar) and acknowledgement status. |

## ICS/CSV Exports
- Provide filtered exports for residency calendars, atelier schedules and event planning.
- Support weekly digest ICS feed for Google/Outlook and ad-hoc CSV downloads with timezone-corrected timestamps.
- Ensure exports respect permissions (e.g. residency team vs gastronomy team data scopes).

### Implementation Notes
- Build export services that accept filters (resource kinds, programme tags, status) and return standardised DTOs.
- Use ICS generation library (e.g. `eluceo/ical`) and native PHP CSV functions.
- Cache export payloads for quick regeneration but invalidate on booking/inquiry changes.

## Integrations & UI
- Add a **Operations → Tasks** admin screen with Kanban/list toggle, filters by resource and status, and bulk actions.
- Embed housekeeping summary widgets in the timeline view (today/tomorrow tasks, overdue items).
- Allow inquiry board to trigger maintenance tasks directly when issues are reported by guests.

## Deliverables
1. Database tables and corresponding `ObjectModel` classes for tasks, notes, notifications and deliveries.
2. Cron command that generates daily housekeeping/maintenance tasks from arrival/departure data.
3. Admin UI for managing tasks, including quick status updates and printable checklists.
4. Notification dispatcher services (in-app, email, ICS feed generation) with preference management.
5. Export controllers for ICS/CSV plus automated tests covering generation and permissions.

