<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-11-24

### Added

- Cards for links to App Store apps in Talk, Text and anywhere else Nextcloud shows link previews, including public share pages. A card shows the app's screenshot, summary, latest version, supported Nextcloud versions, rating and categories.
- App Store apps in unified search and the Smart Picker.
- A local copy of the App Store catalogue for this Nextcloud version, updated daily by a background job or straight away with `occ appstore_cards:refresh`.
- Screenshots downloaded once by the server and cached, up to 100 MB in total, and an admin switch to turn them off.
- Removal of the catalogue, cached screenshots and background job when the app is disabled or removed.

[Unreleased]: https://github.com/oliv3rdrt/appstore_cards/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/oliv3rdrt/appstore_cards/releases/tag/v0.1.0
