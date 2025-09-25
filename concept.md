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
- When `_KUNSTORT_CORE_MODE_` is set to `inquiry`, the legacy checkout controllers short-circuit to the inquiry landing page (and AJAX calls return friendly errors) instead of exposing cart mechanics, while the full cart-first flow immediately returns once the flag is cleared.
- Inquiry mode now also blocks the cart controller from mutating cart contents so direct requests cannot create ghost carts behind the scenes, and the original checkout templates/controllers remain intact for instant fallback.
- When marketplace access is disabled, admin catalogue and theme pages display offline guidance instead of loading remote iframes.
- The admin booking screen now opens with a tabbed occupancy timeline; the legacy month grid loads lazily only when the calendar tab is selected, and timeline data stays cached while the tab remains active for near-instant toggling.
- Drag-and-drop reallocation is available directly on the occupancy timeline with server-side conflict checks against disabled rooms and capacity limits.
- Resource taxonomy scaffolding now lives in core tables (`kl_resource_profile`, `kl_resource_capacity`, `kl_resource_amenity`, `kl_resource_story`, `kl_resource_history`) with matching `ObjectModel` classes to power upcoming admin forms and APIs.
- Pricing scaffolding now introduces `KLRatePlan`, `KLRatePlanSeason`, `KLPackage`, `KLPackageComponent` and `KLQuote` ObjectModels, and the accompanying `KLQuotePricingEngine` turns those definitions into inquiry-ready quote payloads with seasonal adjustments and optional package components.
- The back office now exposes a **Rate Plans** console so staff can curate plan metadata, scope eligibility, flag approval needs and manage seasonal adjustments without touching the database.
- The back office also includes a **Packages** builder so curated offers can be assembled from lodging, atelier, catering and experience components without editing tables manually.
- The back office exposes a dedicated **Resource Profiles** tab so staff can edit taxonomy metadata and capacity descriptors for rooms, ateliers and gastronomy spaces, assign amenities, review change history and preview how entries surface on the residency showcase.
- Catalog management now includes an **Amenities** screen so the taxonomy team can seed and curate reusable amenity codes, icons and translation domains before wiring them into resource profiles.
- Install and upgrade flows now invoke a CLI-friendly seeder that backfills resource profiles and capacity rows for any legacy room types so taxonomy tables stay aligned with existing inventory.
- Operations automation scaffolding now lives in the `kloperations` module, which seeds run/task tables, generates housekeeping arrival/checkout tasks via cron and provides an Operations → Tasks console for staff updates.
- The front-office header ships as a static residency navigation strip with in-house quick links; cart/account/newsletter/social blocks have been removed from both the theme and core module set so no commerce widgets are expected.
- The front-office home page now surfaces a residency showcase fed by published resource profiles so rooms, ateliers, gastronomy and programme spaces display live metadata instead of mockups.
- Legacy PrestaShop webservice entry points are stubbed; `/webservice` responds with HTTP 410 and no admin UI exposes API keys.
- A repo-level `start_dev.sh` script provisions a Python virtualenv for tooling, keeps Composer dependencies current, and boots the PHP built-in server for local testing.

## Inquiry Workflow Additions
- A dedicated Inquiry entity tracks residency requests independently from carts, including assignee, reminder, and note metadata.
- The back office exposes an Inquiries Kanban board for triage, assignment, reminders and mail notes that can be emailed to guests directly from the board.
- Timeline and inquiry APIs now expose JSON endpoints for UI interactions (reallocation, status changes, availability lookups) without reviving the legacy webservice.
- The front-office inquiry route now renders a structured three-step submission flow that persists directly into the Kanban board, sends guest/staff notifications and hydrates autosuggest inputs via dedicated JSON lookups.

## Near-Term Focus
Detailed task briefs now live in [`devtasks/`](devtasks/) to coordinate execution notes, acceptance criteria and cross-team dependencies. The multi-phase plan remains in [`roadmap.md`](roadmap.md). Immediate priorities concentrate on the first roadmap phases:

1. **Timeline interaction upgrades** *(complete)* – drag-and-drop reallocation on the admin timeline now blocks conflicts against disabled rooms and occupancy limits, and powers the new REST endpoints.
2. **Inquiry workflow foundations** *(complete)* – the dedicated Inquiry model, Kanban board, reminders and mail notes replace cart-driven scaffolding in the back office; notes can optionally email guests for a documented audit trail.
3. **Resource taxonomy groundwork** *(complete)* – model `resource_kind`, capacity descriptors and amenities on rooms, ateliers and gastronomy spaces to unlock richer storytelling and reporting. The Resource Profiles tab now includes inline capacity validation, amenity assignment, change history snapshots and a front-office preview. Detailed deliverables and data models live in [`docs/blueprints/resource-taxonomy.md`](docs/blueprints/resource-taxonomy.md).
4. **Frontend storytelling** – refactor offer pages to present curated narratives, availability cues and inquiry entry points instead of commodity pricing widgets. Copy strategy, media pairing and availability cues will be coordinated with the taxonomy work so the front office can reuse the same metadata without bespoke duplication.
5. **Rate plans & packages** – layer configurable plans (BAR, corporate, residency programmes) and bundled offers for ateliers/catering as described in [`docs/blueprints/rate-plans-packages.md`](docs/blueprints/rate-plans-packages.md) to keep quoting grounded in a consistent rule set. Base database tables and models now ship with the module so upcoming admin UIs and pricing services can hook into them.
6. **Operations automation** – the first increment ships with automated housekeeping task generation and an admin console (`kloperations` module); upcoming work extends notifications, maintenance task types and exports per [`docs/blueprints/operations-automation.md`](docs/blueprints/operations-automation.md).

These additions extend the original near-term plan without changing the underlying principle: resource clarity first, then progressive automation that remains transparent to staff.

## Longer-Term Ideas
- Replace leftover commerce terminology in the database schema and UI strings.
- Introduce CSV/ICS export for residency and seminar planning.
- Build bridges to Kunstort's public programme website for event publishing.
