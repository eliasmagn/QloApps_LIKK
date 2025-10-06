# Inquiry Workflow Blueprint

This document records how the dedicated inquiry pipeline works now that the front-office landing page evolved into a structured submission flow feeding the Kanban board.

## Guest-Facing Experience

The `/index.php?controller=inquiry` route now renders a multi-step form that guides visitors through three stages:

1. **Guest details** – collect the requestor name, email address and optional phone number.
2. **Stay preferences** – capture arrival/departure dates, flexibility flag, party size and the resource kinds (rooms, ateliers, seminar spaces, gastronomy) the guest wants to explore. An autosuggest input accepts specific resource codes while a text area records programme focus.
3. **Programme & submission** – allow guests to pick curated package codes, append additional notes and accept the GDPR data-usage statement (newsletter opt-in is optional). The form summarises the captured data in real time so guests can double check before sending.

Client-side enhancements fetch published resource profiles and active packages via the new JSON endpoint `index.php?fc=module&module=hotelreservationsystem&controller=inquirylookup`. The payload hydrates autosuggest lists without exposing the legacy webservice.

## Server-Side Handling

`InquiryController` now validates submissions and delegates persistence to `KLInquirySubmission`:

- Required fields (name, email, arrival/departure and consent) are validated using PrestaShop validators, including basic date window checks and CSRF tokens.
- `KLInquirySubmission::createFromFront()` builds a `HotelInquiry` record, storing the structured resource request (interests, party size, notes, package codes, flexibility flag) as JSON in `resource_request` and stamping context metadata in `internal_notes`.
- An audit note is added via `HotelInquiryNote::addNote()` documenting the payload for staff review.
- Guests receive an acknowledgement email (`mails/en/kl_inquiry_confirmation.*`) outlining their reference number and captured preferences.
- Staff receive a matching alert (`mails/en/kl_inquiry_staff_alert.*`) with a direct link into the admin inquiry board so they can triage immediately.

`KLInquirySubmission` also emits a hook (`actionKunstortInquirySubmitted`) so future automation (notifications, task creation) can subscribe without modifying the controller.

## JSON Lookups

The module front controller `InquiryLookupModuleFrontController` responds with JSON payloads for:

- **Resources** – published `KLResourceProfile` rows including resource code, kind, capacity snapshot and publication/bookability flags.
- **Packages** – active `KLPackage` entries, optionally filtered by resource kind.
- **Quote preview** – a pass-through to `KLQuotePricingEngine::generateQuote()` for upcoming UI enhancements that will surface price guidance during submission.
- **Testimonials** – cached CMS payloads keyed by storytelling resource type so headless consumers can reuse editorial copy without direct database reads.
- **FAQ** – cached accordion markup sourced from the storytelling CMS keys, filtered by resource type.

Each CMS response includes the requested `resource`, an ordered `resource_groups` array detailing which storytelling groups were evaluated, and a `generated_at` timestamp so clients can display freshness metadata or coordinate client-side caching.

All endpoints require HTTPS and reuse PrestaShop’s `ModuleFrontController` stack so authentication/CSRF behaviour is consistent with other AJAX controllers.

## Next Steps

- Build availability hints into the form by extending the JSON endpoint to surface next-open windows per resource.
- Surface rate-plan descriptions alongside package options to help guests differentiate curated programmes.
- Connect the submission hook to the operations automation module so new inquiries can spawn housekeeping prep tasks once confirmed.
