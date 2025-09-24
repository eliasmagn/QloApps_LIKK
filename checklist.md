# Development Checklist

## Completed
- [x] Introduced `config/defines_custom.inc.php` with distribution feature flags.
- [x] Disabled QloApps marketplace lookups in Tools and admin controllers.
- [x] Replaced marketplace catalog UIs with offline guidance when remote services are disabled.
- [x] Replaced the admin booking calendar with a tabbed occupancy timeline and lazy-loaded month grid fallback.
- [x] Cached admin booking timeline data to keep tab switches instantaneous.
- [x] Short-circuited checkout routes and templates to the inquiry landing page.
- [x] Replaced hook-driven header widgets with a static residency navigation and removed cart/account/newsletter/social modules from the codebase.
- [x] Retired the legacy PrestaShop webservice (dispatcher now returns 410, admin tab removed, classes stubbed).
- [x] Removed legacy bank wire, cheque and PayPal Commerce payment modules plus their theme overrides.
- [x] Modeled a dedicated Inquiry entity plus Kanban board with reminders, assignments and mail notes that email guests when flagged.
- [x] Added `start_dev.sh` to bootstrap Composer, Python tooling and the PHP dev server for local testing.

## In Progress
- [ ] Draft resource taxonomy for rooms, ateliers, gastronomy areas.
  - [x] Scaffolded database tables and `ObjectModel` classes for profiles, capacities, amenities, storytelling and history logs.
  - [x] Added an admin "Resource Profiles" tab to manage profile metadata and capacity descriptors.
  - [x] Added a Catalog → Amenities manager so reusable amenity codes, icons and translation domains can be curated before linking to resources.
  - [x] Seeded room-type profiles and capacity rows via install/upgrade hooks and an idempotent CLI helper.
- [ ] Design configurable rate plan entities and package bundling rules.
- [ ] Outline automation scope for housekeeping, notifications and exports.

## Planned
- [ ] Build out the dedicated inquiry submission pipeline on top of the new entry point.
- [ ] Rebuild front-office templates around availability storytelling.
- [ ] Introduce configurable rate plans and bundled residency packages.
- [ ] Implement housekeeping and maintenance task automation.
- [ ] Implement export utilities (CSV/ICS) for residency scheduling.
- [ ] Deliver utilisation dashboards and programme reporting.

## Recently Completed
- [x] Layered drag-and-drop reallocation controls onto the booking timeline with conflict detection.
- [x] Exposed REST endpoints for timeline edits, inquiry updates and availability lookups.
- [x] Enabled mail note delivery from the inquiry board so assignees can email guests while logging internal notes.
- [x] Replaced the front-office residency showcase mockups with live resource profile data and capacity summaries.
