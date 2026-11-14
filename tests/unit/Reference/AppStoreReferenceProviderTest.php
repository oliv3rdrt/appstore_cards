<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Reference;

use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Reference\AppStoreReferenceProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AppStoreReferenceProviderTest extends TestCase {
	private CatalogueRepository&MockObject $repository;
	private AppStoreReferenceProvider $provider;

	protected function setUp(): void {
		$this->repository = $this->createMock(CatalogueRepository::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('imagePath')->willReturn('/apps-extra/appstore_cards/img/app-dark.svg');
		$urlGenerator->method('getAbsoluteURL')->willReturnCallback(static fn (string $url): string => 'https://cloud.example.org' . $url);

		$this->provider = new AppStoreReferenceProvider($this->repository, $l10n, $urlGenerator);
	}

	/**
	 * @return array<string, array{string, bool}>
	 */
	public static function links(): array {
		return [
			'app page' => ['https://apps.nextcloud.com/apps/deck', true],
			'trailing slash' => ['https://apps.nextcloud.com/apps/deck/', true],
			'query string' => ['https://apps.nextcloud.com/apps/deck?utm_source=talk', true],
			'fragment' => ['https://apps.nextcloud.com/apps/deck#screenshots', true],
			'plain http' => ['http://apps.nextcloud.com/apps/deck', true],
			'other host' => ['https://apps.example.com/apps/deck', false],
			'look-alike host' => ['https://apps.nextcloud.com.example.org/apps/deck', false],
			'store front page' => ['https://apps.nextcloud.com/', false],
			'sub-page of an app' => ['https://apps.nextcloud.com/apps/deck/releases', false],
			'upper-case app ID' => ['https://apps.nextcloud.com/apps/Deck', false],
			'text around the link' => ['see https://apps.nextcloud.com/apps/deck', false],
		];
	}

	#[DataProvider('links')]
	public function testRecognisesOnlyAppStoreAppLinks(string $link, bool $expected): void {
		$this->assertSame($expected, $this->provider->matchReference($link));
	}

	public function testResolvesAKnownAppIntoACard(): void {
		$this->repository->method('findByAppId')->with('deck')->willReturn(self::deck());

		$reference = $this->provider->resolveReference('https://apps.nextcloud.com/apps/deck?utm_source=talk');

		$this->assertNotNull($reference);
		$this->assertSame('Deck', $reference->getTitle());
		$this->assertSame('Personal planning and team project organization', $reference->getDescription());
		$this->assertSame('https://apps.nextcloud.com/apps/deck', $reference->getUrl());
		$this->assertSame(AppStoreReferenceProvider::RICH_OBJECT_TYPE, $reference->getRichObjectType());
		$this->assertSame([
			'appId' => 'deck',
			'name' => 'Deck',
			'summary' => 'Personal planning and team project organization',
			'url' => 'https://apps.nextcloud.com/apps/deck',
			'latestVersion' => '1.19.2',
			'platformSpec' => '>=35.0.2 <=35',
			'categories' => ['office', 'organization'],
			'rating' => 0.83,
			'ratingCount' => 50,
			'lastModified' => '2026-11-05T15:54:19+00:00',
		], $reference->getRichObject());
	}

	public function testLeavesUnknownAppsToTheGenericPreview(): void {
		$this->repository->method('findByAppId')->willReturn(null);

		$this->assertNull($this->provider->resolveReference('https://apps.nextcloud.com/apps/unknown'));
	}

	public function testShowsTheSameCardOnPublicShares(): void {
		$this->repository->method('findByAppId')->willReturn(self::deck());

		$reference = $this->provider->resolveReferencePublic('https://apps.nextcloud.com/apps/deck', 'share-token');

		$this->assertSame('Deck', $reference?->getTitle());
	}

	public function testSharesOneCacheEntryPerAppAcrossUsersAndShares(): void {
		$link = 'https://apps.nextcloud.com/apps/deck';

		$this->assertSame('deck', $this->provider->getCachePrefix($link));
		$this->assertNull($this->provider->getCacheKey($link));
		$this->assertNull($this->provider->getCacheKeyPublic($link, 'share-token'));
	}

	public function testAppearsInTheSmartPickerAndSearchesThroughUnifiedSearch(): void {
		$this->assertSame('appstore_cards', $this->provider->getId());
		$this->assertSame('App Store apps', $this->provider->getTitle());
		$this->assertSame('https://cloud.example.org/apps-extra/appstore_cards/img/app-dark.svg', $this->provider->getIconUrl());
		$this->assertSame(['appstore_cards'], $this->provider->getSupportedSearchProviderIds());
	}

	private static function deck(): CatalogueEntry {
		$entry = new CatalogueEntry();
		$entry->appId = 'deck';
		$entry->name = 'Deck';
		$entry->summary = 'Personal planning and team project organization';
		$entry->latestVersion = '1.19.2';
		$entry->platformSpec = '>=35.0.2 <=35';
		$entry->categories = ['office', 'organization'];
		$entry->rating = 0.83;
		$entry->ratingCount = 50;
		$entry->lastModified = new \DateTimeImmutable('2026-11-05T15:54:19Z');
		return $entry;
	}
}
