# Task Brief: Inquiry to Operations Bridge

## Objective
Connect the inquiry triage board to the Operations module so staff can raise and track housekeeping/maintenance follow-ups directly from inquiry context, with reciprocal links back into the originating inquiry card.

## Key Deliverables
- Extend the inquiry Kanban sidebar with inspector controls that surface requester context, existing operations tasks and one-click follow-up buttons for housekeeping and maintenance.
- Implement an AJAX endpoint that persists manual `KlOperationTask` rows for an inquiry (including payload, schedule, priority, optional resource hints) and optionally logs a note to the inquiry timeline.
- Allow the note dialog to optionally spin up an operations follow-up in the same submission so frontline staff can react without retyping context.
- Ensure operations task creation emits context metadata (`context_type = inquiry`) and expose a quick link in the Operations console detail view to jump back to the originating inquiry.
- Return operations task summaries alongside inquiry detail responses so the UI can render current follow-ups without additional requests.

## Technical Considerations
- Guard the bridge behind a `Module::isEnabled('kloperations')` check; the sidebar should gracefully hide follow-up controls when the Operations module is disabled.
- Reuse PrestaShop link builders when generating admin URLs so tokens and routing stay consistent (`AdminKlOperationTasks` view for task detail, `AdminHotelInquiries` with a `focus_inquiry` query param for deep links).
- Normalise datetime inputs coming from `datetime-local` fields before persisting to the database and fall back to the shop timezone for `KlOperationTask` rows.
- Prefer plain-text notes for inquiry logging to avoid XSS and keep the existing note history renderer unchanged.

## Acceptance Criteria
- Selecting an inquiry opens a sidebar inspector with requester info, reminder status and an “Operations follow-ups” block that lists linked tasks (including status, schedule and a view link) when the Operations module is active.
- Submitting the follow-up form creates a `KlOperationTask` with `context_type = inquiry`, returns the new task metadata in the JSON response and logs a note against the inquiry when requested.
- Saving a note with the “create follow-up” toggle spins up the requested operations task, reuses the note body for payload context and refreshes the sidebar list without leaving the modal.
- Viewing a task inside **Operations → Tasks** shows a clickable link back to the inquiry when context data is available.
- Documentation updates capture the inquiry → operations workflow in the project concept, README, roadmap and checklist summaries.

## Risks & Mitigations
- **Risk:** Duplicate follow-ups created from repeated clicks. **Mitigation:** Generate a unique task key per submission and rely on server responses to refresh the sidebar immediately after creation.
- **Risk:** Operations module disabled or missing. **Mitigation:** Feature-detect availability before rendering controls; display a friendly notice or hide the section entirely when the bridge cannot be used.
