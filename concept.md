# Kunstort Lehnin Hotel Management Concept

## Vision
Create a lean, fully self-hosted hospitality operations tool tailored to Kunstort Lehnin. The software prioritizes clarity of resource availability (Zimmer, Ateliers, Programmflächen) and enquiry-driven workflows over e-commerce features. It keeps the existing QloApps data model as a base while progressively reshaping it into a focused residency and guest management system.

## Guiding Principles
- **No third-party marketplaces**: disable all integrations with paid module stores and focus on in-repo extensibility.
- **Inquiry-first operations**: pivot away from shopping carts to HS/3-style booking timelines and manual confirmation flows.
- **Modular but transparent**: retain the ability to build custom modules from source without any proprietary dependencies.
- **Campus-wide planning**: a unified calendar must represent rooms, studios, seminar spaces and events side-by-side.

## Current Architecture Notes
- The `hotelreservationsystem` module remains the functional core (ObjectModels for rooms, bookings, pricing).
- PrestaShop marketplace hooks have been disabled globally via `_QLOAPP_DISABLE_MARKETPLACE_`.
- Admin module catalogue interactions are short-circuited to keep the back office free from external promotions.
- `config/defines_custom.inc.php` houses feature flags for the Kunstort distribution (e.g. `_KUNSTORT_CORE_MODE_ = 'inquiry'`).
- When marketplace access is disabled, admin catalogue and theme pages display offline guidance instead of loading remote iframes.
- The admin booking screen now opens with a tabbed occupancy timeline; the legacy month grid loads lazily only when the calendar tab is selected, and timeline data stays cached while the tab remains active for near-instant toggling.

## Near-Term Roadmap
1. **Calendar refactor** *(ongoing)*: extend the admin booking view into a resource timeline (baseline timeline shipped; drag-and-drop management still pending); expose it read-only in front office.
2. **Inquiry workflow**: new controller & UI to log stay requests, decoupled from the PrestaShop cart.
3. **Resource taxonomy**: introduce `resource_kind` and related metadata to rooms to cover Außenzimmer, Ateliers, Café etc.
4. **Frontend narrative**: replace price-driven templates with curated descriptions and availability cues.

## Longer-Term Ideas
- Replace leftover commerce terminology in the database schema and UI strings.
- Introduce CSV/ICS export for residency and seminar planning.
- Build bridges to Kunstort's public programme website for event publishing.
