<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 2. Name the app App Store Cards with the ID appstore_cards

- Status: accepted
- Date: 2026-11-13

## Context

App Store rules forbid "Nextcloud" in an app's name. The app ID may contain only lowercase ASCII letters and underscores, must match the app's top-level folder, and becomes the common name of the signing certificate. Once the certificate is issued, the ID cannot change.

Candidates considered: `appstore_cards`, `app_cards` and `app_previews`. All were free on 2026-11-13: no App Store page and no folder in the certificate request repository.

## Decision

- Name: App Store Cards
- App ID: `appstore_cards`
- PHP namespace: `OCA\AppStoreCards`

## Consequences

- The name says what the app does and matches what people would search for.
- The repository and the app folder use the ID.
- Changing the ID later would mean a new certificate and a new App Store listing.
