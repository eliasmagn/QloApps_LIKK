# Storytelling Editorial & Localisation Styleguide

This guide gives writers, translators and designers a single reference for shaping the residency, atelier, gastronomy and programme storytelling pages. It consolidates tone guidance, imagery requirements, localisation expectations and annotated layouts so content drops into the templates without rework.

## 1. Copy Tone & Voice

### 1.1 Narrative Principles
- **Warmly professional:** balance Kunstort Lehnin's artistic residency ethos with operational clarity. Lead with inspiration, close with concrete next steps.
- **Present tense with active verbs:** "Residencies bring together…", "Studios host…". Use past tense sparingly for testimonials.
- **Short paragraphs (2–3 sentences):** keep reading comfort across mobile breakpoints.
- **Explicit calls to inquiry:** end major sections with verbs such as "Plan a stay", "Request a studio", "Book a communal meal" that link to the inquiry flow.
- **Inclusive language:** avoid gendered nouns unless quoting. Default to "artists", "residents", "teams".
- **Avoid sales jargon:** no "exclusive deals" or "limited-time offers"—highlight community, craft and support services instead.

### 1.2 Section-by-Section Guidance
- **Hero straplines:** ≤ 80 characters. Pair a poetic lead with a concrete value (e.g. "Residencies rooted in Brandenburg’s quiet forests" + "Dedicated studios, on-campus lodging and curatorial support").
- **Availability snapshot intros:** start with the next open window (“Next 6-week residency window begins 14 September”). Mention application cadence if applicable.
- **Story blocks:** structure as: context sentence, sensory detail, operational note. Example: "Morning light fills the south-facing studios, where analogue and digital equipment sit side by side, ready for residents to prototype installations without waiting on logistics."
- **Practical info:** use bullet lists for transport, accessibility and contacts. Include estimated travel times from Berlin and Potsdam.
- **Testimonials:** keep to 40–60 words, first-person voice, include role or residency year.

### 1.3 Microcopy Patterns
- Primary CTA labels: "Start an inquiry", "Request availability", "Discuss programme fit".
- Secondary CTA labels: "Download factsheet", "View studio specs", "Email residency team".
- Error or fallback copy: "We’re refreshing availability—drop us a note and we’ll confirm your dates." (pairs with cached availability errors).

## 2. Imagery Specifications

### 2.1 Source & Processing
- Store source assets in `themes/hotel-reservation-theme/storytelling/media/source/` before running the build pipeline.
- Generate responsive variants with `npm run build:hero-media` inside the theme directory. The script outputs `{reference}-{width}.webp/jpg` pairs consumed by the templates.

### 2.2 Composition Guidelines
- **Hero images:** 16:9 crops at a minimum of 2560×1440. Centre line of sight within the upper third. Prefer human presence engaged in work to convey residency life.
- **Section imagery:** 4:3 crops at 1600×1200. Focus on textures (clay, instruments, culinary prep) to complement copy.
- **Colour grading:** maintain natural lighting; avoid heavy filters. Adjust white balance to keep woods neutral and highlight Kunstort’s green surroundings.
- **Accessibility:** capture alt text in the taxonomy story (`alt_text` field). Write complete sentences describing the action (“Ceramic resident glazing a vessel beside a kiln with open forest views”).

### 2.3 File Management
- Name assets with `{resource}-{scene}` (e.g. `atelier-ceramics-wheel`). Avoid dates to keep URLs stable.
- Keep WebP variants under 450 KB; JPEG fallbacks under 650 KB. Re-export via the build pipeline if compressed files exceed thresholds.
- Confirm each asset surfaces in the presenter payload before publishing; missing variants trigger template fallbacks.

## 3. Localisation & Translation Guidance

### 3.1 Supported Languages
- Primary languages: **German (`id_lang = 1`)** and **English (`id_lang = 2`)**. Future languages should follow the same CMS slot pattern.
- Maintain parity between languages—no placeholder text in secondary locales.

### 3.2 Workflow
1. Draft German copy as the source of truth, aligning with Kunstort’s tone.
2. Provide translators with this guide plus the taxonomy data export for terminology consistency (room names, programme titles).
3. Capture translated strings in the CMS pages referenced by `KL_STORY_*` keys. Do **not** hardcode strings in templates.
4. For microcopy keyed in PHP, add translations to the module language files and regenerate caches with `php bin/console prestashop:translations:export hotelreservationsystem` if needed.

### 3.3 Linguistic Notes
- Preserve nouns like "Residency"/"Residency Programme" as proper names when referring to specific Kunstort initiatives.
- Use polite imperative for CTAs in German (“Anfrage starten”). Avoid formal letter closings.
- When referencing measurements, prefer metric units (square metres, seating capacity).
- For dates, use locale-specific format (e.g. `14. September 2024` vs `14 September 2024`). Availability cards already format dates; only free text needs manual adjustment.

### 3.4 Additional Locales
- Add new CMS pages for each locale and register their IDs under the same `KL_STORY_*` configuration keys.
- Provide translators with glossary entries (amenities, equipment) and note that hero alt text requires localisation alongside copy.
- Ensure new locales run through QA on the storytelling templates to validate text expansion across breakpoints.

## 4. Sample Layouts

### 4.1 Residency Landing Structure
1. **Hero Band** – headline, strapline, primary CTA (Inquiry) and optional secondary CTA (Factsheet).
2. **Availability Snapshot** – next window (two cards max), CTA per slot.
3. **Residency Story Blocks** – two columns of narrative copy with inline amenity chips.
4. **Featured Packages** – grouped cards with pricing highlights and inquiry CTAs.
5. **Resident Voices** – rotating testimonial slider with attribution.
6. **Practical Information** – transport, on-site support, contact email.
7. **FAQ Accordion** – 4–6 questions tailored to residencies.

### 4.2 Atelier & Studio Landing
1. Hero featuring active workspace scene.
2. Availability snapshot emphasising hourly/daily slots; include "Request a studio" CTA.
3. Equipment & Capacity grid (pulled from taxonomy) with concise descriptions.
4. Process gallery (optional) highlighting materials or workshop outputs.
5. Programme tie-ins linking to residencies or gastronomy support.
6. Practical info focusing on access, loading, technical support.
7. FAQ emphasising booking lead times, storage, safety briefings.

### 4.3 Gastronomy Landing
1. Hero showcasing communal dining or catering setup.
2. Availability card describing booking notice period and kitchen capacity.
3. Menu highlights with seasonal notes and dietary accommodations.
4. Package pairings (e.g. residency welcome dinners) cross-linked to inquiry.
5. Testimonials from residents or partners about meals.
6. Practical info covering kitchen specs, licensing, allergen management.
7. FAQ covering service hours, staffing, vendor collaborations.

### 4.4 Programme & Events Landing
1. Hero with live rehearsal or performance in situ.
2. Upcoming schedule snapshot with CTA to discuss programme fit.
3. Story blocks outlining curatorial focus and community reach.
4. Speaker/artist roster with short bios (link to taxonomy profiles when available).
5. Support services (tech, marketing) bullet list.
6. Practical info including seating plans, AV inventory, accessibility routes.
7. FAQ for ticketing approach, residency overlap, onsite logistics.

## 5. Editorial Process Checklist
- [ ] Confirm hero media variants exist for each featured story and pass Lighthouse asset budgets.
- [ ] Ensure copy in all locales fits within template components (test at 320 px width).
- [ ] Verify CTAs link to inquiry URLs with appropriate query parameters (`resource_kind`, `arrival`, `departure`, `package_code`).
- [ ] Run terminology QA with taxonomy exports before publishing.
- [ ] Capture before/after screenshots for archives and share in weekly content reviews.

Maintaining this guide ensures every storytelling release lands with a cohesive voice, accessible imagery and predictable layouts while leaving room for future programme-specific embellishments.
