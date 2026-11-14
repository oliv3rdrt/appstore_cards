<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 15. Release with semantic versions through a protected workflow

- Status: accepted
- Date: 2026-11-14

## Context

The App Store reads release notes from `CHANGELOG.md`, matching headings such as `## 0.1.0` against the version in `info.xml`. Every release must be signed with the app's private key and uploaded with an App Store token.

The template includes a workflow that builds, signs and uploads a release when a GitHub release is published. It runs in a GitHub environment called `release`.

## Decision

- Follow semantic versioning and keep `CHANGELOG.md` in the Keep a Changelog format.
- Use Conventional Commits, which CI already checks on pull requests.
- To release, update the version and changelog, then publish a GitHub release for the tag `vX.Y.Z`.
- Store the private key and App Store token only as secrets of the `release` environment, which requires the maintainer's approval before each run.
- Never commit the key or the token.

## Consequences

- Releases are repeatable and leave a visible trail on GitHub.
- A stolen GitHub session alone cannot publish a release without the environment approval.
- The release workflow must be reviewed before its first run.
