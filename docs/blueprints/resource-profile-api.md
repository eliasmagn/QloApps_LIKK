# Resource Profile API Notes

## Overview
The resource profile API exposes published rooms, ateliers, gastronomy spaces and programme venues as JSON for internal dashboards and integrations. It reuses `HotelReservationSystemStorytellingPresenter` so consumers receive the same enriched payloads that power storytelling landings.

## Authentication
- Configure `_KUNSTORT_RESOURCE_API_TOKEN_` in `config/defines_custom.inc.php` (or via an environment include) with a shared secret.
- Clients must send `Authorization: Bearer <token>` or append `?token=<token>` when calling the controller.
- Missing tokens return `401`, invalid tokens return `403`, and unset constants return `503` with guidance.

Rotate the token regularly; coordinate with downstream services before changing the value.

## Endpoints
Base path: `index.php?controller=resourceprofileapi`

### List
```
GET /index.php?controller=resourceprofileapi
```

Optional query parameters:
- `resource_kinds=room,atelier,…` to scope results.
- `id_lang` / `id_shop` to override the context (defaults to current language/shop or global defaults).

Response payload:
- `profiles` – array of published profiles with:
  - `id_kl_resource_profile`, `resource_code`, `resource_kind`, `display_order`, `is_bookable`, etc.
  - `capacity` block (adults/children/total/seated/standing/floor_area_sqm/ceiling_height_m) and `capacity_notes`.
  - `amenities` array with catalogue metadata and notes.
  - `story` object (headline/excerpt/body/media references).
  - `next_availability` – normalised slot (ISO8601 `start`/`end`, inquiry URL/query, human label) or `null`.
- `availability` – aggregated snapshot grouped by resource kind (status/message/slots/groups) mirroring the storytelling presenter.
- `resource_kinds` – echo of the active filters (empty array when none supplied).
- `generated_at` – ISO 8601 timestamp.

### Detail
```
GET /index.php?controller=resourceprofileapi&action=detail&resource_code=RES-001
```

Query parameters:
- `resource_code` **or** `id_kl_resource_profile` (required).
- `id_lang` / `id_shop` (optional overrides).

Response payload:
- `profile` – the matching entry with the same fields listed for the list endpoint.
- `availability` – snapshot filtered to the profile's resource kind.
- `generated_at` – ISO 8601 timestamp.

Missing resources return `404` with an `error` object.

## Error responses
Errors always return `application/json` with:
```
{
  "error": {
    "code": 401,
    "message": "Missing authentication token."
  }
}
```

Use the HTTP status code to differentiate between authentication (`401`/`403`), configuration (`503`) and not-found (`404`) states.

## Testing
- Use `curl` with the bearer token to smoke-test list and detail endpoints:
  ```bash
  curl -H "Authorization: Bearer $TOKEN" \ 
       "https://example.test/index.php?controller=resourceprofileapi&resource_kinds=room"
  ```
- Validate output while toggling `id_lang`/`id_shop` to confirm localisation.
- Exercise error branches by omitting the header or requesting unknown `resource_code` values.

## Future extensions
- Add pagination when profile counts grow beyond a few dozen entries.
- Introduce lightweight query parameters for availability horizon or inclusion of unpublished drafts (role-gated).
- Consider HMAC-signed short-lived tokens when exposing the API beyond internal networks.
