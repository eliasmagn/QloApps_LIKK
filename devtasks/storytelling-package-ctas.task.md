# Task Brief: Storytelling Package CTA Groups

## Objective
Surface featured packages on storytelling landings as scoped card groups, each with inquiry shortcuts that prefill the package context and resource kind.

## Key Deliverables
- Restructure the storytelling presenter so featured packages are grouped by `resource_kind_scope`, exposing metadata for the group (label, intro, anchor) and CTA payloads per package.
- Generate package-level inquiry URLs (with RFC 3986 query encoding) that include the package code, relevant resource kind and landing-specific UTM parameters.
- Render grouped package cards on residencies, ateliers, gastronomy and programme templates with primary CTA buttons that launch the inquiry form.
- Provide a graceful fallback group for packages without scope metadata so editors can still promote cross-campus bundles.

## Technical Considerations
- Keep presenter caching behaviour intact—package group payloads should be built alongside the existing storytelling data without introducing long-lived caches.
- Reuse `getSectionMetadata()` so group labels/anchors remain consistent with availability and profile sections; fall back to a readable label when a scope is unknown.
- Ensure CTA parameters are compatible with `InquiryController` prefill logic; extend query parsing as needed so package codes select the relevant option.
- Update storytelling styles to support nested package groups while preserving responsiveness and accessibility.

## Dependencies & Coordination
- Confirm with editorial stakeholders that CTA copy aligns with current inquiry messaging and quote workflows.
- Coordinate with operations to verify that inquiry submissions carrying `package_code` land in the expected Kanban swimlanes.

## Acceptance Criteria
- Storytelling landings render package cards grouped by resource kind scope (plus an "all campus" fallback) when featured packages exist.
- Each package card displays its copywriting (name, tagline, description) and a CTA button that opens the inquiry form with package metadata prefilled.
- Packages without scope metadata still appear in a dedicated fallback group on every landing.
- Documentation (`concept.md`, `checklist.md`, `README.md`, `roadmap.md`) highlights the new grouped package CTAs.

## QA Notes
- Smoke-test each storytelling landing with `_KUNSTORT_STORYTELLING_LAUNCH_` enabled to confirm grouping, CTA behaviour and responsive layouts.
- Trigger an inquiry via the CTA URL and verify that package preferences populate on the form and in the stored submission.
