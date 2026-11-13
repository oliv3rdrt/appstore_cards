<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 3. Support Nextcloud 35 only

- Status: accepted
- Date: 2026-11-13

## Context

Nextcloud 35 was released on 2026-09-16 and needs PHP 8.3 or later. Versions 33 and 34 are still supported.

Nextcloud 35 adds public APIs that older versions lack: `ITypedQueryBuilder`, an ORM with attribute-based entities, attribute-based `occ` commands (`#[AsCommand]`), the `ColumnType` enum and controller parameters validated from their declared types.

App Store rules allow a release's `max-version` to be at most the latest Nextcloud release plus one.

This is a new app with no existing installations to keep working.

## Decision

Support Nextcloud 35 only: `min-version="35"` and `max-version="36"`, with PHP 8.3 as the minimum.

## Consequences

- The app can use Nextcloud 35's newest APIs without compatibility code.
- The CI matrix covers one server version across PHP 8.3, 8.4 and 8.5.
- Instances on Nextcloud 33 or 34 cannot install it.
- Some of the new APIs have little documentation yet, so they need extra care and tests.
- Each new Nextcloud major release means testing the app and raising `max-version`.
