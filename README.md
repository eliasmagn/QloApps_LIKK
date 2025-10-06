# Kunstort Lehnin Hotel Management (QloApps Fork)

## Overview
This repository is a lean fork of QloApps that is being transformed into a residency- and hotel-management tool for [Kunstort Lehnin](https://kunstortlehnin.de). The objective is to keep the reliable billing and room data models from QloApps while removing all marketplace cruft and re-centering the product on an HS/3-style booking calendar and enquiry-driven workflows.

Key characteristics of the fork:
- 🚫 **Marketplace free** – outbound calls to the QloApps / Prestashop module stores are disabled by default.
- 🧩 **Extensible from source** – custom modules can still be developed and dropped into `modules/` without depending on proprietary services.
- 📆 **Calendar first** – the admin booking view now opens on a resource timeline covering rooms, ateliers, seminar rooms and programme spaces, with the legacy month grid available on demand.
- 📨 **Inquiry workflow** – when inquiry mode is enabled, the legacy checkout paths forward to a multi-step inquiry form that collects structured stay preferences, persists directly to the Kanban board and emails confirmations, while cart-driven checkout remains available when the flag is disabled.
- 🔌 **Offline-friendly admin** – the Addons and Theme catalogues show local installation guidance instead of remote marketplace iframes.
- 🧭 **Residency navigation** – the front-office header ships with a static residency nav bar and in-house quick links; cart/account/newsletter/social blocks have been excised from both the theme overrides and the module set.
- 🗺️ **Resource showcase** – the home page now renders a residency showcase fed by published resource profiles so rooms, studios, gastronomy and programme spaces surface real capacity and availability cues.
- 🔒 **Legacy API removed** – `/webservice` now responds with HTTP 410 and the back office no longer advertises API key management.
- 💳 **Payments deferred** – legacy bank wire, cheque and PayPal Commerce modules are stripped out so stays are confirmed and settled off-platform.
- 🔄 **Interactive timeline** – bookings can be reallocated by dragging directly on the admin timeline, with conflict checks against disabled rooms and occupancy caps.
- 🗂️ **Inquiry board** – a Kanban-style board replaces cart scaffolding so inquiries can be triaged, assigned, noted and scheduled with reminders (mail notes email guests directly from the board).
- 🌐 **Scoped REST endpoints** – new JSON endpoints cover timeline moves, inquiry status changes and availability lookups without reviving the legacy webservice.
- 🧱 **Resource taxonomy scaffolding** – installing the distribution now seeds dedicated tables and `ObjectModel` classes for resource profiles, capacities, amenities, storytelling copy and change history so upcoming admin UX can plug into structured metadata.
- 🗃️ **Resource profile editor** – the back office ships with a Resource Profiles tab so staff can maintain taxonomy metadata and capacity descriptors for rooms, ateliers and gastronomy spaces ahead of amenity management.
- 🧾 **Taxonomy editor enhancements** – amenity assignments, inline capacity validation, change history snapshots and residency showcase previews live directly inside the Resource Profiles form so staff can review impact before publishing.
- 🏷️ **Amenity catalogue manager** – a new Catalog → Amenities screen lets staff create reusable amenity codes, toggle availability and capture icon/translation metadata in preparation for resource-level linking.
- 🧮 **Room-type seeding helper** – install/upgrade flows and `modules/hotelreservationsystem/tools/seed_resource_profiles.php` backfill taxonomy profiles and capacities for existing room types so legacy data is represented immediately.
- 🧶 **Storytelling scaffolding** – feature-flagged residencies (`index.php?controller=residencies`), ateliers (`index.php?controller=ateliers`), gastronomy (`index.php?controller=gastronomy`) and programme (`index.php?controller=programme`) landings now pull taxonomy-driven sections, scope-aware featured package groups, grouped availability snapshots, amenity callouts, slot-level inquiry CTAs and CMS-managed hero/highlight/practical/FAQ/testimonial slots when `_KUNSTORT_STORYTELLING_LAUNCH_` is enabled.
- 💡 **Storytelling pricing highlights** – featured packages now display cached starting rates, sample stay context and inclusion summaries generated via canonical calls to `KLQuotePricingEngine::generateQuote()`.
- 🖼️ **Hero media pipeline** – taxonomy stories expose hero media references and alt text; the theme ships `npm run build:hero-media` to emit responsive WebP/JPEG variants while storytelling templates render lazy-loaded `<picture>` elements with accessible captions.
- 💼 **Rate plan & quote engine** – the module now ships database tables and `ObjectModel` classes for rate plans, seasonal modifiers, bundled packages and inquiry-linked quotes, and the `KLQuotePricingEngine` turns those definitions into inquiry-ready pricing breakdowns.
- 🗓️ **Rate plan console** – manage plan metadata, eligibility scopes and seasonal adjustments directly from the back office.
- 🎁 **Package builder** – assemble bundled offers by combining lodging, atelier, catering and experience components without touching SQL tables.
- 🧹 **Operations automation** – the `kloperations` module seeds housekeeping runs, spawns maintenance start/release tasks from room disable ranges, emails daily digests plus overdue reminders, and exposes an **Operations → Tasks** console for manual task authoring, assignment workflows and mobile checklists.
- 📤 **Operations exports** – admins can export pending tasks to CSV or ICS directly from the console for external scheduling tools.

The high-level concept lives in [`concept.md`](concept.md), the multi-phase plan in [`roadmap.md`](roadmap.md), tactical progress in [`checklist.md`](checklist.md), and task briefs in [`devtasks/`](devtasks/).

### Inquiry Landing

Visiting `/index.php?controller=inquiry` now renders a guided three-step submission experience. Guests enter contact details, stay preferences (dates, flexibility, party size, resource kinds) and optional programme notes or package codes before consenting to data usage. Autosuggest lists are hydrated via `index.php?fc=module&module=hotelreservationsystem&controller=inquirylookup`, which exposes published resource profiles and active packages without relying on the retired webservice. Submissions persist to `HotelInquiry`, an audit note is logged and both guest/staff receive confirmation emails.

Linking into the form with `package_code` or `package_preferences` query parameters preselects the relevant package so storytelling CTAs land with context.

When `_KUNSTORT_CORE_MODE_` equals `inquiry`, legacy checkout URLs such as `/index.php?controller=order` continue to redirect to this form instead of exposing cart mechanics, but the original order and one-page-checkout controllers/templates remain in place so clearing the flag instantly restores the classic flow.

While inquiry mode is active the cart controller refuses to mutate cart contents—direct requests receive an error (or redirect to the inquiry landing) so ghost carts cannot accumulate behind the scenes—and any AJAX checkout calls now short-circuit with a friendly JSON error explaining that checkout is disabled.

### Storytelling landings (feature flagged)

Set `_KUNSTORT_STORYTELLING_LAUNCH_` to `true` in `config/defines_custom.inc.php` to expose the storytelling landings at `/index.php?controller=residencies`, `/index.php?controller=ateliers`, `/index.php?controller=gastronomy` and `/index.php?controller=programme`. Each page is powered by `HotelReservationSystemStorytellingPresenter`, which aggregates taxonomy-driven sections with metadata, surfaces featured `KLPackage` entries flagged for promotion, caches grouped availability highlights, hydrates CMS-managed copy slots and now feeds amenity callouts alongside capacity summaries.

All storytelling controllers register `themes/hotel-reservation-theme/css/storytelling.css`, which is authored in `sass/storytelling.scss` and scoped to the `.kl-storytelling` namespace. The bundle introduces responsive grid/flex layouts, WCAG 2.1 AA colour tokens and accessible focus states while a trimmed inline block (`_partials/storytelling-critical.tpl`) ensures hero and container styling render immediately without blocking requests.

Non-critical JavaScript can opt into the new `klStorytellingDefer` helper. Either enqueue payloads with:

```javascript
window.klStorytellingDeferQueue = window.klStorytellingDeferQueue || [];
window.klStorytellingDeferQueue.push({ src: 'https://example.test/analytics.js', async: true });
```

or output `<template data-kl-storytelling-defer data-src="/modules/feature.js" data-kl-async>` from a module hooked into `displayStorytellingScripts`. The helper processes queued entries after the `load` event, appending scripts with `defer`/`async` semantics so analytics widgets, galleries and other enhancements stay out of the critical rendering path.

Availability snapshots surface CTA buttons beside each slot; the presenter now emits slot-specific inquiry URLs so clicking a CTA opens the inquiry form with arrival/departure fields, resource kind and the highlighted resource code already filled in.

Featured packages render as grouped cards keyed to each resource kind scope, complete with CTA buttons that deep-link into the inquiry form with package codes and resource interests preselected. Packages without scope metadata fall back to a campus-wide highlight group so cross-cutting bundles stay visible.

Pricing highlights reuse those package groups: the presenter now assembles canonical sample stays per package, calls `KLQuotePricingEngine::generateQuote()` and caches the resulting starting rate, sample stay description and inclusion summary so cards show rate guidance without hammering the engine.

- **Residencies CMS keys:** `KL_STORY_RESIDENCIES_HERO`, `KL_STORY_RESIDENCIES_AVAILABILITY`, `KL_STORY_RESIDENCIES_PRACTICAL`, `KL_STORY_RESIDENCIES_FAQ`, `KL_STORY_RESIDENCIES_TESTIMONIALS`.
- **Ateliers CMS keys:** `KL_STORY_ATELIERS_HERO`, `KL_STORY_ATELIERS_AVAILABILITY`, `KL_STORY_ATELIERS_PRACTICAL`, `KL_STORY_ATELIERS_FAQ`, `KL_STORY_ATELIERS_TESTIMONIALS`.
- **Gastronomy CMS keys:** `KL_STORY_GASTRONOMY_HERO`, `KL_STORY_GASTRONOMY_AVAILABILITY`, `KL_STORY_GASTRONOMY_PRACTICAL`, `KL_STORY_GASTRONOMY_FAQ`, `KL_STORY_GASTRONOMY_TESTIMONIALS`.
- **Programme CMS keys:** `KL_STORY_PROGRAMME_HERO`, `KL_STORY_PROGRAMME_HIGHLIGHTS`, `KL_STORY_PROGRAMME_AVAILABILITY`, `KL_STORY_PROGRAMME_SCHEDULE`, `KL_STORY_PROGRAMME_INQUIRY`, `KL_STORY_PROGRAMME_FAQ`.

Assign the configuration keys to CMS page IDs (per shop) to hydrate copy blocks. Any missing content gracefully falls back to taxonomy data and placeholder messaging, keeping both pages navigable during rollout rehearsals.

### Hero media workflow

Resource story records now surface `image_reference` slugs and `alt_text` copy. Drop full-resolution assets into `themes/hotel-reservation-theme/storytelling/media/source/`, run `npm install` (the first time) and then `npm run build:hero-media` from the theme directory to generate responsive WebP/JPEG variants in `storytelling/media/`. The presenter exposes hero media only when the `{reference}-{width}.{ext}` files exist, so templates fall back gracefully if imagery has not been processed. Smarty renders the payload as a lazy-loaded `<picture>` element with a figcaption derived from the story metadata, keeping accessibility descriptions aligned with editorial updates.

### Legacy PrestaShop Webservice

For security and maintainability the bundled PrestaShop webservice has been retired:

- `webservice/dispatcher.php` immediately returns **410 Gone** without bootstrapping the application.
- Core webservice classes are replaced by stubs so that stray module references fail fast instead of re-enabling the API.
- The **Advanced Parameters → Webservice** tab and related configuration switches are removed from the installer and upgrade scripts.

If you need API access, build explicit modules on top of modern authentication flows rather than reviving the legacy endpoint.

## Admin Booking Timeline
The back-office path **Hotel Reservation System → Booking** now presents a tabbed layout with a top-aligned tab bar instead of the previous side menu:

- **Timeline**: a fast-loading occupancy grid grouped by room type; cells are colour-coded for booked, in-cart, unavailable and partially available stays, and the fetched dataset is cached while the tab remains active to keep reloads instant.
- **Calendar**: the familiar month grid rendered lazily only when the tab is opened.
- **Search & Filters**: the booking form, occupancy selector and availability stats.

Edits performed from the availability list or cart refresh both the timeline and (when initialised) the month grid so that staff always see the latest state.

### Drag-and-Drop Reallocation

Drag any booked cell from its start date to another room/day to trigger a reallocation preview. The timeline will validate the move against:

- Existing bookings on the destination room.
- Room disable ranges (maintenance/out-of-order blocks).
- Room type capacity (adults, children and total guests).

If the move is valid you can confirm the change directly from the drop action. Conflicts are listed inline so staff know why a move is blocked.

### Availability API

`index.php?controller=AdminHotelRoomsBooking&ajax=1&action=lookupAvailability` accepts `id_hotel`, `id_room_type`, `date_from` and `date_to` to return JSON payloads of available, booked and disabled rooms for the requested window. The timeline UI consumes the same endpoint.

## Inquiry Board
Navigate to **Hotel Reservation System → Inquiries** to triage, assign and progress residency requests. Each column represents a stage (Inbox, Qualifying, Awaiting reply, Scheduled, Archived) and cards expose:

- Assignee dropdowns that fire async updates.
- Reminder shortcuts (stored on the inquiry record) for follow-up scheduling.
- Note capture with an optional “mail note” flag that emails the requester and logs the correspondence for the team.

Dragging a card to another column automatically updates its stage (and default status), with conflict messaging if a move is not allowed.

## Resident-Focused Front Office

- The theme header no longer invokes `HOOK_TOP`, `displayTopColumn`, or left/right column placeholders. Instead it renders a static residency navigation bar with anchors for residences, studios & ateliers, dining, programme spaces, and contact along with direct resident-service shortcuts and support contacts.
- The home page now includes a residency showcase block sourced from the new resource taxonomy tables so rooms, ateliers, gastronomy and programme spaces present real metadata (capacity, availability posture, timezone) instead of mock content.
- The hotel description ribbon is rendered inline from configuration values so the chain name and tagline stay visible without needing module hooks.
- Legacy e-commerce widgets (`blockcart`, `blockuserinfo`, `blockmyaccount`, `blocknewsletter`, `blocksocial`) have been removed from both `themes/hotel-reservation-theme/modules/` and `modules/`, and their default hook assignments have been stripped from installation metadata so fresh installs or upgrades never attempt to load them.
- Core controllers now guard newsletter integration points so missing modules do not trigger autoload errors on identity or authentication flows.

Upcoming storytelling templates for residencies, ateliers, gastronomy and programme spaces are being planned in [`docs/blueprints/front-office-storytelling.md`](docs/blueprints/front-office-storytelling.md), which defines the presenter layer, CMS content keys and rollout sequencing for the next milestone.

## Requirements
The project still runs on the QloApps/PrestaShop stack. For development you will need:

- PHP 8.1 – 8.4 with extensions: PDO_MySQL, cURL, OpenSSL, SOAP, GD, SimpleXML, DOM, Zip, Phar.
- MySQL/MariaDB 5.7 – 8.4.
- Apache or Nginx with HTTPS support.
- Composer (for dependency management) and npm/yarn if you plan to rebuild assets.

Increase PHP limits for development (memory ≥ 256M, max_execution_time ≥ 300) to accommodate module installations and asset builds.

## Getting Started
1. Clone the repository and install dependencies:
   ```bash
   composer install
   ```
2. Create a database and copy `config/settings.inc.php` from the installer or your previous QloApps installation.
3. Make sure `config/defines_custom.inc.php` is loaded (it is included automatically) so that marketplace integrations remain disabled.
4. Run the classic QloApps installer by visiting `/install` in your browser, or migrate an existing database.

### Quickstart script

For day-to-day development run:

```bash
./start_dev.sh
```

The helper script creates (or reuses) a local Python virtual environment at `.venv`, installs Composer dependencies, warns if the application still needs to be installed, and finally starts the PHP built-in server on `http://127.0.0.1:8000`. Override the host or port by exporting `HOST`/`PORT` before executing the script. The bundled development router stays compatible with PHP 7.4+, so the server can run on environments that have not yet upgraded to PHP 8.

After installation you can log into the admin back office at `/admin` (rename the directory for security). The module catalogue will no longer attempt to connect to external stores; only locally available modules are listed.

## Known issues

We are tracking the current bug backlog in [`docs/issues.md`](docs/issues.md). Highlights from the latest maintenance round:

- Composer now targets PHP 8.1–8.4, matching the runtime guidance above.
- The inquiry quote preview AJAX endpoint relays payloads that align with `KLQuotePricingEngine::generateQuote()` and preserve submitted occupancy details.
- `./start_dev.sh` bootstraps tooling without leaving a `.venv/` directory tracked by Git.

### Resource taxonomy seeding

Existing databases upgraded to the Kunstort fork can populate taxonomy metadata by running:

```bash
php modules/hotelreservationsystem/tools/seed_resource_profiles.php [--employee-id=ID]
```

The helper inspects `htl_room_type` rows, creates any missing `kl_resource_profile` records, and mirrors the legacy adult/child/total capacity values into `kl_resource_capacity`. Pass an optional `--employee-id` to record who executed the migration. The script is idempotent and safe to re-run after adding new room types.

### Resource profile management

Navigate to **Hotel Reservation System → Resource Profiles** to maintain taxonomy metadata. The form now bundles amenity assignment checkboxes (fed by Catalog → Amenities), inline capacity validation that guards against conflicting bookings, a change history summary pulled from `kl_resource_history` and a residency showcase preview that mirrors the front-office block. These cues help staff verify edits before publishing them to the showcase or downstream exports.

### Rate plan & package scaffolding

The new pricing scaffolding lives inside the `hotelreservationsystem` module. After pulling an update that includes database changes run the module upgrade so the `kl_rate_plan*`, `kl_package*` and `kl_quote` tables are created:

```bash
php bin/console prestashop:module upgrade hotelreservationsystem
```

Alternatively trigger the upgrade from **Modules → Module Manager** in the back office. The installer is safe to re-run; it only creates missing tables.

### Rate plan management UI

Back-office staff can administer pricing rules from **Hotel Reservation System → Rate Plans**:

- Create and edit rate plans with translated names, taglines and long-form descriptions.
- Define pricing method, plan currency, eligible resource kinds and optional audience segment tags.
- Capture cancellation policy notes, deposit requirements and approval flags.
- Jump into per-plan season management to configure fixed or percentage adjustments, date windows and stay/lead-time constraints.

Seasonal rules live under the same navigation: choose a plan, then click **Seasons** to open an inline list filtered to that plan. Each season supports fixed amount or percentage adjustments expressed in minor units/basis points plus optional stay, occupancy and lead-time guards.

### Package assembly UI

Head to **Hotel Reservation System → Packages** to craft bundled offers:

- Curate package metadata (code, translatable name, tagline, storytelling description, featured flag) and scope it to specific resource kinds or audience segments.
- Link a default rate plan and annotate duration hints so inquiry responses stay consistent with residency programme expectations.
- Use the inline **Package components** builder to add lodging, atelier, meal, experience or custom components, control quantities/units, capture optional extras, associate alternate rate plans and order the lines for downstream quoting.
- Reorder components with quick move buttons, edit entries in place and persist the JSON payload without direct database edits.

### Quote generation service

With plans and packages configured, the `KLQuotePricingEngine` orchestrates stay pricing. Call `KLQuotePricingEngine::generateQuote()` with the rate plan, resource profile, stay window and optional package selections to receive a currency-safe payload of line items, seasonal adjustments and totals. Persist the result alongside an inquiry via `KLQuotePricingEngine::persistQuote()` to keep an auditable history of drafts, sent quotes and approvals inside the Kanban board.

### Operations automation module

Operations automation now ships inside `modules/kloperations`:

- Installing/upgrading the module creates `kl_operation_run`, `kl_operation_task`, `kl_operation_task_assignment` and `kl_operation_task_note` tables plus matching `ObjectModel` classes.
- `KlOperationTaskGenerator` hooks into `actionCronJob` to create arrival and checkout housekeeping tasks each day based on `HotelBookingDetail` rows while skipping cancelled/refunded stays.
- Room disable ranges spawn paired maintenance tasks: a morning "maintenance_start" checklist when the block begins and an afternoon "maintenance_release" follow-up on the day the block ends so spaces are reopened deliberately.
- Booking lifecycle hooks keep generated tasks in sync—arrivals flip to `in_progress` when guests check in, checkouts mark completed when stays close.
- The back office exposes **Operations → Tasks** for listings, bulk completion, payload/notes inspection and assignment management.
- Manual task authoring is available from the same console, capturing structured payloads and optional kickoff notes while generating unique keys and audit trails automatically.
- Assign tasks to employees or named teams, capture acknowledgement/completion timestamps and manage statuses inline without leaving the console.
- A lightweight mobile view is available at `index.php?controller=AdminKlOperationTasks&mobile_view=1&token=…`, letting logged-in staff review their queue, update statuses, release tasks and claim unassigned work from housekeeping devices.
- Toolbar buttons export pending tasks for the next seven days to CSV or iCalendar so schedules (now including assignment summaries) can be shared with external partners.
- Daily cron runs also deliver an HTML/text digest and overdue reminders to the addresses listed in `KLOPERATIONS_DIGEST_RECIPIENTS` (comma/space separated emails via the `Configuration` table or module upgrade script).
- Configure reusable teams by storing JSON or newline-delimited entries in `KLOPERATIONS_TEAMS`; each entry provides a `id`/`label` pair surfaced in the assignment form for quick selection.

To run the generator on demand you can trigger the cron hook:

```bash
php bin/console prestashop:module run-hook kloperations actionCronJob
```

Or schedule it through the standard Prestashop `cronjobs` module/host-level cron hitting `index.php?module=cronjobs&...`. The generator is idempotent thanks to unique task hashes and updates `last_reminded_at` so overdue emails are throttled.

## Distribution Flags
All Kunstort-specific flags live in `config/defines_custom.inc.php`:

- `_QLOAPP_DISABLE_MARKETPLACE_` – when `true`, disables outbound marketplace requests and UI components.
- `_KUNSTORT_CORE_MODE_` – describes the current interaction model (`'inquiry'` while we move away from carts).

Use these constants in future contributions to gate legacy commerce flows.

## Development Priorities
- Broaden resource annotations (rooms, ateliers, gastronomy) to enrich availability storytelling and reporting. See [`docs/blueprints/resource-taxonomy.md`](docs/blueprints/resource-taxonomy.md) for the canonical data model.
- Replace the front-office room list with storytelling-driven templates and an enquiry form tied to curated packages. Content strategy is outlined in the taxonomy blueprint and will drive copy blocks surfaced on offer pages.
- Wire up configurable rate plans and bundled packages on top of the new scaffolding so inquiries can be priced consistently, following [`docs/blueprints/rate-plans-packages.md`](docs/blueprints/rate-plans-packages.md).
- Extend operations automation with follow-on analytics (utilisation dashboards, programme reporting) once assignment workflows and mobile checklists settle in (see [`docs/blueprints/operations-automation.md`](docs/blueprints/operations-automation.md)).

See [`checklist.md`](checklist.md) for the current implementation status.

## Contributing
This fork welcomes contributions that reinforce the above goals. Keep the codebase libre and avoid reintroducing external marketplaces or proprietary dependencies. Please open issues or discussions before large structural changes.

## License
The original QloApps core remains licensed under OSL-3.0. Custom additions in this fork inherit the same license unless stated otherwise. Review [`LICENSE.md`](LICENSE.md) for details.
