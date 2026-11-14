<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\ORM\Repository;

/**
 * @template-extends Repository<CatalogueEntry>
 * @final Not declared final so that unit tests can mock it.
 */
class CatalogueRepository extends Repository {
	use TTransactional;

	public const string entityClass = CatalogueEntry::class;

	public function findByAppId(string $appId): ?CatalogueEntry {
		try {
			return $this->findOneBy(['appId' => $appId]);
		} catch (DoesNotExistException) {
			return null;
		}
	}

	/**
	 * Finds apps whose name or summary contains the term, ignoring case, ordered by name.
	 *
	 * @param positive-int $limit
	 * @param non-negative-int $offset
	 * @return list<CatalogueEntry>
	 */
	public function search(string $term, int $limit, int $offset = 0): array {
		$query = $this->connection->getTypedQueryBuilder();
		$pattern = $query->createNamedParameter('%' . $this->connection->escapeLikeParameter($term) . '%');
		$query->selectColumns('app_id')
			->from($this->getTableName())
			->where($query->expr()->orX(
				$query->expr()->iLike('name', $pattern),
				$query->expr()->iLike('summary', $pattern),
			))
			->orderBy('name', \SortDirection::Ascending)
			->addOrderBy('app_id', \SortDirection::Ascending)
			->setFirstResult($offset)
			->setMaxResults($limit);
		/** @var list<string> $appIds */
		$appIds = $query->executeQuery()->fetchFirstColumn();

		if ($appIds === []) {
			return [];
		}

		// The ORM loads only by equality or IN, so a second query loads the matches in the same order.
		return iterator_to_array($this->findBy(['appId' => $appIds], ['name' => \SortDirection::Ascending, 'appId' => \SortDirection::Ascending]), false);
	}

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
