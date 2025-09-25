# Task Brief: Front-Office Storytelling Templates

## Objective
Replace the remaining commodity product listings with narrative-driven templates that highlight residency programmes, studios, gastronomy and event spaces. The experience should guide visitors toward curated inquiries instead of instant booking.

## Key Deliverables
- Design modular Twig/Smarty templates that showcase hero imagery, storytelling copy, availability cues and inquiry CTAs per resource type.
- Build CMS-backed content slots so non-technical staff can adjust narratives without redeploying code.
- Integrate rate plan/package highlights pulled from `KLRatePlan` and `KLPackage` metadata.
- Provide accessibility-compliant layouts (WCAG 2.1 AA) with responsive behaviour for mobile, tablet and desktop breakpoints.

## Technical Considerations
- Reuse taxonomy metadata (amenities, capacities, storytelling fields) to avoid duplication; expose helper services if needed.
- Ensure templates respect `_KUNSTORT_CORE_MODE_ = 'inquiry'` by routing CTAs to the inquiry landing or new submission flow.
- Optimise asset loading: lazy-load gallery images, inline critical CSS via the theme build pipeline, and defer non-essential scripts.
- Guard against missing translations by falling back to default locale strings and logging gaps for localisation follow-up.

## Cross-Team Dependencies
- Coordinate with design for art direction, photography needs and copy tone.
- Align with inquiry workflow owners so CTA copy, form fields and expectations match the upcoming submission pipeline.

## Acceptance Criteria
- Residency, atelier and gastronomy pages use the new templates with taxonomy-driven data and pass Lighthouse performance and accessibility audits (score ≥ 90 for each category on desktop).
- QA checklist covers multi-language rendering, right-to-left support and offline caching scenarios if Service Worker work begins.
- Documentation updates in `docs/blueprints/frontend-storytelling.md` outline template structure and editable content areas.

## Risks & Mitigations
- **Risk:** Content editors may find CMS slots confusing. **Mitigation:** Provide inline editing tips and a short Loom walkthrough.
- **Risk:** Asset-heavy pages could regress performance. **Mitigation:** Enforce image compression budgets and monitor Core Web Vitals.
