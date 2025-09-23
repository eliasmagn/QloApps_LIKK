# Development Checklist

## Completed
- [x] Introduced `config/defines_custom.inc.php` with distribution feature flags.
- [x] Disabled QloApps marketplace lookups in Tools and admin controllers.
- [x] Replaced marketplace catalog UIs with offline guidance when remote services are disabled.
- [x] Replaced the admin booking calendar with a tabbed occupancy timeline and lazy-loaded month grid fallback.
- [x] Cached admin booking timeline data to keep tab switches instantaneous.
- [x] Short-circuited checkout routes and templates to the inquiry landing page.
- [x] Retired the legacy PrestaShop webservice (dispatcher now returns 410, admin tab removed, classes stubbed).

## In Progress
- [ ] Draft resource taxonomy for rooms, ateliers, gastronomy areas.
- [ ] Layer drag-and-drop reallocation controls onto the new booking timeline.

## Planned
- [ ] Build out the dedicated inquiry submission pipeline on top of the new entry point.
- [ ] Rebuild front-office templates around availability storytelling.
- [ ] Implement export utilities (CSV/ICS) for residency scheduling.
