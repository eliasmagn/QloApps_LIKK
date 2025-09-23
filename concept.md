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
- Legacy offline payment modules (bank wire, cheque) and the PayPal Commerce gateway have been removed to keep the stack focused on inquiry-driven fulfilment.
- When `_KUNSTORT_CORE_MODE_` is set to `inquiry`, the legacy checkout controllers and templates short-circuit to the inquiry landing page instead of exposing cart mechanics.
- When marketplace access is disabled, admin catalogue and theme pages display offline guidance instead of loading remote iframes.
- The admin booking screen now opens with a tabbed occupancy timeline; the legacy month grid loads lazily only when the calendar tab is selected, and timeline data stays cached while the tab remains active for near-instant toggling.
- The front-office header ships as a static residency navigation strip with in-house quick links; cart/account/newsletter/social blocks have been removed from both the theme and core module set so no commerce widgets are expected.
- Legacy PrestaShop webservice entry points are stubbed; `/webservice` responds with HTTP 410 and no admin UI exposes API keys.

## Near-Term Focus
The detailed multi-phase plan lives in [`roadmap.md`](roadmap.md). Immediate priorities concentrate on the first roadmap phases:

1. **Timeline interaction upgrades** *(in progress)* – finish the drag-and-drop reallocation tools and collision checks for the admin timeline, then surface read-only availability to the public site.
2. **Inquiry workflow foundations** – carve out a dedicated Inquiry model, Kanban board and assignment flow so booking management no longer depends on carts.
3. **Resource taxonomy groundwork** – model `resource_kind`, capacity descriptors and amenities on rooms, ateliers and gastronomy spaces to unlock richer storytelling and reporting.
4. **Frontend storytelling** – refactor offer pages to present curated narratives, availability cues and inquiry entry points instead of commodity pricing widgets.

## Longer-Term Ideas
- Replace leftover commerce terminology in the database schema and UI strings.
- Introduce CSV/ICS export for residency and seminar planning.
- Build bridges to Kunstort's public programme website for event publishing.
