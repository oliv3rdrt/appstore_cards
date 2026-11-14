<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 17. Run unit tests without a server, and integration tests inside one

- Status: accepted
- Date: 2026-11-14

## Context

The template's test bootstrap loads a whole Nextcloud server and calls the private `\OC_App` and `OC_Hook` classes, so its tests only run inside a server checkout.

The `nextcloud/ocp` package contains the public API but has no autoloader of its own. Most of the app's logic depends only on public interfaces, which can be mocked. The ORM repository and the migrations need a real database.

## Decision

- **Unit tests** in `tests/unit` run without a server. Composer's development autoloader maps the `OCP` and `NCU` namespaces to the `nextcloud/ocp` package, and dependencies are mocked.
- **Integration tests** in `tests/integration` run inside a Nextcloud server checkout against real databases. They cover the repository and the migrations, and arrive with the repository.
- Neither suite uses private server classes directly.

## Consequences

- Unit tests run in seconds on a laptop and in CI without any setup.
- There are two PHPUnit configurations to maintain.
- The database matrix from ADR 13 applies to the integration suite.
