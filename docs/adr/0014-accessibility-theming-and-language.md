<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 14. Build on Nextcloud components for accessibility, theming and translation

- Status: accepted
- Date: 2026-11-14

## Context

Cards appear inside other apps, so they must match whichever theme the user has chosen, including dark mode and high contrast, and work with a keyboard and a screen reader.

The App Store provides app names and summaries in up to 45 languages. Storing all of them would multiply the size of the catalogue table.

## Decision

- Use `@nextcloud/vue` components and Nextcloud's CSS variables for colours, spacing and radii, never hard-coded values.
- Use semantic HTML: the card is a link with a clear accessible name, ratings have a text equivalent, and screenshots have alt text such as "Screenshot of Deck".
- Make every string of our own translatable with `t()` and `n()` from `@nextcloud/l10n` in Vue, and `IL10N` in PHP.
- In version 0.1.0, show App Store text in English. Showing it in the user's language is on the roadmap.

## Consequences

- Dark mode and high contrast follow automatically from the theme.
- Keyboard and screen reader checks are part of the end-to-end tests and the release checklist.
- Users of other languages see our interface translated, once translations exist, but App Store text stays in English for now.
