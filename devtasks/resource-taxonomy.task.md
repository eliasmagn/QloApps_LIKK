# Task Brief: Resource Taxonomy Admin Refinements

## Objective
Stabilise and enrich the new resource taxonomy tooling so staff can confidently maintain rooms, ateliers, gastronomy and programme spaces without database intervention. The goal is to make profile metadata, capacities, amenities and storytelling copy manageable directly inside the back office.

## Key Deliverables
- Expand the **Resource Profiles** tab with inline validation cues, change history context and empty-state guidance.
- Implement amenity assignment UI that links curated amenity codes to resource profiles with drag-and-drop or checkbox workflows.
- Provide preview blocks (or quick links) that show how taxonomy metadata surfaces on the front-office showcase and upcoming storytelling templates.
- Harden CLI/installer seeders to cover new resource kinds and retry transient database issues gracefully.

## Technical Considerations
- Reuse existing `KLResourceProfile`, `KLResourceCapacity`, `KLResourceAmenity` models. Extend them rather than introducing parallel tables.
- Validate capacity changes against existing bookings to avoid overcommitting. If conflicts occur, surface actionable warnings instead of silent failures.
- Keep amenity assignments translatable by storing display strings in translation domains; surface missing translations in the UI.
- Ensure AJAX endpoints respect `_QLOAPP_DISABLE_MARKETPLACE_` and `_KUNSTORT_CORE_MODE_` flags.

## Cross-Team Dependencies
- Coordinate with the storytelling/content team on amenity taxonomy naming and iconography.
- Align with operations on capacity nomenclature to match housekeeping and seminar planning language.

## Acceptance Criteria
- Staff can add/edit/delete amenity links per resource profile without manual SQL and see confirmation toasts.
- Capacity edits that would violate booked stays are blocked with clear error messaging referencing the affected reservation IDs.
- Documentation in `docs/blueprints/resource-taxonomy.md` is updated with new fields and UI flows.
- Automated tests cover the new amenity assignment endpoints and capacity validation rules.

## Risks & Mitigations
- **Risk:** Heavy taxonomy pages may load slowly with large datasets. **Mitigation:** Implement pagination and async searches.
- **Risk:** Conflicting taxonomy edits between staff. **Mitigation:** Introduce optimistic locking or last-updated timestamps with conflict dialogs.
