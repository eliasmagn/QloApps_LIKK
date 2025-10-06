# Task Brief: Storytelling Content API

## Objective
Expose testimonials and FAQ CMS slots through a cached JSON interface so storytelling landings and external consumers can reuse editorial content without coupling to PrestaShop tables.

## Key Deliverables
- Extend the inquiry lookup module front controller with `testimonials` and `faq` actions that map CMS configuration keys per storytelling resource group.
- Guard the new actions with HTTPS enforcement, active-module checks and lightweight throttling to align with other JSON endpoints.
- Cache CMS payloads per language/shop to avoid repeated database work while keeping responses fresh for editors.
- Surface helper methods on `HotelReservationSystemStorytellingPresenter` to resolve CMS slots and generate module links for the new JSON actions.
- Update storytelling templates to expose `data-kl-storytelling-*` attributes and hydrate testimonial/FAQ sections via a dedicated JavaScript helper that consumes the JSON payloads after initial render.

## Technical Considerations
- Reuse the existing CMS configuration keys defined in the presenter so resource-to-slot mappings stay centralised.
- Use the core `Cache` facade with TTL metadata so payloads can expire gracefully when editors publish new copy.
- Ensure throttling keys incorporate the requester address and action name to keep the guard simple but effective.
- Keep server-rendered CMS copy in the templates as a graceful fallback if JSON fetches fail or a slot is unpublished.

## Dependencies & Coordination
- Confirm with the editorial team that CMS content for testimonials/FAQ slots is populated before exposing the JSON API publicly.
- Coordinate with front-end consumers to document response shapes and authentication expectations (same-origin cookies, HTTPS).

## Acceptance Criteria
- Hitting `index.php?fc=module&module=hotelreservationsystem&controller=inquirylookup&action=testimonials` returns CMS payloads keyed by storytelling resource type (null when no slot is defined) with `generated_at` metadata.
- Requests over HTTP, to disabled modules or exceeding the throttle window return 403/429 responses rather than cached payloads.
- Storytelling templates decorate testimonial and FAQ containers with `data-kl-storytelling-slot` attributes and hydrate via `storytelling-content.js` using the new endpoints.
- Repository documentation (`README.md`, `concept.md`, `roadmap.md`, `checklist.md`) references the JSON actions and front-end hydration strategy.

## QA Notes
- Validate testimonial/FAQ JSON responses for residencies, ateliers, gastronomy and programme landings in multiple languages.
- Confirm repeated calls from the same IP respect the throttle window and that cached responses update after CMS content changes.
