# Task Brief: Housekeeping & Maintenance Automation

## Objective
Bootstrap operational tooling that generates housekeeping, maintenance and export tasks from booking and inquiry data so staff can manage daily workloads without spreadsheets.

## Key Deliverables
- Define task generation rules that translate arrivals/departures and resource attributes into housekeeping checklists and maintenance jobs.
- Build an operations dashboard showing upcoming tasks, status filters and quick actions (complete, reassign, escalate).
- Implement notification channels (email, optional push later) triggered by overdue tasks or critical maintenance flags.
- Produce CSV/ICS export routines for sharing schedules with external partners.

## Progress Update *(May 2025)*
- ✅ Bootstrapped the `kloperations` module with task/run tables, ObjectModels and a cron-driven generator that creates arrival & checkout housekeeping tasks from `HotelBookingDetail` data while avoiding duplicates.
- ✅ Added lifecycle hooks to keep tasks aligned with booking status changes (arrival tasks flip to `in_progress` on check-in, checkout tasks mark as completed when stays close).
- ✅ Delivered an **Operations → Tasks** admin console with listings, bulk completion and a detail view exposing payload/notes.
- ✅ Layered maintenance start/release tasks derived from room disable ranges plus digest/reminder emails (HTML/text) routed to `KLOPERATIONS_DIGEST_RECIPIENTS`, throttled by `last_reminded_at`.
- ✅ Added CSV and ICS export buttons (default 7-day window) to the operations console with timezone-aware events.
- ✅ Added manual task authoring inside the Operations console with JSON payload capture and optional kickoff notes so teams can log ad-hoc work alongside automated checklists.
- 🚧 Next focus: assignment workflows and lightweight mobile views once staff validate the new automations.

## Technical Considerations
- Extend existing inquiry and booking observers to emit domain events that task generators consume.
- Model operations entities in new tables (`kl_operation_task`, `kl_operation_run`, etc.) with audit fields and status enums.
- Keep the system configurable: allow per-resource or per-segment rules to be toggled and tuned from the back office.
- Ensure exports respect timezone settings and include iCalendar metadata suitable for import into Outlook/Google Calendar.

## Cross-Team Dependencies
- Collaborate with facilities/housekeeping leads to map cleaning standards, maintenance SLA expectations and escalation paths.
- Coordinate with IT for email infrastructure and any push notification gateway required later.

## Acceptance Criteria
- Task generation runs automatically on booking/inquiry changes and can be re-triggered manually without duplicating tasks.
- Staff can update task statuses with audit trails capturing who made the change and when.
- Export files validate successfully (CSV passes schema checks, ICS imports cleanly into Google Calendar).
- Documentation updates land in `docs/blueprints/operations-automation.md` detailing the automation rules and UI workflows.

## Risks & Mitigations
- **Risk:** Overly aggressive automation might spam staff. **Mitigation:** Include throttling controls and digest options.
- **Risk:** Timezone mismatches could create schedule errors. **Mitigation:** Centralise timezone handling via a helper service and add unit tests for edge cases.
