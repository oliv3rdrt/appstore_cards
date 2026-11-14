<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 9. Serve screenshots through the Nextcloud server

- Status: accepted
- Date: 2026-11-14

## Context

466 of the 550 apps compatible with Nextcloud 35 have screenshots, all hosted on `usercontent.apps.nextcloud.com` (checked on 2026-11-13).

If browsers loaded them directly, every person viewing a card would reveal their IP address to the App Store, and the content security policy would have to allow images from that host.

A server-side image fetch that takes a URL from the caller could be abused to make the server request arbitrary addresses.

## Decision

- An endpoint returns the screenshot for an app ID. The server looks the image URL up in its own table, so callers never supply a URL.
- The server only fetches from the App Store's image host, with a timeout and a size limit, and checks the content type.
- Fetched images are cached in the app's data folder with a total size limit.
- Like Nextcloud's own preview and avatar endpoints, it is a plain controller that returns an image.

## Consequences

- No data from viewers' browsers reaches the App Store.
- The content security policy stays unchanged.
- The app needs code and storage for fetching, caching and cleaning up images.
- Access rules for logged-in users and public shares must be defined and tested.
