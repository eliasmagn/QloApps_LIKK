# Task Brief: Storytelling Availability CTA Enhancements

## Objective
Layer actionable inquiry calls-to-action into the storytelling availability widgets so visitors can jump straight into an inquiry pre-filled with the slot they are viewing.

## Key Deliverables
- Extend the storytelling presenter so each availability slot includes structured inquiry query parameters for the highlighted resource, arrival and departure dates.
- Generate slot-specific inquiry URLs on the landing payloads while preserving existing UTM tracking per page.
- Render compact CTA buttons next to every availability slot (including grouped variants) across residencies, ateliers, gastronomy and programme templates.
- Prefill the inquiry form with arrival/departure dates, resource kind and the surfaced resource code whenever those query parameters are present.

## Technical Considerations
- The presenter already caches availability snapshots; attach CTA data without mutating the cached payload stored under the existing cache keys.
- Use RFC 3986 query encoding when composing CTA URLs so resource codes with special characters survive the redirect.
- Keep the storytelling styles responsive—buttons should stack beneath the slot summary on small screens and align to the right on wider breakpoints.
- Ensure the new query parameters do not break validation in `InquiryController`; only accept values that pass the existing date and resource kind checks.

## Dependencies & Coordination
- Confirm with the design/content team that CTA copy matches current inquiry messaging.
- Coordinate with operations to make sure prefilled resource notes align with the identifiers they expect during triage.

## Acceptance Criteria
- CTA buttons appear alongside every availability slot on all storytelling landings when `_KUNSTORT_STORYTELLING_LAUNCH_` is enabled.
- Clicking a CTA opens the inquiry page with arrival/departure inputs filled, the relevant resource kind pre-selected and the surfaced resource code noted.
- The inquiry form still validates correctly when loaded without CTA parameters.
- Documentation (`concept.md`, `checklist.md`, `README.md`, `roadmap.md`) reflects the CTA enhancement.

## QA Notes
- Smoke-test each storytelling landing to confirm CTA visibility and responsive layout behaviour.
- Submit a test inquiry from a CTA URL and verify the payload logged on the Kanban board includes the prefilled values.
