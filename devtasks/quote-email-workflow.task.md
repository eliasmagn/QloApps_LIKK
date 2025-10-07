# Quote email workflow

## Summary
The inquiry board needs a resilient workflow for sending quotes to guests. Employees should be able to trigger guest emails directly from the inquiry inspector, with the module handling PDF generation, shareable links, delivery logging and reply configuration. Templates must exist in all supported languages.

## Objectives
- Provide branded HTML and plaintext email templates for quote notifications (`kl_quote_notification`).
- Centralise guest email delivery behind a dispatcher that adds PDF attachments and shareable download links.
- Track when a quote is sent (or re-sent) by flipping the status to `sent` and logging a system note.
- Allow managers to configure the sender/reply-to addresses used for quote notifications.
- Surface a “Send to guest” control in the inquiry inspector and expose the updated status in the UI.

## Acceptance cues
- Triggering “Send to guest” over AJAX attaches the latest PDF, includes a shareable link and updates the quote status to *Sent to guest*.
- Notes show when quotes change status, and repeated sends keep a consistent status without duplicate transitions.
- From/reply-to email addresses can be configured from the board and fall back to shop defaults.
- Email templates render correctly in English and German, with translated copy and placeholders.
- The README, concept, checklist and roadmap mention the new workflow.

## Follow-ups
- Offer a guest-facing landing page for multi-quote threads with acceptance/decline buttons.
- Capture delivery metrics (opens/bounces) via a webhook-friendly provider.
