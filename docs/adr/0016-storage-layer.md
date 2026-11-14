<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# 16. Use the Nextcloud 35 ORM for the catalogue table

- Status: accepted
- Date: 2026-11-14

## Context

Nextcloud 35 offers three ways for an app to work with its tables:

- `QBMapper` with `Entity` (since Nextcloud 14). Widely used and documented, with properties accessed through generated getters and setters.
- `ITypedQueryBuilder` (since Nextcloud 34). A query builder whose results carry the types of the selected columns.
- An ORM with attribute-mapped entities and a `Repository` base class (since Nextcloud 35). Entities are plain classes with typed public properties. The repository provides `findBy()`, `findOneBy()`, `insert()`, `update()` and `delete()`.

Nextcloud 35's own `oauth2` and `twofactor_backupcodes` apps already use the ORM, which gives working examples while its documentation is still thin. The `Repository` class relies on private server classes internally, so code built on it can only be tested against a real database.

The app supports Nextcloud 35 only.

## Decision

- Map the catalogue table with an entity marked `#[Entity]`, `#[Id]` and `#[Column]`, using `OCP\DB\Schema\ColumnType`.
- Put all database access in a repository that extends `OCP\AppFramework\ORM\Repository`, using the connection's query builder for the search query.
- Keep creating and changing the schema with migrations.

## Consequences

- Entities are plain, typed objects that are easy to read and to build in tests.
- The repository is tested against real databases, and the code that uses it is tested with a mock.
- When questions come up, the server's own apps are the reference, and anything learned can feed back into the documentation.
- In Nextcloud 35 the ORM treats a single `#[Id]` property without a generator as an autoincrement column and leaves it out of inserts. The table therefore has a numeric `id` primary key and a unique index on `app_id`, instead of using the app ID as the key.
