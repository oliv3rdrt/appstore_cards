<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 5. Run development tools natively and Nextcloud in Docker

- Status: accepted
- Date: 2026-11-13

## Context

Linting, static analysis and unit tests run many times a day. Nextcloud itself needs a web server, a database and a cache. The development machine is a Mac with Apple Silicon.

The alternative was to run every tool inside the Nextcloud containers, which avoids local installs but makes each run slower and editor integration harder.

## Decision

- Install PHP, Composer and Node 24 with Homebrew.
- Run Nextcloud with nextcloud-docker-dev.
- Pin Composer's platform to PHP 8.3 so resolved dependencies work on the minimum supported version.

## Consequences

- Linting, Psalm and PHPUnit run quickly, with editor support.
- Local PHP (8.5) is newer than the minimum (8.3), so CI must run on PHP 8.3 to catch syntax or functions that older versions lack.
- Homebrew packages need occasional updates.
