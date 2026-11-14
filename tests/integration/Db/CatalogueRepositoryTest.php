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

	public function testFindsAnAppById(): void {
		$this->repository->replaceAll([self::entry('deck')]);

		$this->assertSame('Deck', $this->repository->findByAppId('deck')?->name);
		$this->assertNull($this->repository->findByAppId('unknown'));
	}

	public function testSearchIgnoresCaseInNamesAndSummaries(): void {
		$this->repository->replaceAll([
			self::entry('deck', summary: 'Kanban boards'),
			self::entry('forms', summary: 'Surveys and questionnaires'),
		]);

		$this->assertSame(['deck'], self::ids($this->repository->search('DECK', 10)));
		$this->assertSame(['deck'], self::ids($this->repository->search('kanban', 10)));
		$this->assertSame([], self::ids($this->repository->search('calendar', 10)));
	}

	public function testSearchTreatsLikeWildcardsAsPlainText(): void {
		$this->repository->replaceAll([
			self::entry('percent', summary: 'Shows 100% of your files'),
			self::entry('underscore', summary: 'Uses snake_case names'),
			self::entry('plain', summary: 'Shows 1000 files in snakeXcase'),
		]);

		$this->assertSame(['percent'], self::ids($this->repository->search('100%', 10)));
		$this->assertSame(['underscore'], self::ids($this->repository->search('snake_case', 10)));
	}

	public function testSearchPagesThroughResultsInNameOrder(): void {
		$this->repository->replaceAll([
			self::entry('cherry', summary: 'Fruit'),
			self::entry('apple', summary: 'Fruit'),
			self::entry('banana', summary: 'Fruit'),
		]);

		$this->assertSame(['apple', 'banana'], self::ids($this->repository->search('fruit', 2)));
		$this->assertSame(['cherry'], self::ids($this->repository->search('fruit', 2, 2)));
	}

	/**
	 * @param list<CatalogueEntry> $entries
	 * @return list<string>
	 */
	private static function ids(array $entries): array {
		return array_map(static fn (CatalogueEntry $entry): string => $entry->appId, $entries);
	}

	private static function entry(
		string $appId,
		?string $screenshotUrl = 'https://usercontent.apps.nextcloud.com/example',
		?string $summary = null,
	): CatalogueEntry {
		$entry = new CatalogueEntry();
		$entry->appId = $appId;
		$entry->name = ucfirst($appId);
		$entry->summary = $summary ?? 'Summary of ' . $appId;
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
