# Rate Plans & Packages Blueprint

This blueprint outlines how flexible price plans and packages will integrate with the Kunstort Lehnin fork.

## Goals
- Support configurable rate plans (BAR, corporate, residency programmes, cultural partners) with seasonal overrides.
- Allow packages that bundle accommodation, atelier usage, catering and experiences into weekly or event-based offers.
- Keep pricing decisions traceable by logging applied rules, adjustments and approvals.
- Expose plans/packages to the inquiry workflow so staff can quote consistent pricing quickly.

## Core Entities
| Entity | Description |
| --- | --- |
| `KLRatePlan` | Defines plan code, display name, eligibility rules (resource kinds, segments), pricing method and cancellation policy. |
| `KLRatePlanSeason` | Date-bounded adjustments (percentage or fixed modifiers) plus minimum stay, occupancy or lead-time rules. |
| `KLPackage` | Bundled offer that references one or more rate plans, resources, services and optional experience slots. |
| `KLPackageComponent` | Itemised line describing what is included (lodging night, atelier session, meal service, equipment). |
| `KLQuote` | Stores generated quotes linked to inquiries, capturing selected plan/package, price breakdown and approval status. |

## Pricing Engine Sketch
1. Staff selects a resource and dates inside the inquiry board or booking timeline.
2. The system fetches applicable rate plans (`resource_kind`, corporate tag, residency programme, etc.).
3. Staff chooses a rate plan or package. The pricing engine:
   - Applies base price (nightly or weekly) from the plan.
   - Applies seasonal overrides and adjustments.
   - Adds package components (atelier usage, catering) and calculates net totals.
   - Generates tax breakdown according to existing QloApps configuration.
4. A `KLQuote` record is stored with the computed lines. Quotes can be revised with audit trail of manual edits.

## Admin Interfaces
- **Rate Plans**: CRUD grid with plan metadata, eligibility conditions (resource kinds, residency tags), and seasonal modifiers.
- **Packages**: Builder UI where staff assemble components (resource nights, catering, experiences) via drag-and-drop.
- **Quote Review**: Inquiry detail sidebar that lists generated quotes, allows approval, copy-to-email and export to PDF.

## Front-Office Storytelling
- Offer pages showcase curated packages with imagery, highlight copy and availability cues drawn from the taxonomy tables.
- CTA buttons trigger the inquiry modal pre-filled with the selected package and preferred dates.

## Technical Considerations
- Store monetary values in the default currency minor units to avoid floating point drift.
- Reuse PrestaShop `CartRule` style condition builders where possible, but keep evaluation server-side inside dedicated classes.
- Ensure packages gracefully degrade when included amenities are unavailable for selected dates (surface warnings, allow manual overrides).
- Localise plan/package descriptions using the existing translation infrastructure.

## Deliverables
1. Database tables `_DB_PREFIX_kl_rate_plan`, `_DB_PREFIX_kl_rate_plan_lang`, `_DB_PREFIX_kl_rate_plan_season`, `_DB_PREFIX_kl_package`, `_DB_PREFIX_kl_package_lang`, `_DB_PREFIX_kl_package_component`, `_DB_PREFIX_kl_quote`.
2. ObjectModel classes for each entity and services for rule evaluation.
3. Admin controllers and Vue components for plan/package management.
4. Inquiry board integration for quote generation and tracking.
5. Exportable PDF quote template using TCPDF/FPDF.
6. Automated tests covering rate selection, seasonal pricing, package totals and quote persistence.

