# Task Brief: Storytelling Hero Media

## Objective
Provide a repeatable pipeline for sourcing, optimising and publishing hero photography across all storytelling templates. Each taxonomy-backed profile should expose media metadata (reference, responsive assets, alt text) so the front office can render accessible `<picture>` elements without manual asset wrangling.

## Key Deliverables
- Extend taxonomy story records to expose hero media references and alt text in the storytelling presenter payload.
- Update Smarty templates for residencies, ateliers, gastronomy and programme pages to render responsive `<picture>` blocks with lazy-loaded `<img>` fallbacks and captions.
- Ship theme tooling (Node-based) that ingests source imagery, generates optimised JPEG/WebP variants at predefined breakpoints and writes them to `storytelling/media/`.
- Document the asset workflow (source drop location, build command, naming conventions) across README, concept, checklist and roadmap notes so editors can follow the process.
- Ensure templates gracefully handle profiles without media (no broken layouts, hero copy still renders).

## Technical Considerations
- Reuse the existing taxonomy `KLResourceStory` fields (`image_reference`, `alt_text`) as the canonical identifiers for hero media and accessibility copy.
- Normalise image references so build tooling can emit `{reference}-{width}.{ext}` patterns for `srcset` entries.
- Keep generated variants within reasonable file sizes (≤ 220KB at 1200px width) by tuning Sharp/WebP quality settings and disabling enlargement for smaller originals.
- Provide a shared Smarty partial for profile media to avoid duplicating markup across storytelling templates.
- Allow editors to run the media build script without global installs—`npm install` scoped to the theme directory should wire everything.

## Cross-Team Dependencies
- Coordinate with content/design for preferred breakpoints, crop ratios and caption copy guidelines.
- Ensure operations staff know where to upload original assets (shared drive, DAM) before committing them to the repository or build pipeline.
- Communicate performance guardrails to the devops team so CDN caching/GZIP settings accommodate new media variants.

## Acceptance Criteria
- Presenter payloads expose media arrays (formats, srcset, alt text, caption fallback) for any profile with an `image_reference`.
- Storytelling templates render `<picture>` elements with `source[type=webp]`, JPEG fallbacks, `loading="lazy"`, `decoding="async"`, and optional `<figcaption>`.
- Theme tooling command (`npm run build:hero-media`) produces responsive JPEG/WebP variants for all files in `storytelling/media/source/` and logs output paths.
- Documentation updates describe the hero media workflow, command invocations and where the generated files live.
- Visiting each storytelling page with `_KUNSTORT_STORYTELLING_LAUNCH_` enabled shows existing copy plus hero media blocks when assets are present, with clean fallbacks otherwise.

## Risks & Mitigations
- **Risk:** Editors skip running the build script and commit source-only imagery. **Mitigation:** Document the workflow prominently and add CI linting later to detect missing variants.
- **Risk:** Large source files slow down builds. **Mitigation:** Encourage upstream cropping and leverage Sharp's `withoutEnlargement` plus tuned quality to minimise output size.
- **Risk:** Missing assets produce broken links. **Mitigation:** Presenter skips media payload when variants are absent; templates check before rendering `<picture>`.
