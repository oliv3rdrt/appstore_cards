<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 10. Use OCS endpoints with OpenAPI, and keep the app's own API small

- Status: accepted
- Date: 2026-11-14

## Context

Nextcloud recommends OCS controllers for data endpoints. JSON endpoints under `index.php` have been deprecated since Nextcloud 30. OCS endpoints use a standard response envelope and require the `OCS-APIRequest: true` header, which protects them against cross-site requests.

The template already generates `openapi.json` from controller attributes and types, and CI fails when the file is out of date.

Most data reaches the frontend through Nextcloud's own interfaces: previews through the reference provider's rich object, search through unified search, and settings through declarative settings.

## Decision

- Any data endpoint is an OCS controller method with `#[ApiRoute]` and explicit access attributes, documented by the generated OpenAPI file.
- Endpoints that return images, such as screenshots, are plain controllers, following Nextcloud's own preview and avatar endpoints.
- The app only adds endpoints where Nextcloud offers no interface.

## Consequences

- Version 0.1.0 may need no OCS endpoint at all. This decision sets the rule for any that are added.
- Every endpoint's access rules are visible on the method and in the OpenAPI file.
