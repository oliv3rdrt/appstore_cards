<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Service;

use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Service\CatalogueParser;
use PHPUnit\Framework\TestCase;

final class CatalogueParserTest extends TestCase {
	/** @var array<string, CatalogueEntry> */
	private array $entries;

	protected function setUp(): void {
		// A trimmed recording of /api/v1/platform/35.0.3/apps.json, plus fixture apps for edge cases.
		$json = (string)file_get_contents(__DIR__ . '/../../fixtures/apps-35.0.3.json');
		$this->entries = [];
		foreach ((new CatalogueParser())->parse($json) as $entry) {
			$this->entries[$entry->appId] = $entry;
		}
	}

	public function testReadsTheFieldsACardNeeds(): void {
		$deck = $this->entries['deck'];

		$this->assertSame('Deck', $deck->name);
		$this->assertSame('Personal planning and team project organization', $deck->summary);
		$this->assertSame('>=35 <=35', $deck->platformSpec);
		$this->assertSame(['office', 'organization'], $deck->categories);
		$this->assertSame(0.83, $deck->rating);
		$this->assertSame(50, $deck->ratingCount);
		$this->assertStringStartsWith('https://usercontent.apps.nextcloud.com/', (string)$deck->screenshotUrl);
		$this->assertSame('2026-11-05T15:54:19+00:00', $deck->lastModified->format(DATE_ATOM));
	}

	public function testPrefersTheHighestStableReleaseOverEarlierListedBetas(): void {
		// Deck lists 1.19.0-beta.2 and 1.19.0-beta.1 before 1.19.0.
		$this->assertSame('1.19.0', $this->entries['deck']->latestVersion);
	}

	public function testComparesVersionsNumericallyAndIgnoresNewerPreReleases(): void {
		// Releases 1.2.0, 1.10.0, 2.0.0-beta.1 and 1.9.9: a string comparison would pick 1.9.9.
		$this->assertSame('1.10.0', $this->entries['fixture_unsorted']->latestVersion);
	}

	public function testFallsBackToTheHighestPreReleaseWhenThereIsNoStableRelease(): void {
		$this->assertSame('0.1.0-beta.2', $this->entries['fixture_prerelease_only']->latestVersion);
	}

	public function testSkipsAppsWithOnlyNightlyReleasesOrAnInvalidId(): void {
		$this->assertArrayNotHasKey('fixture_nightly_only', $this->entries);
		$this->assertArrayNotHasKey('Invalid-ID', $this->entries);
		$this->assertCount(6, $this->entries);
	}

	public function testShortensValuesToTheirColumnLengths(): void {
		$this->assertSame(255, mb_strlen($this->entries['fixture_long_name']->name));
	}

	public function testLeavesTheScreenshotEmptyWhenAnAppHasNone(): void {
		$this->assertNull($this->entries['fixture_unsorted']->screenshotUrl);
	}

	public function testRejectsInvalidJson(): void {
		$this->expectException(\JsonException::class);
		(new CatalogueParser())->parse('{not json');
	}

	public function testRejectsJsonThatIsNotAList(): void {
		$this->expectException(\UnexpectedValueException::class);
		(new CatalogueParser())->parse('{"detail": "Not found"}');
	}
}
