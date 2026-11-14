<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 7. Store a slim copy of the catalogue in a database table

- Status: accepted
- Date: 2026-11-14

## Context

The App Store's list of apps for Nextcloud 35 is about 11 MB and covers 550 apps (measured on 2026-11-13). The fields a card needs (name, summary, rating, latest version, compatible versions, categories and one screenshot) take about 410 bytes per app, or about 225 KB in total.

Previews look apps up by ID, and search filters them by name and summary.

Alternatives considered:

- The distributed cache only. Entries can be evicted at any time, some instances have no cache configured, and the full catalogue is far larger than Memcached's default 1 MB item limit.
- A JSON file in the app's data folder. Every lookup would read and parse the whole file.

`occ app:remove` runs an app's uninstall steps but does not drop its tables by itself.

## Decision

- Keep one row per app in a table of our own, created by a migration, with only the fields the features use.
- Store the refresh state (the App Store's ETag and the time of the last refresh) in app config.
- Read single apps by ID and search with indexed, paginated queries.
- Remove the app's data and settings in an uninstall step.

## Consequences

- Lookups and search never wait for the App Store.
- Schema changes need migrations that work for upgrades and downgrades.
- The uninstall step must be tested, including reinstalling the app afterwards.
