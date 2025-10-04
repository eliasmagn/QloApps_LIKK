# Task Brief: Gastronomy Storytelling Landing

## Objective
Launch the gastronomy-focused storytelling landing so catering and communal dining spaces benefit from the same taxonomy-driven presenter, CMS copy slots and inquiry CTAs as the residencies and atelier pages.

## Scope
- Extend `HotelReservationSystemStorytellingPresenter` with a `gastronomy` payload that hydrates CMS slots, filters availability snapshots to catering resources, surfaces featured packages and exposes amenity details.
- Ship a `GastronomyController` front controller and `themes/hotel-reservation-theme/storytelling/gastronomy.tpl` template that render hero copy, availability messaging, taxonomy sections with amenity callouts and CMS-managed supporting blocks.
- Update front-office navigation, quick links and sitemap entries so the gastronomy landing surfaces wherever storytelling routes appear, guarded by `_KUNSTORT_STORYTELLING_LAUNCH_`.
- Capture the milestone in `concept.md`, `checklist.md`, `README.md` and `roadmap.md`, and ensure the feature flag instructions include the new route and CMS keys.

## Acceptance Criteria
- Visiting `/index.php?controller=gastronomy` with `_KUNSTORT_STORYTELLING_LAUNCH_` enabled renders taxonomy-backed gastronomy sections, live availability messaging, amenity lists and CMS fallbacks.
- Navigation/header quick links, top task shortcuts and sitemap listings point to the gastronomy controller whenever the storytelling flag is active and fall back to legacy anchors otherwise.
- Presenter methods return the `gastronomy` CMS slot set, filter availability/package data to gastronomy resources and expose amenity labels/notes for templates.
- Documentation and roadmap references highlight the gastronomy storytelling launch and link to this task brief.
