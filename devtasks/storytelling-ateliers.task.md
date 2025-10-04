# Task Brief: Ateliers Storytelling Landing

## Objective
Publish the ateliers & studio storytelling landing so editors can narrate production spaces with the same tooling that powers the residencies page. The route should mirror the residency experience while filtering data to atelier-specific taxonomy and packages.

## Scope
- Extend `HotelReservationSystemStorytellingPresenter` to expose an `ateliers` payload (CMS slots, taxonomy sections, availability snapshot, featured packages).
- Add an `AteliersController` front controller plus `themes/hotel-reservation-theme/storytelling/ateliers.tpl` template that renders hero copy, availability cues, taxonomy-driven sections and CMS-managed supporting blocks.
- Update navigation, quick links and sitemap/footer references so the new controller surfaces wherever residencies storytelling appears, gated behind `_KUNSTORT_STORYTELLING_LAUNCH_`.
- Document the rollout in `concept.md`, `checklist.md`, `README.md` and `roadmap.md` so the storytelling milestone reflects the new coverage.

## Acceptance Criteria
- Visiting `/index.php?controller=ateliers` with `_KUNSTORT_STORYTELLING_LAUNCH_` enabled renders taxonomy-backed atelier sections, live availability messaging and CMS copy fallbacks.
- Navigation/header quick links, top task shortcuts and sitemap listings link to the new controller whenever the storytelling flag is on, and fall back to legacy anchors when it is off.
- Presenter methods load the `ateliers` CMS slot group, filter packages to atelier scopes and reuse the availability snapshot without surfacing residency-specific wording.
- Documentation, roadmap and task index entries reference the ateliers rollout and link to this brief.
