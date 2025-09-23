# Kunstort Lehnin Hotel Management (QloApps Fork)

## Overview
This repository is a lean fork of QloApps that is being transformed into a residency- and hotel-management tool for [Kunstort Lehnin](https://kunstortlehnin.de). The objective is to keep the reliable billing and room data models from QloApps while removing all marketplace cruft and re-centering the product on an HS/3-style booking calendar and enquiry-driven workflows.

Key characteristics of the fork:
- 🚫 **Marketplace free** – outbound calls to the QloApps / Prestashop module stores are disabled by default.
- 🧩 **Extensible from source** – custom modules can still be developed and dropped into `modules/` without depending on proprietary services.
- 📆 **Calendar first** – upcoming development focuses on a resource timeline covering rooms, ateliers, seminar rooms and programme spaces.
- 📨 **Inquiry workflow** – the shopping-cart driven booking journey will be replaced with curated requests that staff confirm manually.

The high-level concept and roadmap live in [`concept.md`](concept.md). Tactical progress is tracked in [`checklist.md`](checklist.md).

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
- Transform the admin booking calendar into a per-resource timeline with drag-and-drop management.
- Introduce a unified resource taxonomy (rooms, ateliers, gastronomy) in the database and admin forms.
- Replace the front-office room list with storytelling-driven templates and an enquiry form.
- Provide CSV/ICS exports to support residency and seminar scheduling outside the app.

See [`checklist.md`](checklist.md) for the current implementation status.

## Contributing
This fork welcomes contributions that reinforce the above goals. Keep the codebase libre and avoid reintroducing external marketplaces or proprietary dependencies. Please open issues or discussions before large structural changes.

## License
The original QloApps core remains licensed under OSL-3.0. Custom additions in this fork inherit the same license unless stated otherwise. Review [`LICENSE.md`](LICENSE.md) for details.
