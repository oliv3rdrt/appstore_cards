<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 18. One admin setting, bounded screenshots, and data removal on uninstall

- Status: accepted
- Date: 2026-11-14

## Context

ADRs 8, 9 and 12 left three details open: what admins can change, how large screenshots and their cache may grow, and what removing the app leaves behind.

Measured on 2026-11-14, App Store screenshots are 110 to 343 kB and are all served as `application/octet-stream`. The `smallThumbnail` field is empty.

Nextcloud caches resolved links on the server and tells browsers to keep them for an hour. Removing an app deletes only its files; its uninstall steps must clean up the rest.

The Nextcloud 35 settings page removes "<app ID>_" from a form ID before saving, while the server compares the unmodified ID. A form whose ID starts with its app ID therefore never saves, and the request still succeeds.

## Decision

- **Settings:** one admin switch, "Show screenshots on cards", on by default. The form stores a boolean through `IDeclarativeSettingsFormWithHandlers`, clears the cached links of every app when the switch changes, and shows the catalogue status in its description. The form ID is `admin`, without the app prefix.
- **Screenshots:** at most 2 MB each, recognised from their first bytes as PNG, JPEG, GIF or WebP, never SVG. The cache keeps at most 100 MB and the daily job removes screenshots of apps that left the catalogue, then the oldest, until it is within that limit. The route answers 60 requests a minute per visitor and 120 per logged-in user.
- **Removal:** an uninstall step deletes the catalogue rows, the cached screenshots, the app config and the refresh job. The empty table stays, because only Nextcloud may change its record of applied migrations, and a reinstall reuses it.

## Consequences

- Turning screenshots off stops the server contacting the image host straight away, while browsers may show the old card for up to an hour.
- The screenshot cache cannot grow without bound, and a changed screenshot replaces the old one.
- `occ app:remove` leaves no data behind apart from an empty table. `occ app:remove --keep-data` skips the uninstall step.
- The form ID workaround is worth reporting upstream.
