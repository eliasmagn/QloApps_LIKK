# Kunstort Lehnin Hotel Management (QloApps Fork)

## Overview
This repository is a lean fork of QloApps that is being transformed into a residency- and hotel-management tool for [Kunstort Lehnin](https://kunstortlehnin.de). The objective is to keep the reliable billing and room data models from QloApps while removing all marketplace cruft and re-centering the product on an HS/3-style booking calendar and enquiry-driven workflows.

Key characteristics of the fork:
- 🚫 **Marketplace free** – outbound calls to the QloApps / Prestashop module stores are disabled by default.
- 🧩 **Extensible from source** – custom modules can still be developed and dropped into `modules/` without depending on proprietary services.
- 📆 **Calendar first** – the admin booking view now opens on a resource timeline covering rooms, ateliers, seminar rooms and programme spaces, with the legacy month grid available on demand.
- 📨 **Inquiry workflow** – the legacy checkout paths now forward to an inquiry landing page so staff can confirm curated requests manually.
- 🔌 **Offline-friendly admin** – the Addons and Theme catalogues show local installation guidance instead of remote marketplace iframes.
- 🧭 **Residency navigation** – the front-office header ships with a static residency nav bar and in-house quick links; cart/account/newsletter/social blocks have been excised from both the theme overrides and the module set.
- 🗺️ **Resource showcase** – the home page now renders a residency showcase fed by published resource profiles so rooms, studios, gastronomy and programme spaces surface real capacity and availability cues.
- 🔒 **Legacy API removed** – `/webservice` now responds with HTTP 410 and the back office no longer advertises API key management.
- 💳 **Payments deferred** – legacy bank wire, cheque and PayPal Commerce modules are stripped out so stays are confirmed and settled off-platform.
- 🔄 **Interactive timeline** – bookings can be reallocated by dragging directly on the admin timeline, with conflict checks against disabled rooms and occupancy caps.
- 🗂️ **Inquiry board** – a Kanban-style board replaces cart scaffolding so inquiries can be triaged, assigned, noted and scheduled with reminders (mail notes email guests directly from the board).
- 🌐 **Scoped REST endpoints** – new JSON endpoints cover timeline moves, inquiry status changes and availability lookups without reviving the legacy webservice.
- 🧱 **Resource taxonomy scaffolding** – installing the distribution now seeds dedicated tables and `ObjectModel` classes for resource profiles, capacities, amenities, storytelling copy and change history so upcoming admin UX can plug into structured metadata.
- 🗃️ **Resource profile editor** – the back office ships with a Resource Profiles tab so staff can maintain taxonomy metadata and capacity descriptors for rooms, ateliers and gastronomy spaces ahead of amenity management.

The high-level concept lives in [`concept.md`](concept.md), the multi-phase plan in [`roadmap.md`](roadmap.md), and tactical progress in [`checklist.md`](checklist.md).

### Inquiry Landing

Visiting `/index.php?controller=inquiry` (or any deprecated checkout URL such as `/index.php?controller=order`) shows a lightweight landing page that explains the new manual workflow and links to the contact form until the dedicated inquiry UI ships.

### Legacy PrestaShop Webservice

For security and maintainability the bundled PrestaShop webservice has been retired:

- `webservice/dispatcher.php` immediately returns **410 Gone** without bootstrapping the application.
- Core webservice classes are replaced by stubs so that stray module references fail fast instead of re-enabling the API.
- The **Advanced Parameters → Webservice** tab and related configuration switches are removed from the installer and upgrade scripts.

If you need API access, build explicit modules on top of modern authentication flows rather than reviving the legacy endpoint.

## Admin Booking Timeline
The back-office path **Hotel Reservation System → Booking** now presents a tabbed layout with a top-aligned tab bar instead of the previous side menu:

- **Timeline**: a fast-loading occupancy grid grouped by room type; cells are colour-coded for booked, in-cart, unavailable and partially available stays, and the fetched dataset is cached while the tab remains active to keep reloads instant.
- **Calendar**: the familiar month grid rendered lazily only when the tab is opened.
- **Search & Filters**: the booking form, occupancy selector and availability stats.

Edits performed from the availability list or cart refresh both the timeline and (when initialised) the month grid so that staff always see the latest state.

### Drag-and-Drop Reallocation

Drag any booked cell from its start date to another room/day to trigger a reallocation preview. The timeline will validate the move against:

- Existing bookings on the destination room.
- Room disable ranges (maintenance/out-of-order blocks).
- Room type capacity (adults, children and total guests).

If the move is valid you can confirm the change directly from the drop action. Conflicts are listed inline so staff know why a move is blocked.

### Availability API

`index.php?controller=AdminHotelRoomsBooking&ajax=1&action=lookupAvailability` accepts `id_hotel`, `id_room_type`, `date_from` and `date_to` to return JSON payloads of available, booked and disabled rooms for the requested window. The timeline UI consumes the same endpoint.

## Inquiry Board
Navigate to **Hotel Reservation System → Inquiries** to triage, assign and progress residency requests. Each column represents a stage (Inbox, Qualifying, Awaiting reply, Scheduled, Archived) and cards expose:

- Assignee dropdowns that fire async updates.
- Reminder shortcuts (stored on the inquiry record) for follow-up scheduling.
- Note capture with an optional “mail note” flag that emails the requester and logs the correspondence for the team.

Dragging a card to another column automatically updates its stage (and default status), with conflict messaging if a move is not allowed.

## Resident-Focused Front Office

- The theme header no longer invokes `HOOK_TOP`, `displayTopColumn`, or left/right column placeholders. Instead it renders a static residency navigation bar with anchors for residences, studios & ateliers, dining, programme spaces, and contact along with direct resident-service shortcuts and support contacts.
- The home page now includes a residency showcase block sourced from the new resource taxonomy tables so rooms, ateliers, gastronomy and programme spaces present real metadata (capacity, availability posture, timezone) instead of mock content.
- The hotel description ribbon is rendered inline from configuration values so the chain name and tagline stay visible without needing module hooks.
- Legacy e-commerce widgets (`blockcart`, `blockuserinfo`, `blockmyaccount`, `blocknewsletter`, `blocksocial`) have been removed from both `themes/hotel-reservation-theme/modules/` and `modules/`, and their default hook assignments have been stripped from installation metadata so fresh installs or upgrades never attempt to load them.
- Core controllers now guard newsletter integration points so missing modules do not trigger autoload errors on identity or authentication flows.

## Requirements
The project still runs on the QloApps/PrestaShop stack. For development you will need:

- PHP 8.1 – 8.4 with extensions: PDO_MySQL, cURL, OpenSSL, SOAP, GD, SimpleXML, DOM, Zip, Phar.
- MySQL/MariaDB 5.7 – 8.4.
- Apache or Nginx with HTTPS support.
- Composer (for dependency management) and npm/yarn if you plan to rebuild assets.

Increase PHP limits for development (memory ≥ 256M, max_execution_time ≥ 300) to accommodate module installations and asset builds.

## Getting Started
1. Clone the repository and install dependencies:
   ```bash
   composer install
   ```
2. Create a database and copy `config/settings.inc.php` from the installer or your previous QloApps installation.
3. Make sure `config/defines_custom.inc.php` is loaded (it is included automatically) so that marketplace integrations remain disabled.
4. Run the classic QloApps installer by visiting `/install` in your browser, or migrate an existing database.

After installation you can log into the admin back office at `/admin` (rename the directory for security). The module catalogue will no longer attempt to connect to external stores; only locally available modules are listed.

## Distribution Flags
All Kunstort-specific flags live in `config/defines_custom.inc.php`:

- `_QLOAPP_DISABLE_MARKETPLACE_` – when `true`, disables outbound marketplace requests and UI components.
- `_KUNSTORT_CORE_MODE_` – describes the current interaction model (`'inquiry'` while we move away from carts).

Use these constants in future contributions to gate legacy commerce flows.

## Development Priorities
- Broaden resource annotations (rooms, ateliers, gastronomy) to enrich availability storytelling and reporting. See [`docs/blueprints/resource-taxonomy.md`](docs/blueprints/resource-taxonomy.md) for the canonical data model.
- Replace the front-office room list with storytelling-driven templates and an enquiry form tied to curated packages. Content strategy is outlined in the taxonomy blueprint and will drive copy blocks surfaced on offer pages.
- Introduce configurable rate plans and bundled packages so inquiries can be priced consistently, following [`docs/blueprints/rate-plans-packages.md`](docs/blueprints/rate-plans-packages.md).
- Provide CSV/ICS exports plus internal notifications to support residency and seminar scheduling outside the app, grounded in [`docs/blueprints/operations-automation.md`](docs/blueprints/operations-automation.md).

See [`checklist.md`](checklist.md) for the current implementation status.

## Contributing
This fork welcomes contributions that reinforce the above goals. Keep the codebase libre and avoid reintroducing external marketplaces or proprietary dependencies. Please open issues or discussions before large structural changes.

## License
The original QloApps core remains licensed under OSL-3.0. Custom additions in this fork inherit the same license unless stated otherwise. Review [`LICENSE.md`](LICENSE.md) for details.
