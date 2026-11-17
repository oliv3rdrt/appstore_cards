<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Contributing

Thank you for helping. Bug reports, ideas and pull requests are all welcome.

## Reporting a bug or asking for a feature

Open an [issue](https://github.com/oliv3rdrt/appstore_cards/issues/new/choose) and pick the matching form. Please report security issues privately, as described in [SECURITY.md](SECURITY.md).

## Setting up

You need Docker, PHP 8.3 or later with Composer, and Node 24 with npm 11.

1. Start Nextcloud 35 with [nextcloud-docker-dev](https://github.com/nextcloud/nextcloud-docker-dev), for example its `stable35` container.
2. Clone this repository into the container's `apps-extra` folder as `appstore_cards`.
3. Install the dependencies and build the frontend:

   ```bash
   composer install
   npm ci
   npm run dev
   ```

   `npm run watch` rebuilds on every change.
4. Enable the app and fill the catalogue:

   ```bash
   occ app:enable appstore_cards
   occ appstore_cards:refresh
   ```

[docs/architecture.md](docs/architecture.md) explains how the app fits together.

## Making a change

- Keep a pull request to one change, with tests.
- Record a design decision in a new [ADR](docs/adr) and link it from the code it explains.
- Add a line to the `[Unreleased]` section of [CHANGELOG.md](CHANGELOG.md) for anything users or admins notice.
- Write commit messages in the [Conventional Commits](https://www.conventionalcommits.org) format, such as `fix: keep the screenshot switch when the app is disabled`. A check on pull requests enforces it.
- Sign off your commits with `git commit -s` to certify the [Developer Certificate of Origin](https://developercertificate.org).
- Start every new file with an SPDX copyright and licence header, so that the REUSE check passes.

## Checking a change

Run the checks that CI runs:

```bash
composer run lint
composer run cs:check
composer run psalm
composer run test:unit
npm run lint
npm run stylelint
npm run ts:check
npm run test:coverage
npm run build
```

[docs/testing.md](docs/testing.md) explains the integration and end-to-end tests, which need a Nextcloud server. Reviewers go through [docs/review-checklist.md](docs/review-checklist.md), so it is worth reading before you open a pull request.

## Licence

By contributing, you agree that your contribution is licensed under the AGPL-3.0-or-later, like the rest of the app.
