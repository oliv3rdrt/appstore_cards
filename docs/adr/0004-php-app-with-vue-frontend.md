<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 4. Build a PHP app with a Vue 3 frontend, not an ExApp

- Status: accepted
- Date: 2026-11-13

## Context

Reference providers, unified search providers, Smart Picker entries and dashboard widgets are PHP interfaces in Nextcloud's public API, registered when the app boots.

ExApps run in their own container and talk to Nextcloud through AppAPI, which needs a deploy daemon to develop and test.

## Decision

Build a classic PHP app that uses only the public `OCP` API, with a Vue 3 and TypeScript frontend built by Vite.

## Consequences

- Every integration point uses its native interface directly.
- Development and testing need one Nextcloud container and no extra services.
- The app shows both PHP backend and Vue frontend work.
- There is no Python in the app.
