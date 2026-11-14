<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 13. Test at three levels, with a database matrix in CI

- Status: accepted
- Date: 2026-11-14

## Context

The app has PHP logic (fetching, parsing, storing and searching the catalogue), Vue components, and integration points that only work inside a running Nextcloud: Text, Talk, the Smart Picker, unified search and public shares.

Nextcloud 35 supports PHP 8.3 to 8.5, MariaDB 10.11 to 12.3, MySQL 8.4 and 9.7, PostgreSQL 14 to 18 and SQLite. The Nextcloud organisation publishes workflow templates for PHPUnit on each database and for Playwright.

## Decision

- **PHP:** PHPUnit tests with mocked dependencies and recorded App Store responses as fixtures. Target at least 90% line coverage for `lib/`, excluding the bootstrap class and migrations.
- **Frontend:** Vitest with Vue Test Utils. Target at least 80% line coverage.
- **End to end:** Playwright tests on a real Nextcloud 35 covering Text, Talk, the Smart Picker, unified search and a public share.
- **CI matrix:**
  - SQLite on PHP 8.3, 8.4 and 8.5.
  - MariaDB 10.11 and 11.8, MySQL 8.4, and PostgreSQL 14 and 18, each on PHP 8.3.
  - Coverage reports published from the SQLite run on PHP 8.3.

## Consequences

- The oldest and newest supported versions of each database are covered, without running every combination.
- CI runs take longer, and recorded fixtures must be refreshed when the App Store's format changes.
- Coverage targets are a floor, not a goal. Tests should describe behaviour.
