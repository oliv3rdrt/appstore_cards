<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\ColumnType;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @psalm-api Run by Nextcloud when the app is installed or upgraded.
 */
#[CreateTable(
	table: 'appstore_cards_apps',
	columns: ['id', 'app_id', 'name', 'summary', 'latest_version', 'platform_spec', 'categories', 'rating', 'rating_count', 'screenshot_url', 'last_modified'],
	description: 'Local copy of the App Store catalogue for this Nextcloud version',
)]
final class Version0100Date20261114120000 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		if ($schema->hasTable('appstore_cards_apps')) {
			return null;
		}

		// Lengths leave room above the longest values in the catalogue (2026-11-14).
		$table = $schema->createTable('appstore_cards_apps');
		$table->addColumn('id', ColumnType::Bigint, ['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
		$table->addColumn('app_id', ColumnType::String, ['notnull' => true, 'length' => 64]);
		$table->addColumn('name', ColumnType::String, ['notnull' => true, 'length' => 255]);
		$table->addColumn('summary', ColumnType::String, ['notnull' => true, 'length' => 512]);
		$table->addColumn('latest_version', ColumnType::String, ['notnull' => true, 'length' => 64]);
		$table->addColumn('platform_spec', ColumnType::String, ['notnull' => true, 'length' => 64]);
		$table->addColumn('categories', ColumnType::Json, ['notnull' => true]);
		$table->addColumn('rating', ColumnType::Float, ['notnull' => true]);
		$table->addColumn('rating_count', ColumnType::Integer, ['notnull' => true, 'unsigned' => true]);
		$table->addColumn('screenshot_url', ColumnType::String, ['notnull' => false, 'length' => 1024]);
		$table->addColumn('last_modified', ColumnType::DatetimeImmutable, ['notnull' => true]);
		// The ORM treats a single #[Id] column as autoincrement, so app_id gets a unique index instead.
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['app_id'], 'appstore_cards_app_id');

		return $schema;
	}
}
