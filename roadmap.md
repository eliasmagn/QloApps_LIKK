# Kunstort Lehnin Roadmap

This roadmap tracks how the Kunstort Lehnin fork evolves from a de-bloated QloApps baseline into a residency-first operations suite. Each phase groups together coherent deliverables so we can prioritise work and spot dependencies early. Status markers: ✅ complete, 🚧 in progress, ⏳ planned.

## Phase 0 – Commerce Debloating (✅ Complete)
- ✅ **Checkout short-circuit** – when inquiry mode is enabled, `order`/`order-opc` controllers redirect to the inquiry landing (AJAX calls receive friendly JSON errors) while the restored legacy controllers and templates ensure clearing the flag immediately revives the cart workflow without code edits.
- ✅ **Cart hardening** – inquiry mode also blocks cart mutations so direct POSTs cannot create ghost carts while the distribution runs inquiry-first.
- ✅ **Payment modules removed** – legacy `bankwire`, `cheque` and `qlopaypalcommerce` modules (and overrides) are no longer shipped so no unused PCI-facing code stays in the tree.
- ✅ **Front-office widgets stripped** – header and column slots now render a static residency navigation without cart, account, newsletter or social hooks.
- ✅ **Webservice disabled** – `/webservice` responds with HTTP 410 and admin tooling for API keys has been excised to reduce attack surface.

## Phase 1 – Inquiry Workflow Maturity (🚧 In progress)
- ✅ **Timeline interaction upgrades** – add drag-and-drop reallocation with conflict detection against room disables and capacity rules.
- ✅ **Inquiry entity & board** – replace cart-derived booking scaffolding with a dedicated Inquiry model, Kanban board UI and assignment workflow, including reminders and guest-facing mail notes from the board.
- ✅ **API endpoints** – expose REST endpoints for timeline updates, inquiry status changes and availability lookups to support the new UI components.
- ✅ **Dev bootstrap helper** – ship `start_dev.sh` to keep Composer dependencies fresh, prep Python tooling via `.venv`, and launch the PHP dev server for quick smoke testing.
- ✅ **Inquiry submission pipeline** – the inquiry landing now presents a three-step form that validates guest details, stores structured payloads on the `HotelInquiry` model, emails confirmations/alerts and exposes JSON lookups for resource and package suggestions (see [`devtasks/inquiry-submission.task.md`](devtasks/inquiry-submission.task.md)).

## Phase 2 – Resource & Pricing Model (🚧 In progress)
- ✅ **Resource taxonomy** – add `resource_kind`, capacity descriptors and amenities metadata to rooms, ateliers, seminar spaces and gastronomy units. The module now ships database tables, ObjectModels, an amenity catalogue and a back-office **Resource Profiles** tab complete with amenity assignment, inline capacity guardrails, change history summaries and residency showcase previews (see [`docs/blueprints/resource-taxonomy.md`](docs/blueprints/resource-taxonomy.md)). Install/upgrade flows (plus a CLI helper) backfill profiles and capacities for existing room types.
- ✅ **Rate plans & packages** – introduce configurable price plans (BAR, corporate, residency programmes) plus bundled packages for weekly ateliers, catering-inclusive stays, etc., as laid out in [`docs/blueprints/rate-plans-packages.md`](docs/blueprints/rate-plans-packages.md). Base database tables (`kl_rate_plan*`, `kl_package*`, `kl_quote`) and ObjectModel scaffolding now ship with the module to unblock admin UIs and pricing services. The back office exposes both **Rate Plans** and **Packages** tabs so staff can manage plan metadata, eligibility scopes, seasonal adjustments and assemble bundled offers via the new component builder, and the `KLQuotePricingEngine` now generates inquiry-ready quote payloads with seasonal adjustments and package components.
- 🚧 **Frontend storytelling** – the home page now surfaces published resource profiles with capacity cues; the next milestone kicks off full storytelling templates per resource type. A new blueprint ([`docs/blueprints/front-office-storytelling.md`](docs/blueprints/front-office-storytelling.md)) maps the presenter/services layer, CMS slots and rollout plan while implementation tasks stay tracked in [`devtasks/front-office-storytelling.task.md`](devtasks/front-office-storytelling.task.md).
  - ✅ Scaffolding complete: `_KUNSTORT_STORYTELLING_LAUNCH_` gates a new `ResidenciesController`, `HotelReservationSystemStorytellingPresenter` aggregates taxonomy/packages/CMS data and `themes/hotel-reservation-theme/storytelling/residencies.tpl` renders placeholder sections ready for editorial content.
  - ✅ Residency availability snapshot now pulls live booking/maintenance data, caches results for 15 minutes and renders per-kind openings on the storytelling landing.
  - ✅ Storytelling availability caches are now invalidated through `KLStoryAvailabilityCache` whenever bookings, inquiries or room disable ranges change so fresh snapshots regenerate immediately after operations automation runs.
  - ✅ Ateliers storytelling landing mirrors the presenter, filters sections to studio resources, exposes dedicated CMS slots and updates navigation links under the same feature flag.
  - ✅ Gastronomy storytelling landing extends the presenter with amenity callouts, CMS slots and gastronomy-scoped availability/packages while navigation, quick links and the sitemap point to `/index.php?controller=gastronomy` when the flag is active.
  - ✅ Programme storytelling landing layers grouped availability cues, CMS-managed highlights/schedule/inquiry slots and feature-flagged navigation/sitemap links for `/index.php?controller=programme`.
  - ✅ Storytelling style layer ships with a shared SCSS/CSS bundle, inline critical hero rules and a `klStorytellingDefer` helper so optional scripts load after first paint.
  - ✅ Storytelling hero media pipeline generates WebP/JPEG variants via `npm run build:hero-media`, exposes taxonomy alt text in the presenter payload and renders lazy-loaded `<picture>` components with accessible captions across all storytelling templates.
  - ✅ Storytelling availability slots now surface CTA buttons wired to slot-specific inquiry URLs so arrival/departure dates, resource kind and resource codes prefill the inquiry flow.
  - ✅ Featured packages now display as scope-aware groups with inquiry CTA buttons and a campus-wide fallback when scope metadata is missing.
  - ✅ Storytelling package cards now surface cached pricing highlights generated from canonical sample stays via `KLQuotePricingEngine::generateQuote()`.
  - ✅ Storytelling testimonials and FAQ slots are available through cached JSON actions on the inquiry lookup controller, now returning explicit `resource` and ordered `resource_groups` metadata alongside ISO `generated_at` stamps, with HTTPS enforcement, throttle protection and front-end hydration handled by the normalised lookups in `storytelling-content.js`.
  - ✅ Storytelling presenter payloads are now covered by PHPUnit tests while Panther smoke tests verify residencies template rendering and Lighthouse navigation timing targets.

## Phase 3 – Operations Automation (🚧 In progress)
- ✅ **Housekeeping automation** – the `kloperations` module now generates arrival/checkout housekeeping tasks via cron, syncs booking lifecycle statuses and exposes an Operations → Tasks console. Architectural notes live in [`docs/blueprints/operations-automation.md`](docs/blueprints/operations-automation.md) with implementation details tracked in [`devtasks/operations-automation.task.md`](devtasks/operations-automation.task.md).
- ✅ **Notification preferences milestone** – room disable ranges still raise maintenance start/release tasks while subscription-aware daily digests and overdue reminders honour quiet hours, channel opt-ins and queued retries via the new `kl_notification_*` tables. Operations staff can manage employee subscriptions from the **Operations → Notification Preferences** console and legacy configuration recipients remain as a fallback.
- ✅ **ICS/CSV exports** – the Operations console can now export pending tasks for the upcoming week in CSV and ICS formats for downstream scheduling.
- ✅ **Assignments & mobile checklists** – the Operations console now pairs manual task authoring with employee/team assignments, acknowledgement tracking, CSV/ICS exports enriched with assignee summaries and a mobile-friendly view so housekeeping devices can claim and complete work on the go. Team presets can be stored in `KLOPERATIONS_TEAMS`.
- ✅ **Timeline summary widget** – the admin booking timeline now embeds an operations summary card that aggregates pending and in-progress tasks into overdue, today and tomorrow buckets per resource kind and links directly to filtered views inside the Operations console.
- ✅ **Inquiry → Operations bridge** – the inquiry Kanban inspector lists linked follow-ups, raises new `KlOperationTask` entries with inquiry context, lets staff convert note submissions into follow-ups on the fly and exposes deep links back to the originating inquiry inside both the Operations console view and list.

## Phase 4 – Reporting & Integrations (⏳ Planned)
- ⏳ **Utilisation dashboards** – surface occupancy, length-of-stay, residency programme metrics with filters by resource type.
- ⏳ **Programme publishing bridge** – sync curated events and residencies to the public Kunstort Lehnin website or other storytelling channels.
- ⏳ **External API strategy** – design modern, scoped APIs (authenticated modules, webhook patterns) to replace the retired PrestaShop webservice when needed.

## Maintenance backlog (✅ Recently resolved)
- ✅ Composer now advertises the PHP 8.1–8.4 window documented in our requirements.
- ✅ The inquiry quote preview AJAX payload matches `KLQuotePricingEngine::generateQuote()` and keeps the submitted occupancy counts intact.
- ✅ `.venv/` is ignored so `start_dev.sh` no longer leaves the repository in a dirty state.

We iterate through the roadmap top-to-bottom: hardening the base, completing inquiry workflows, enriching resource data, automating internal operations, and finally exposing analytics and integrations once the core experience is stable.
