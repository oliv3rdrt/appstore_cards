<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 1. Build rich previews for App Store links

- Status: accepted
- Date: 2026-11-13

## Context

People share Nextcloud apps by pasting App Store links into Talk, Text and other apps. App Store pages carry no OpenGraph tags, so those links fall back to a bare preview with no app details.

No app among the 550 compatible with Nextcloud 35 turns App Store links into previews or lets people search the store from inside Nextcloud (checked on 2026-11-13).

Alternatives considered:

- A Talk bot, for example for stand-ups or release-note digests. It would show webhooks and signature checks, but covers fewer integration points.
- A storage status dashboard widget for Sia. It needs a Sia setup to develop and test.

## Decision

Build App Store Cards:

- App Store links become rich cards (name, summary, rating, latest version, compatible server versions, categories and a screenshot) through a reference provider and a custom reference widget.
- App Store apps can be found from unified search and the Smart Picker, which inserts the app's link.
- An optional dashboard widget lists new and updated apps for the instance's server version.

Data comes only from the public App Store API. The exact v0.1.0 scope is decided separately.

## Consequences

- The app depends on the shape of the App Store API, which returns about 11 MB per server version.
- The only outbound requests go to Nextcloud's own App Store, never to third parties.
- Development and testing need no external accounts or services.
- It touches several core integration points: reference providers, unified search, the Smart Picker, the dashboard, settings, background jobs and the database.
