# Quote PDF Export

## Overview
Introduce a printable, branded PDF experience for inquiry quotes so the residency team can send polished documents to prospects straight from the Kanban board. The deliverable builds on the existing `KLQuote` payloads created by `KLQuotePricingEngine` and brings them to life through a TCPDF-based renderer. Staff members need quick access to download or email the generated PDFs without leaving the inquiry workspace, and permissions must ensure only authorised employees can send files to guests.

## Goals
- Add a reusable PDF generator class that consumes persisted `KLQuote` records and renders a residency-branded summary (covering guest context, stay window, pricing breakdown and totals).
- Wire the generator into the inquiry admin UI with explicit download and email controls that respect employee permissions.
- Store email templates and behaviours so guests receive the PDFs as attachments alongside a concise cover message.
- Provide automated tests that hash generated PDFs to detect regressions when quote layouts evolve.
- Update documentation artefacts (concept, checklist, README, roadmap) with the new capability.

## Key Tasks
1. Implement `QuotePdfGenerator` using TCPDF/FPDF primitives, ensuring deterministic output for hashing in tests and allowing branding overrides.
2. Extend `AdminHotelInquiriesController` endpoints plus sidebar UI to list inquiry quotes, trigger PDF downloads, and send the files by email when permitted.
3. Add module mail templates (`mails/en/`) that deliver quotes to guests with the generated PDF attached.
4. Capture regression tests that generate a sample PDF and assert the structure via checksum comparison.
5. Refresh project documentation and dev task index to reflect the quote export milestone.

## References
- `modules/hotelreservationsystem/classes/KLQuote.php`
- `modules/hotelreservationsystem/classes/KLQuotePricingEngine.php`
- `modules/hotelreservationsystem/controllers/admin/AdminHotelInquiriesController.php`
- `modules/hotelreservationsystem/views/templates/admin/inquiries/`
- TCPDF library at `tools/tcpdf`
