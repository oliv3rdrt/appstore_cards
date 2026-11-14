<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 12. Show cards on public share pages too

- Status: accepted
- Date: 2026-11-14

## Context

App Store links also appear in documents and conversations shared publicly, for example a Text document shared by link. Since Nextcloud 30, a reference provider can implement `IPublicReferenceProvider` to resolve links for visitors who are not logged in.

The catalogue is public information from the App Store. The screenshot endpoint, however, makes the server fetch and store images, so anonymous access could be used to load it.

Nextcloud's AI Contribution Policy expects a feature to be tested in every place it can appear, including public shares.

## Decision

- The reference provider implements `IPublicReferenceProvider` and returns the same card for public share pages.
- The screenshot endpoint is also reachable without logging in, protected by `#[AnonRateLimit]` for visitors and `#[UserRateLimit]` for logged-in users.
- It only serves screenshots of apps in the local table, from the cache where possible.

## Consequences

- Cards look the same for logged-in users and for visitors.
- The anonymous route needs rate limits, tests and a documented limit on cache size.
- End-to-end tests must cover a public share.
- Nextcloud 35 passes the share token to the provider without checking it, and its own `LinkReferenceProvider` ignores it. Because cards only show public catalogue data, this provider ignores it too, rather than adding a share lookup for every card.
