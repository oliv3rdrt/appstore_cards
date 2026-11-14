<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Search;

use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Search\AppStoreSearchProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\ISearchQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AppStoreSearchProviderTest extends TestCase {
	private CatalogueRepository&MockObject $repository;
	private AppStoreSearchProvider $provider;

	protected function setUp(): void {
		$this->repository = $this->createMock(CatalogueRepository::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('imagePath')->willReturn('/apps-extra/appstore_cards/img/app-dark.svg');
		$urlGenerator->method('getAbsoluteURL')->willReturnCallback(static fn (string $url): string => 'https://cloud.example.org' . $url);

		$this->provider = new AppStoreSearchProvider($this->repository, $l10n, $urlGenerator);
	}

	public function testTurnsMatchingAppsIntoResultsThatLinkToTheAppStore(): void {
		$this->repository->expects($this->once())->method('search')->with('deck', 5, 0)->willReturn([self::entry('deck', 'Deck')]);

		$result = $this->provider->search($this->createMock(IUser::class), self::query('  deck ', 5))->jsonSerialize();

		$this->assertSame('App Store', $result['name']);
		$this->assertFalse($result['isPaginated']);
		$entry = $result['entries'][0]->jsonSerialize();
		$this->assertSame('Deck', $entry['title']);
		$this->assertSame('Summary of deck', $entry['subline']);
		$this->assertSame('https://apps.nextcloud.com/apps/deck', $entry['resourceUrl']);
		$this->assertSame('https://cloud.example.org/apps-extra/appstore_cards/img/app-dark.svg', $entry['thumbnailUrl']);
	}

	public function testOffersTheNextPageWhenAPageIsFull(): void {
		$this->repository->method('search')->with('a', 2, 4)
			->willReturn([self::entry('one', 'One'), self::entry('two', 'Two')]);

		$result = $this->provider->search($this->createMock(IUser::class), self::query('a', 2, 4))->jsonSerialize();

		$this->assertTrue($result['isPaginated']);
		$this->assertSame(6, $result['cursor']);
	}

	public function testDoesNotSearchForABlankTerm(): void {
		$this->repository->expects($this->never())->method('search');

		$result = $this->provider->search($this->createMock(IUser::class), self::query('   ', 5))->jsonSerialize();

		$this->assertSame([], $result['entries']);
	}

	private function query(string $term, int $limit, ?int $cursor = null): ISearchQuery&MockObject {
		$query = $this->createMock(ISearchQuery::class);
		$query->method('getTerm')->willReturn($term);
		$query->method('getLimit')->willReturn($limit);
		$query->method('getCursor')->willReturn($cursor);
		return $query;
	}

	private static function entry(string $appId, string $name): CatalogueEntry {
		$entry = new CatalogueEntry();
		$entry->appId = $appId;
		$entry->name = $name;
		$entry->summary = 'Summary of ' . $appId;
		return $entry;
	}
}
