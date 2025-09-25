# Kunstort Lehnin Roadmap

This roadmap tracks how the Kunstort Lehnin fork evolves from a de-bloated QloApps baseline into a residency-first operations suite. Each phase groups together coherent deliverables so we can prioritise work and spot dependencies early. Status markers: ✅ complete, 🚧 in progress, ⏳ planned.

## Phase 0 – Commerce Debloating (✅ Complete)
- ✅ **Checkout short-circuit** – `order`/`order-opc` controllers render the inquiry landing and the matching theme templates only display the manual booking guidance.
- ✅ **Payment modules removed** – legacy `bankwire`, `cheque` and `qlopaypalcommerce` modules (and overrides) are no longer shipped so no unused PCI-facing code stays in the tree.
- ✅ **Front-office widgets stripped** – header and column slots now render a static residency navigation without cart, account, newsletter or social hooks.
- ✅ **Webservice disabled** – `/webservice` responds with HTTP 410 and admin tooling for API keys has been excised to reduce attack surface.

## Phase 1 – Inquiry Workflow Maturity (🚧 In progress)
- ✅ **Timeline interaction upgrades** – add drag-and-drop reallocation with conflict detection against room disables and capacity rules.
- ✅ **Inquiry entity & board** – replace cart-derived booking scaffolding with a dedicated Inquiry model, Kanban board UI and assignment workflow, including reminders and guest-facing mail notes from the board.
- ✅ **API endpoints** – expose REST endpoints for timeline updates, inquiry status changes and availability lookups to support the new UI components.
- ✅ **Dev bootstrap helper** – ship `start_dev.sh` to keep Composer dependencies fresh, prep Python tooling via `.venv`, and launch the PHP dev server for quick smoke testing.

## Phase 2 – Resource & Pricing Model (🚧 In progress)
- 🚧 **Resource taxonomy** – add `resource_kind`, capacity descriptors and amenities metadata to rooms, ateliers, seminar spaces and gastronomy units. The initial database tables plus matching `ObjectModel` classes now ship with the module install (see [`docs/blueprints/resource-taxonomy.md`](docs/blueprints/resource-taxonomy.md)). A back-office **Resource Profiles** tab now lets staff edit profile metadata and capacity descriptors, Catalog → Amenities curates amenity codes/icons/translation domains, and install/upgrade flows (plus a CLI helper) now backfill profiles and capacities for existing room types.
- ✅ **Rate plans & packages** – introduce configurable price plans (BAR, corporate, residency programmes) plus bundled packages for weekly ateliers, catering-inclusive stays, etc., as laid out in [`docs/blueprints/rate-plans-packages.md`](docs/blueprints/rate-plans-packages.md). Base database tables (`kl_rate_plan*`, `kl_package*`, `kl_quote`) and ObjectModel scaffolding now ship with the module to unblock admin UIs and pricing services. The back office exposes both **Rate Plans** and **Packages** tabs so staff can manage plan metadata, eligibility scopes, seasonal adjustments and assemble bundled offers via the new component builder, and the `KLQuotePricingEngine` now generates inquiry-ready quote payloads with seasonal adjustments and package components.
- 🚧 **Frontend storytelling** – the home page now surfaces published resource profiles with capacity cues; next steps extend storytelling and imagery to offer detail pages informed by taxonomy and packages.

## Phase 3 – Operations Automation (⏳ Planned)
- ⏳ **Housekeeping & maintenance tasks** – auto-generate cleaning and technical checklists from arrival/departure data and allow staff to report statuses (clean, in progress, needs repair). Architectural notes live in [`docs/blueprints/operations-automation.md`](docs/blueprints/operations-automation.md).
- ⏳ **Internal notifications** – hook inquiry and timeline events into task reminders, digest emails and calendar feeds for staff following the same blueprint.
- ⏳ **ICS/CSV exports** – deliver calendar and reporting exports for residency, seminar and event planning.

## Phase 4 – Reporting & Integrations (⏳ Planned)
- ⏳ **Utilisation dashboards** – surface occupancy, length-of-stay, residency programme metrics with filters by resource type.
- ⏳ **Programme publishing bridge** – sync curated events and residencies to the public Kunstort Lehnin website or other storytelling channels.
- ⏳ **External API strategy** – design modern, scoped APIs (authenticated modules, webhook patterns) to replace the retired PrestaShop webservice when needed.

We iterate through the roadmap top-to-bottom: hardening the base, completing inquiry workflows, enriching resource data, automating internal operations, and finally exposing analytics and integrations once the core experience is stable.
