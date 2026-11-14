<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Search;

use OCA\AppStoreCards\AppInfo\Application;
use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

/**
 * Searches the local copy of the App Store catalogue, so search terms never leave the server.
 */
final class AppStoreSearchProvider implements IProvider {
	public const string ID = 'appstore_cards';

	public function __construct(
		private readonly CatalogueRepository $repository,
		private readonly IL10N $l10n,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getId(): string {
		return self::ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('App Store');
	}

	/**
	 * Shown after the results of the apps people use every day.
	 */
	#[\Override]
	public function getOrder(string $route, array $routeParameters): ?int {
		return 60;
	}

	#[\Override]
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$term = trim($query->getTerm());
		if ($term === '') {
			return SearchResult::complete($this->getName(), []);
		}

		$limit = max(1, $query->getLimit());
		$cursor = $query->getCursor();
		$offset = is_numeric($cursor) ? max(0, (int)$cursor) : 0;

		$icon = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg'));
		$results = array_map(
			static fn (CatalogueEntry $entry): SearchResultEntry => new SearchResultEntry($icon, $entry->name, $entry->summary, $entry->storeUrl()),
			$this->repository->search($term, $limit, $offset),
		);

		if (count($results) < $limit) {
			return SearchResult::complete($this->getName(), $results);
		}

		return SearchResult::paginated($this->getName(), $results, $offset + count($results));
	}
}
