<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 8. Refresh the catalogue once a day in a background job

- Status: accepted
- Date: 2026-11-14

## Context

The App Store endpoint `/api/v1/platform/<x.y.z>/apps.json` needs a full version number and supports ETags. A conditional request with `If-None-Match` returns `304 Not Modified` with no body when nothing changed, and the full 11 MB when anything changed. The catalogue changes several times a day.

Many instances would run this app, so the refresh rate multiplies across all of them.

Admins can disable App Store access with the `appstoreenabled` system setting or point to another store with `appstoreurl`.

## Decision

- A `TimedJob` refreshes the catalogue every 24 hours, marked as time-insensitive so Nextcloud can run it off-peak, and never in parallel with itself.
- Each request sends the stored ETag, uses `IClientService` with a 30-second timeout, and asks for the instance's own version.
- If the request fails, the job keeps the last good data and logs a warning.
- The job does nothing when `appstoreenabled` is false and uses `appstoreurl` when it is set.
- An `occ` command runs the same refresh on demand.

## Consequences

- Card details can be up to a day old.
- Most days cost at most one full download per instance.
- Until the first refresh finishes, App Store links fall back to Nextcloud's generic preview.
