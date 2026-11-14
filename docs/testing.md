<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Testing

The app is tested at three levels. [ADR 13](adr/0013-testing-and-ci.md), [ADR 17](adr/0017-unit-and-integration-tests.md) and [ADR 19](adr/0019-test-infrastructure.md) explain why.

| Suite | Runs | Command |
|-------|------|---------|
| PHP unit | Without a server, with mocks | `composer run test:unit` |
| PHP integration | Inside a Nextcloud server, against its database | `composer run test:integration` |
| Frontend | Vitest in jsdom | `npm test`, or `npm run test:coverage` |
| End to end | Playwright against a throwaway Nextcloud 35 container | `npm run playwright` |

## PHP integration tests

Run them inside the server the app is installed in. With [nextcloud-docker-dev](https://github.com/nextcloud/nextcloud-docker-dev) and the app in `apps-extra`:

```bash
docker compose exec -T -u www-data -w /var/www/html/apps-extra/appstore_cards stable35 php vendor/bin/phpunit -c tests/phpunit.integration.xml
```

They empty the catalogue, so refill it afterwards with `occ appstore_cards:refresh`.

## End-to-end tests

The tests need Docker, a built frontend (`npm run build`) and Playwright's browser (`npx playwright install chromium --only-shell`). The first run downloads the server image and checks out the `stable35` branch, which takes a few minutes.

Docker Desktop on macOS may not provide `/var/run/docker.sock`. Point the test server at Docker's own socket instead:

```bash
DOCKER_SOCKET="$HOME/.docker/run/docker.sock" npm run playwright
```

Outside CI, the container keeps running after the tests so that the next run starts quickly. Remove it with:

```bash
docker rm -f nextcloud-e2e-test-server_appstore_cards
```

## Coverage

CI fails below 90% line coverage for `lib/` across both PHP suites, and below 80% for the frontend. The reports are attached to the workflow runs as artifacts.
