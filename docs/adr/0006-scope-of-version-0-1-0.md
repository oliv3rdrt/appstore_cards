<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 6. Keep version 0.1.0 to previews, search and their supporting parts

- Status: accepted
- Date: 2026-11-14

## Context

A first release should be small enough to test in every place it appears and to explain line by line, while still showing the core integration points.

Candidate features: rich link previews, unified search, the Smart Picker, a dashboard widget, a page of its own and notifications about app updates.

## Decision

Version 0.1.0 ships:

- Rich previews of App Store links through a reference provider and a custom reference widget.
- App Store search in unified search, reused by the Smart Picker to insert links.
- A background job that keeps a local copy of the catalogue up to date.
- Admin settings through declarative settings.

On the roadmap: a dashboard widget of new and updated apps, a page for browsing the store, and notifications about updates.

The template's example page and example API are removed once the real features replace them.

## Consequences

- The release has one user-facing feature (previews) with one way to find content (search and the Smart Picker).
- The app has no entry in the top navigation.
- The dashboard widget waits for a later release, although it would need little code.
