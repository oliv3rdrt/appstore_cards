<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 19. Measure coverage across both PHP suites, and test end to end in a throwaway container

- Status: accepted
- Date: 2026-11-14

## Context

ADR 13 set the coverage targets, the database matrix and the end-to-end scenarios. ADR 17 split the PHP tests into a unit suite without a server and an integration suite inside one. Neither suite alone covers `lib/`: the repository and the event listener only run against a real server.

The Nextcloud organisation publishes `@nextcloud/e2e-test-server`, which starts a Nextcloud container on a chosen server branch, mounts the app and installs other apps. If checking out the branch fails, the container keeps running on master without failing. It keeps the branch only in the process that started the container, and creating users in parallel fails on its SQLite database.

The organisation's coverage workflows upload reports to Codecov, a third-party service.

## Decision

- **PHP coverage:** the SQLite job on PHP 8.3 runs both suites with PCOV, merges the results with `phpcov`, and fails below 90% of lines.
- **Frontend coverage:** Vitest fails below 80% of lines.
- **Reports:** both reports are kept as GitHub Actions artifacts, and the PHP summary is shown on the workflow run. Nothing is sent to a third party.
- **End to end:** Playwright tests run on `@nextcloud/e2e-test-server` with the server's `stable35` branch, one test at a time. Setup checks that the server really is Nextcloud 35, installs Text and Viewer from the same branch and Talk from the App Store, and downloads the real catalogue once. Each test uses a new user.

## Consequences

- One number covers all PHP code, whichever suite exercises it.
- End-to-end tests depend on GitHub, the App Store and Docker being reachable. A failed download stops the run instead of testing the wrong version.
- The end-to-end suite takes about a minute once the container is running.
- Adding Codecov later only needs an upload step and a token.
