<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Db;

use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\ORM\Repository;

/**
 * @template-extends Repository<CatalogueEntry>
 * @final Not declared final so that unit tests can mock it.
 */
class CatalogueRepository extends Repository {
	use TTransactional;

	public const string entityClass = CatalogueEntry::class;

	public function count(): int {
		$query = $this->connection->getQueryBuilder();
		$query->select($query->func()->count('*'))
			->from($this->getTableName());

		return (int)$query->executeQuery()->fetchOne();
	}

	/**
	 * Replaces the whole catalogue in one transaction, so a failure keeps the previous copy.
	 *
	 * @param list<CatalogueEntry> $entries
	 */
	public function replaceAll(array $entries): void {
		$this->atomic(function () use ($entries): void {
			$this->deleteBy([]);
			foreach ($entries as $entry) {
				$this->insert($entry);
			}
		}, $this->connection);
	}
}
