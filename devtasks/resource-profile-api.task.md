# Task Brief: Resource Profile API

## Objective
Expose authenticated JSON endpoints that deliver resource profile listings and detail payloads, including capacity metrics, amenity catalogues, storytelling copy and availability signals for internal tooling.

## Key Deliverables
- Add a front-office controller that responds with JSON for `/resourceprofileapi` list and detail actions, gated behind a shared bearer token.
- Reuse the storytelling presenter to return published profiles enriched with amenity assignments, capacity rows, storytelling stories and next-availability snippets.
- Provide a filtered availability snapshot per request so internal services can hydrate UI widgets without duplicating presenter logic.
- Return meaningful HTTP status codes and machine-friendly error payloads for authentication failures, missing resources and misconfiguration.

## Technical Considerations
- Parse `resource_kind` filters (single or comma-separated) and `id_lang`/`id_shop` overrides to support multi-shop, multi-language use cases.
- Ensure responses disable caching, emit ISO 8601 timestamps and normalise keys/structures to align with other storytelling APIs.
- Pull the authentication token from `_KUNSTORT_RESOURCE_API_TOKEN_` with support for `Authorization: Bearer` headers and query string fallbacks for scheduled jobs.
- Keep the controller framework-light by exiting early when the storytelling module is disabled or misconfigured.

## Dependencies & Coordination
- Coordinate with infrastructure on secure distribution of the shared token and rotation procedures.
- Confirm resource taxonomy data (profiles, capacities, amenities, stories) is populated before exposing the API to stakeholders.
- Align with BI/ops teams on response fields required for downstream dashboards and scripts.

## Acceptance Criteria
- `index.php?controller=resourceprofileapi` returns a `200` JSON payload containing `profiles`, `availability`, and `resource_kinds`, all authenticated requests only.
- `index.php?controller=resourceprofileapi&action=detail&resource_code=...` returns a single profile with `story`, `amenities`, `capacity`, and `next_availability` data; unknown codes return `404`.
- Requests without valid tokens return `401/403` JSON errors; missing token configuration responds with `503` and instructs operators to set the constant.
- Repository docs (`README.md`, `concept.md`, `roadmap.md`, `checklist.md`) and developer guides document the new endpoints and authentication flow.

## QA Notes
- Hit list/detail endpoints with valid and invalid tokens to confirm HTTP status handling.
- Validate responses for multiple resource kinds and languages, ensuring amenities/capacities match database state.
- Smoke-test downstream consumers (e.g., BI scripts) using the documented authentication method.
