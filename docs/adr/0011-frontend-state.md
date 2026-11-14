<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 11. Use Vue 3 composables for frontend state, without a global store

- Status: accepted
- Date: 2026-11-14

## Context

The frontend is a reference widget: Nextcloud mounts one instance per App Store link shown in Talk, Text and other apps. Each instance receives its card data from the reference's rich object. Nothing needs to be shared between cards.

Many Nextcloud apps use Pinia for state shared across a whole page.

## Decision

- Write components with `<script setup lang="ts">` and the Composition API.
- Use strict TypeScript, with types for the rich object the backend sends.
- Keep state in props and small composables. Do not add Pinia or another store.

## Consequences

- Fewer dependencies and a smaller bundle for a script that loads wherever previews appear.
- Each card is independent, which keeps component tests simple.
- If a later feature shares state across a page, such as a browse page, a store can be added then.
