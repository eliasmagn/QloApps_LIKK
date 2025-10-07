# Task Brief: Operations Notification Preferences

## Objective
Introduce per-employee notification subscriptions so operations emails respect quiet hours, channel opt-ins and future delivery vectors while centralising configuration inside the back office.

## Key Deliverables
- Model canonical notification entities (`kl_notification_subscription`, `kl_notification_event`, `kl_notification_delivery`) with audit fields and migration coverage.
- Extend the `KlOperationNotificationService` so daily digests and overdue reminders fan out through subscription-aware dispatch, defer during quiet hours and log deliveries for follow-up attempts.
- Update mail templates to reference the self-service preferences screen and ensure queued deliveries can resume once quiet hours lapse.
- Provide an **Operations → Notification Preferences** admin screen for creating/updating employee subscriptions, including channel toggles, quiet hour windows and timezone selection.

## Progress Update *(May 2025)*
- ✅ Implemented database tables and ObjectModels for subscriptions, events and deliveries with upgrade/install scripts to backfill existing installations.
- ✅ Reworked notification dispatch to query employee opt-ins, record events/deliveries, defer during quiet hours and process queued sends when cron runs outside those windows.
- ✅ Daily digest and overdue reminder templates now include manage-preferences messaging aligned with the new back-office surface.
- ✅ Added an admin controller allowing operations coordinators to assign subscriptions, enforce HH:MM quiet hours and surface delivery channel toggles.
- 🚧 Future work: expose delivery history in the UI and allow employees to self-serve from the front-office portal.

## Technical Considerations
- Ensure queued deliveries capture the template payload so retries remain deterministic when quiet hours expire.
- Guard against duplicate subscriptions per employee/event pair through unique indexes and controller validation.
- Keep upgrade paths idempotent—`installDatabase()` is reused by the upgrader to install tables and indexes for existing deployments.

## Acceptance Criteria
- Operators can create or edit subscriptions for any active employee and configure quiet hours per timezone.
- Cron-driven digests respect quiet hours (queued deliveries resend later) and channel opt-ins (no emails sent to disabled channels).
- Delivery attempts are recorded in `kl_notification_delivery` with `sent`, `queued`, `failed` or `cancelled` statuses for audit purposes.

## Risks & Mitigations
- **Risk:** Quiet hour windows misconfigured (e.g., start/end swapped) could block email entirely. **Mitigation:** Controller enforces HH:MM format and requires both fields or neither; service treats identical start/end as “no quiet hours”.
- **Risk:** Legacy config recipients lose notifications. **Mitigation:** Dispatcher still respects `KLOPERATIONS_DIGEST_RECIPIENTS` as a fallback and records those sends under a dedicated channel key.
