# Task Brief: Storytelling Pricing Highlights

## Objective
Surface rate guidance on storytelling package cards by querying the pricing engine with canonical sample stays, then expose the resulting starting rate and inclusions alongside existing CTA copy.

## Key Deliverables
- Presenter helpers that assemble canonical sample stay requests per featured package and call `KLQuotePricingEngine::generateQuote()` to retrieve pricing summaries.
- Package DTO updates that cache highlight payloads per language/shop so heavy pricing requests are reused during the request lifecycle.
- Storytelling templates (residencies, ateliers, gastronomy, programme) updated to render starting-rate headlines, sample stay context and inclusion summaries with graceful fallbacks when pricing is unavailable.
- A lightweight CSS block to style the highlight panel within existing storytelling cards.

## Technical Considerations
- Canonical stay logic should consider package duration metadata and linked rate plans when picking the sample check-in/check-out window; fall back to sensible defaults if data is missing.
- Cache highlight payloads via the presenter (and PrestaShop's cache layer) with an expiry so repeated page loads do not spam the pricing engine.
- Handle missing rate plans, unpublished resource profiles or quote errors gracefully—front-end output should default to "coming soon" messaging instead of surfacing exceptions.
- Reuse translation helpers so highlight strings are localisation-ready and match the storytelling domain namespace.

## Dependencies & Coordination
- Coordinate with the pricing team to validate the canonical stay definitions per package type.
- Confirm with editorial that inclusion labels align with component terminology already used in inquiry communications.

## Acceptance Criteria
- Featured packages on all storytelling landings display a starting rate headline, sample stay description and inclusion summary when rate plan/package data is complete.
- Packages missing prerequisite data display a translated fallback message instead of leaving empty space.
- Highlight data is cached per package/lang/shop combination and expires automatically to avoid stale pricing.
- Documentation (`concept.md`, `checklist.md`, `README.md`, `roadmap.md`) reflects the new storytelling pricing highlights capability.

## QA Notes
- Smoke-test each storytelling landing with `_KUNSTORT_STORYTELLING_LAUNCH_` enabled to verify highlight rendering, currency formatting and fallback messaging.
- Temporarily disable a package's linked rate plan to confirm the templates show the fallback message instead of crashing.
- Monitor logs for pricing-engine exceptions while browsing the storytelling pages to ensure caching prevents repeated failures.
