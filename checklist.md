# Development Checklist

## Completed
- [x] Introduced `config/defines_custom.inc.php` with distribution feature flags.
- [x] Disabled QloApps marketplace lookups in Tools and admin controllers.
- [x] Replaced marketplace catalog UIs with offline guidance when remote services are disabled.
- [x] Replaced the admin booking calendar with a tabbed occupancy timeline and lazy-loaded month grid fallback.
- [x] Cached admin booking timeline data to keep tab switches instantaneous.
- [x] Short-circuited checkout routes and templates to the inquiry landing page whenever inquiry mode is enabled, while keeping the cart workflow available when the flag is cleared.
- [x] Restored the full legacy order and one-page-checkout controllers/templates so disabling inquiry mode instantly re-enables checkout, with inquiry-mode requests returning friendly redirects or JSON errors.
- [x] Blocked cart controller mutations while inquiry mode is active to prevent ghost carts.
- [x] Replaced hook-driven header widgets with a static residency navigation and removed cart/account/newsletter/social modules from the codebase.
- [x] Retired the legacy PrestaShop webservice (dispatcher now returns 410, admin tab removed, classes stubbed).
- [x] Removed legacy bank wire, cheque and PayPal Commerce payment modules plus their theme overrides.
- [x] Modeled a dedicated Inquiry entity plus Kanban board with reminders, assignments and mail notes that email guests when flagged.
- [x] Built out the dedicated inquiry submission pipeline on top of the new entry point, including structured validation, JSON lookups and guest/staff notifications.
- [x] Added `start_dev.sh` to bootstrap Composer, Python tooling and the PHP dev server for local testing.
- [x] Designed configurable rate plan entities and package bundling rules.
  - [x] Scaffolded database tables and `ObjectModel` classes for rate plans, seasonal adjustments, bundled packages, components and inquiry quotes.
  - [x] Exposed admin management UIs for rate plans and seasonal rules.
  - [x] Built the package assembly UI for bundled offers.
  - [x] Implemented a pricing engine that produces inquiry quotes with seasonal adjustments and optional components.
- [x] Hardened resource taxonomy admin tools with amenity assignment, front-office previews, change history summaries and capacity guardrails.
- [x] Draft resource taxonomy for rooms, ateliers, gastronomy areas.
  - [x] Scaffolded database tables and `ObjectModel` classes for profiles, capacities, amenities, storytelling and history logs.
  - [x] Added an admin "Resource Profiles" tab to manage profile metadata and capacity descriptors.
  - [x] Added a Catalog → Amenities manager so reusable amenity codes, icons and translation domains can be curated before linking to resources.
  - [x] Seeded room-type profiles and capacity rows via install/upgrade hooks and an idempotent CLI helper.
  - [x] Delivered amenity assignment UI, inline capacity validation and change history previews directly in the Resource Profiles tab.
- [x] Bootstrapped operations automation scaffolding.
  - [x] Delivered the `kloperations` module with run/task/assignment/note tables and ObjectModels.
  - [x] Implemented a cron-driven housekeeping task generator with booking lifecycle synchronisation.
  - [x] Added an **Operations → Tasks** admin console with bulk completion and payload/notes detail view.
- [x] Extended operations automation with maintenance jobs, notifications and exports.
  - [x] Generate maintenance start/release tasks from room disable ranges.
  - [x] Deliver daily digest emails and overdue reminders to configurable recipients.
  - [x] Provide CSV/ICS exports from the operations console and throttle reminder delivery with `last_reminded_at` tracking.

## In Progress
- [ ] Add manual operations task authoring, assignment workflows and mobile-friendly checklists building on the new automation hooks.
  - [x] Ship manual task authoring inside the Operations console with JSON payload capture and optional kickoff notes.
  - [ ] Layer assignment workflows so tasks can be handed to employees or teams.
  - [ ] Provide lightweight mobile checklists tuned for housekeeping devices.

## Issues
- [x] Align Composer's PHP requirement with the documented PHP 8 support window so installs work on current runtimes.
- [x] Fix the inquiry quote preview endpoint payload so it talks to `KLQuotePricingEngine::generateQuote()` and respects submitted occupancy.
- [x] Ignore the `.venv` directory created by `start_dev.sh` to keep repositories clean after running the dev helper.

## Planned
- [ ] Rebuild front-office templates around availability storytelling (see [`devtasks/front-office-storytelling.task.md`](devtasks/front-office-storytelling.task.md)).
- [ ] Layer internal notification channels on top of inquiry/timeline events once the operations hooks are in place.
- [ ] Deliver utilisation dashboards and programme reporting.

## Recently Completed
- [x] Layered drag-and-drop reallocation controls onto the booking timeline with conflict detection.
- [x] Exposed REST endpoints for timeline edits, inquiry updates and availability lookups.
- [x] Enabled mail note delivery from the inquiry board so assignees can email guests while logging internal notes.
- [x] Replaced the front-office residency showcase mockups with live resource profile data and capacity summaries.
