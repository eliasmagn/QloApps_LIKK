# Inquiry Quote Review Workflow

## Overview
Establish a guided review flow for persisted `KLQuote` records inside the inquiry Kanban so coordinators can evaluate pricing pr
oposals, approve or decline them with an audit trail, and trigger guest-facing deliveries without leaving the inspector sidebar.
The effort builds on the existing quote persistence/PDF export work and focuses on surfacing quote context, state transitions an
d delivery tooling alongside inquiry details.

## Goals
- Load stored quotes whenever an inquiry is inspected and surface them as structured cards inside the sidebar.
- Provide approve/decline actions that mutate the `KLQuote` status, log an audit note on the inquiry and refresh the visible quo
tes instantly.
- Keep PDF download/email buttons in place with permission-aware visibility so staff can respond to guests immediately after re
viewing a quote.
- Ensure the UI communicates quote validity windows, totals, author attribution and current state so coordinators understand con
text before acting.
- Update project documentation to capture the new review workflow and operator expectations.

## Key Tasks
1. Extend `AdminHotelInquiriesController` to load quote summaries alongside inquiry payloads, expose approval/decline endpoints
 that enforce permissions, update statuses and log audit notes.
2. Build Smarty partials plus JavaScript helpers that render quote cards, surface status badges, wire approve/decline/download/e
mail buttons and keep the sidebar list in sync with server responses.
3. Refresh the inquiry board styles if necessary so stacked quote cards and action buttons remain legible on narrow viewports.
4. Document the workflow in `concept.md`, `checklist.md`, `README.md`, `roadmap.md` and link this task from `devtasks/tasks.md`.

## References
- `modules/hotelreservationsystem/controllers/admin/AdminHotelInquiriesController.php`
- `modules/hotelreservationsystem/views/templates/admin/inquiries/`
- `modules/hotelreservationsystem/views/js/admin/inquiries_board.js`
- `modules/hotelreservationsystem/views/css/admin/inquiries_board.css`
- `modules/hotelreservationsystem/classes/KLQuote.php`
