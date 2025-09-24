# Resource Taxonomy Blueprint

This document captures the upcoming taxonomy work for Kunstort Lehnin. It extends the current room and product models so every
campus asset can be described consistently across the back office, front office storytelling, rate plans and exports.

## Objectives
- Model a `resource_kind` that disambiguates rooms, ateliers, seminar spaces, gastronomy units and shared infrastructure.
- Record structured capacity descriptors for people, equipment and spatial constraints.
- Maintain a curated amenities catalogue to power storytelling, task automation and rate/package eligibility.
- Provide translation-ready copy blocks (short name, highlight sentence, long description) for front-office use.
- Keep metadata versioned and auditable so staff can iterate on descriptions without losing history.

## Data Model Sketch

| Table | Purpose |
| --- | --- |
| `ps_kl_resource_profile` | One row per resource (room, atelier, gastronomy unit) with basic taxonomy fields. |
| `ps_kl_resource_capacity` | Structured capacity descriptors keyed by resource (people/adult/child counts, area, equipment limits). |
| `ps_kl_resource_amenity` | Amenity catalogue with category, icon, and localisation keys. |
| `ps_kl_resource_amenity_link` | Many-to-many table linking resources to amenities plus optional notes (e.g. “upon request”). |
| `ps_kl_resource_story` | Localised storytelling blocks for the front office (headline, excerpt, detailed body, image references). |
| `ps_kl_resource_history` | Append-only change log referencing employees and timestamps for metadata edits. |

All tables are prefixed with `_DB_PREFIX_kl_` to stay namespaced. Profiles reference `id_hotel_room` for rooms, and dedicated
IDs for non-room spaces (ateliers, gastronomy) to keep future expansion possible.

### Implementation progress

The first slice of this schema now ships directly in the module install scripts:

- `kl_resource_profile` with code, kind, publication flags, timezone metadata and optional `id_room_type` linkage.
- `kl_resource_capacity` for structured occupancy, equipment and footprint descriptors (single row per resource).
- `kl_resource_amenity` and `kl_resource_amenity_link` to catalogue reusable amenities and attach them to profiles with optional notes/requirements.
- `kl_resource_story` to store translated copy blocks, imagery references and per-language authorship for the front office.
- `kl_resource_history` to log JSON snapshots of each change alongside the triggering employee or automation source.
- `modules/hotelreservationsystem/tools/seed_resource_profiles.php` (also called on install/upgrade) to seed profiles and capacity rows for any existing room types so the taxonomy starts populated.

Each table has a matching `ObjectModel` (`KLResourceProfile`, `KLResourceCapacity`, `KLAmenity`, `KLAmenityLink`, `KLResourceStory`, `KLResourceHistory`) so controller work can hydrate models without bespoke SQL. Helper methods cover common lookups such as amenity indexing, next display order per resource kind and language-aware story retrieval.

## Admin UX Notes
- Extend the **Hotel Reservation System → Rooms** edit form with a new “Profile” tab that surfaces the taxonomy fields.
- Mirror the profile UI in Atelier and Gastronomy controllers (or create a shared Vue component fed by AJAX endpoints).
- Provide amenity management in **Catalog → Amenities** with drag-and-drop categorisation and icon upload.
- Add a change log sidebar showing recent edits with rollback links (soft rollback that clones a previous version).

## API Exposure
- Extend the internal JSON endpoints to include taxonomy metadata alongside availability payloads.
- Add `/api/resource-profile/{id}` endpoints guarded by employee tokens so the front office and housekeeping dashboard can
fetch consistent data.

## Dependencies & Risks
- Requires data migration for existing rooms; create a CLI helper to seed defaults based on current room type labels.
- Amenity taxonomy should avoid duplication with existing feature lists in `ps_hotel_room_features`; we will migrate
and deprecate the old list gradually.
- Storytelling blocks rely on media assets; ensure the media library supports multiple renditions and alt text.

## Deliverables
1. Database migration scripts (install + upgrade) for the new tables.
2. PHP `ObjectModel` classes: `KLResourceProfile`, `KLResourceCapacity`, `KLAmenity`, `KLAmenityLink`, `KLResourceStory`.
3. Admin controllers/forms for managing metadata, including translation fields.
4. REST endpoints exposing taxonomy data for the front office and exports.
5. Unit tests for the new models and data accessors (seeded with fixtures for room/atelier/gastronomy resources).

