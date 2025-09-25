# Task Brief: Inquiry Submission Pipeline

## Objective
Deliver a purpose-built inquiry submission experience that replaces the temporary contact-form handoff. Visitors should be able to assemble residency requests, review curated packages and send structured data that the Kanban board can process immediately.

## Key Deliverables
- Design the front-office multi-step inquiry form with guest details, stay preferences, resource selections and optional package add-ons.
- Implement server-side controllers that validate and persist submissions into the `KLInquiry` model with audit trails.
- Provide email confirmations for guests and notifications for staff when new inquiries arrive or require approval.
- Expose JSON endpoints for auto-complete of resources, available dates and package suggestions to keep the UI responsive.

## Technical Considerations
- Reuse the existing inquiry landing route (`?controller=inquiry`) and progressively enhance it into the new flow.
- Rate plan and package suggestions should leverage `KLQuotePricingEngine` to supply indicative pricing within the form summary.
- Guard against spam by integrating hCaptcha or rate limiting via Symfony middleware without introducing third-party tracking.
- Ensure GDPR compliance: allow guests to accept data usage terms and provide a double-opt-in path for newsletter consent (if reintroduced later).

## Cross-Team Dependencies
- Coordinate with legal/compliance on data retention periods and privacy disclosures.
- Align with operations to define minimum data requirements for triaging (arrival window, programme intent, special needs, etc.).

## Acceptance Criteria
- Submissions create inquiry records with structured payloads accessible from the Kanban board without manual re-entry.
- Staff receive actionable notifications (email or future task system) with key request details and quick links into the admin UI.
- Automated tests cover form validation, spam protection and persistence logic.
- Documentation in `docs/blueprints/inquiry-workflow.md` reflects the new submission steps and data model changes.

## Risks & Mitigations
- **Risk:** Complex forms may overwhelm guests. **Mitigation:** Introduce progressive disclosure and autosave drafts tied to session tokens.
- **Risk:** Pricing hints might drift from final quotes. **Mitigation:** Display clear disclaimers and log the pricing snapshot version used at submission time.
