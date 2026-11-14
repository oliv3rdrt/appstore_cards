<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Integration\Db;

use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Server;
use PHPUnit\Framework\TestCase;

/**
 * Runs against the real database. It empties the catalogue table, which the next refresh refills.
 */
final class CatalogueRepositoryTest extends TestCase {
	private CatalogueRepository $repository;

	protected function setUp(): void {
		$this->repository = Server::get(CatalogueRepository::class);
		$this->repository->deleteBy([]);
	}

	protected function tearDown(): void {
		$this->repository->deleteBy([]);
	}

	public function testStoresAndReadsBackEveryField(): void {
		$this->repository->replaceAll([self::entry('deck', screenshotUrl: null)]);

		$deck = $this->repository->findOneBy(['appId' => 'deck']);

		$this->assertSame('Deck', $deck->name);
		$this->assertSame('Summary of deck', $deck->summary);
		$this->assertSame('1.19.0', $deck->latestVersion);
		$this->assertSame('>=35 <=35', $deck->platformSpec);
		$this->assertSame(['office', 'organization'], $deck->categories);
		$this->assertSame(0.83, $deck->rating);
		$this->assertSame(50, $deck->ratingCount);
		$this->assertNull($deck->screenshotUrl);
		$this->assertSame('2026-11-05 15:54:19', $deck->lastModified->format('Y-m-d H:i:s'));
	}

	public function testReplacingTheCatalogueRemovesAppsNoLongerListed(): void {
		$this->repository->replaceAll([self::entry('deck'), self::entry('forms')]);
		$this->repository->replaceAll([self::entry('forms')]);

		$this->assertSame(1, $this->repository->count());
		$this->expectException(DoesNotExistException::class);
		$this->repository->findOneBy(['appId' => 'deck']);
	}

	public function testAFailedReplacementKeepsThePreviousCatalogue(): void {
		$this->repository->replaceAll([self::entry('deck')]);

		try {
			// The duplicate app ID breaks the unique index halfway through the transaction.
			$this->repository->replaceAll([self::entry('forms'), self::entry('forms')]);
			$this->fail('The replacement should have failed');
		} catch (\OCP\DB\Exception) {
		}

		$this->assertSame(1, $this->repository->count());
		$this->assertSame('Deck', $this->repository->findOneBy(['appId' => 'deck'])->name);
	}

	private static function entry(string $appId, ?string $screenshotUrl = 'https://usercontent.apps.nextcloud.com/example'): CatalogueEntry {
		$entry = new CatalogueEntry();
		$entry->appId = $appId;
		$entry->name = ucfirst($appId);
		$entry->summary = 'Summary of ' . $appId;
		$entry->latestVersion = '1.19.0';
		$entry->platformSpec = '>=35 <=35';
		$entry->categories = ['office', 'organization'];
		$entry->rating = 0.83;
		$entry->ratingCount = 50;
		$entry->screenshotUrl = $screenshotUrl;
		$entry->lastModified = new \DateTimeImmutable('2026-11-05T15:54:19Z');
		return $entry;
	}
}
