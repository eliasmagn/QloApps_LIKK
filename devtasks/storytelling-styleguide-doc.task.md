# Task Brief: Storytelling Styleguide Documentation

## Objective
Produce an editorial and localisation styleguide that anchors the storytelling templates in a consistent voice. The guide should empower copywriters, translators and designers to ship residency pages without second-guessing tone, media specs or page structure.

## Key Deliverables
- Draft `docs/storytelling-styleguide.md` with:
  - Tone, tense and vocabulary guidance for residencies, ateliers, gastronomy and programme copy.
  - Image sourcing, crop ratios, colour grading and alt-text standards tied to the hero media pipeline.
  - Localisation expectations for German/English today plus a pattern for additional languages.
  - Sample page layouts that map copy slots, media and CTAs per storytelling template.
- Cross-link the new guide from the repository README and docs index so collaborators can discover it quickly.
- Update `concept.md`, `checklist.md`, and `roadmap.md` with the documentation deliverable so future sprint plans track editorial enablement work.

## Inputs & Research
- Existing storytelling templates under `themes/hotel-reservation-theme/storytelling/`.
- Taxonomy-driven copy fields surfaced by `HotelReservationSystemStorytellingPresenter`.
- Hero media processing pipeline documented in the `storytelling-hero-media` task.
- Translation keys declared in CMS configuration (`KL_STORY_*`).

## Acceptance Criteria
- `docs/storytelling-styleguide.md` covers tone, imagery, localisation and layout expectations with actionable guidance and example snippets.
- README, docs index and planning docs all reference the new guide.
- Content design stakeholders confirm the guide is sufficient to brief freelance writers or translators without additional meetings.

## Risks & Mitigations
- **Risk:** Tone guidance drifts from resident-facing messaging. **Mitigation:** Anchor recommendations in Kunstort values and provide sample microcopy for approvals.
- **Risk:** Image specs diverge from the hero media pipeline. **Mitigation:** Reuse the same dimensions/variants outlined in the hero media tooling docs and reference the build command explicitly.
