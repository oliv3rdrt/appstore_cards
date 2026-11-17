<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Review checklist

What a reviewer checks before approving a pull request. Not every item applies to every change.

## Scope and design

- The change does one thing, and fits the scope in [ADR 6](adr/0006-scope-of-version-0-1-0.md) or a newer ADR.
- A new design decision has its own ADR, and the code links to it.
- It uses only Nextcloud's public API (`OCP\` and `@nextcloud/*` packages), never `OC\` classes or private frontend code ([ADR 3](adr/0003-support-nextcloud-35-only.md)).

## Tests

- New behaviour has a test at the lowest level that can show it: unit, then integration, then end to end ([ADR 13](adr/0013-testing-and-ci.md)).
- A bug fix comes with a test that fails without it.
- Coverage stays above the floors: 90% of PHP lines, 80% of frontend lines ([ADR 19](adr/0019-test-infrastructure.md)).
- Psalm passes at level 1 without new suppressions. An unavoidable suppression says why.

## Security

- Input from requests is validated. The screenshot route takes app IDs, never URLs ([ADR 9](adr/0009-serve-screenshots-through-the-server.md)).
- The server only fetches from the App Store and its image host, over HTTPS, with a timeout and a size limit.
- New routes have the narrowest access attributes and a rate limit.
- Output goes through Vue templates. No `v-html`, and the content security policy is not relaxed.
- No secrets, tokens or personal data in code, logs or fixtures.

## Privacy

- Nothing new leaves the server. If something does, "What leaves the server" in [the architecture](architecture.md) and the README's privacy section say so.
- Nothing about users is stored.
- The admin's screenshot choice is respected everywhere, including after the app is disabled.

## Accessibility, theming and language

- Interactive parts work with a keyboard and have accessible names ([ADR 14](adr/0014-accessibility-theming-and-language.md)).
- Colours come from Nextcloud's CSS variables, so cards follow light, dark and high-contrast themes.
- Every new string is translatable, through `t()` or `n()` in Vue and `IL10N` in PHP.

## Database and performance

- Schema changes are new migrations, never edits to released ones.
- Queries use an index, and nothing loads the whole catalogue on a request.
- The uninstall step still removes everything the change stores ([ADR 18](adr/0018-settings-screenshot-limits-and-removal.md)).

## Documentation and licensing

- `CHANGELOG.md` has a line under `[Unreleased]` for anything users or admins notice.
- The README and `docs/` match the new behaviour.
- New files start with an SPDX header, and REUSE passes.
- Commit messages follow Conventional Commits and are signed off.
