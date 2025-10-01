# Blueprint: Front-Office Storytelling Experience

## Purpose
Establish a narrative-driven front-office experience that replaces the remaining commodity product listings with curated storytelling for Kunstort Lehnin's residencies, ateliers, gastronomy and programme spaces. The templates should surface availability cues, highlight relevant packages and guide visitors toward the inquiry workflow instead of instant checkout interactions.

## Experience Pillars
- **Narrative first** – hero copy, photography and programme highlights lead each page so visitors understand the artistic context before scanning rates or schedules.
- **Inquiry centric** – every call-to-action routes to the inquiry form (or a tailored step) and reflects whether `_KUNSTORT_CORE_MODE_` is set to `inquiry`.
- **Taxonomy powered** – resource profiles, amenities and storytelling metadata from the taxonomy tables populate content blocks to avoid duplication.
- **Package aware** – rate plans and curated packages provide optional add-ons or residency bundles contextualised to each resource type.
- **Performance minded** – media loading, layout shifts and script execution are tuned to keep Lighthouse performance/accessibility scores ≥ 90.

## Template Architecture
Each major resource type (Residencies, Ateliers & Studios, Gastronomy & Catering, Programme & Events) receives a dedicated template composed from shared building blocks:

1. **Hero Band**
   - Full-bleed responsive image pulled from taxonomy storytelling media references.
   - Overlay headline, strapline and primary CTA button linking to `/index.php?controller=inquiry` with UTM parameters identifying the originating template.

2. **Availability Snapshot**
   - Surface next open residency windows or atelier slots using cached availability summaries from the booking timeline endpoints.
   - Provide a secondary CTA (“Request this slot”) that deep-links into the inquiry form with pre-filled resource preferences.

3. **Storytelling Sections**
   - Markdown-enabled copy slots for artistic focus, facilities, community offerings and support services, stored via CMS fields so editors can update content without deployments.
   - Inline amenity chips and capacity callouts generated from `kl_resource_amenity` and `kl_resource_capacity` rows.

4. **Programme Highlights**
   - Optional carousel that cross-promotes upcoming programmes or residencies, driven by future `KLPackage` entries flagged for front-office display.

5. **Testimonial / Resident Voices**
   - Rotating quotes stored in a new CMS content group with attribution fields.

6. **Practical Information**
   - Travel guidance, accessibility statements and downloadable factsheets (PDF) maintained through CMS-managed assets.

7. **FAQ Block**
   - Accordion component seeded from CMS question/answer pairs per resource type.

## Content Management Strategy
- Introduce CMS identifiers (e.g. `KL_STORY_RESIDENCIES_HERO`, `KL_STORY_ATELIER_FAQ`) and register them in the admin UI with inline guidance.
- Provide preview tooling in the back office so editors can see staged copy against the new templates before publishing.
- Document content governance (word counts, imagery ratios, translation requirements) alongside example copy snippets.

## Data & Service Integrations
- Extend the `KLQuotePricingEngine` to expose highlight data (starting weekly rate, package inclusions) for templates.
- Add helper methods on `HotelReservationSystemStorytellingPresenter` (new class) that merges taxonomy, package and availability data for consumption by Smarty.
- Cache availability snippets in `Cache::store()` for 15 minutes to balance freshness with performance.
- Create JSON endpoints for testimonials and FAQ groups to support potential headless reuse.

## Performance & Accessibility Guardrails
- Optimise hero and gallery images via the theme’s build pipeline (WebP + responsive `srcset`).
- Inline critical CSS for hero typography and primary layout grid; defer non-critical assets.
- Ensure heading hierarchy remains consistent across templates.
- Provide keyboard-focus outlines for carousel and accordion components, and validate colour contrast ≥ 4.5:1.
- Add integration tests (PHPUnit + Symfony Panther) that run Lighthouse CLI audits in CI with thresholds: Performance ≥ 90, Accessibility ≥ 90, Best Practices ≥ 90, SEO ≥ 90.

## Rollout Plan
1. **Scaffolding (Milestone Kick-off)**
   - Build the presenter/service layer, register CMS keys, and stub Smarty templates with placeholder sections. ✅ Implemented via `HotelReservationSystemStorytellingPresenter`, a new `ResidenciesController` front controller and `themes/hotel-reservation-theme/storytelling/residencies.tpl`.
   - Ship feature flags in `config/defines_custom.inc.php` for incremental rollout (`_KUNSTORT_STORYTELLING_LAUNCH_`, default `false`).
   - CMS slot identifiers for the residency landing: `KL_STORY_RESIDENCIES_HERO`, `KL_STORY_RESIDENCIES_AVAILABILITY`, `KL_STORY_RESIDENCIES_PRACTICAL`, `KL_STORY_RESIDENCIES_FAQ`, `KL_STORY_RESIDENCIES_TESTIMONIALS`. Populate each with CMS page IDs via `Configuration` to hydrate the template.
2. **Residencies Template**
   - Implement full content flow and availability snapshot.
   - Capture design QA feedback and iterate on components.
3. **Ateliers & Studios Template**
   - Reuse components, adapt copy guidelines and package highlights.
4. **Gastronomy & Programme Templates**
   - Integrate catering-specific packages, event schedules and menu download links.
5. **Accessibility & Performance Validation**
   - Run Lighthouse audits, device lab smoke tests and screen reader reviews.
6. **Launch & Enablement**
   - Update documentation, train editors, switch the rollout flag to enable templates for all visitors.

## Documentation & Communication
- Maintain this blueprint alongside implementation notes in `docs/blueprints/front-office-storytelling.md`.
- Track execution tasks in `devtasks/front-office-storytelling.task.md` and reference checklist/roadmap updates.
- Add screenshots and copywriting guidelines to `docs/storytelling-styleguide.md` (new file to be created during implementation).
