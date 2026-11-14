<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Integration\Migration;

use OCA\AppStoreCards\BackgroundJob\RefreshCatalogueJob;
use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Migration\RemoveAppData;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Server;
use PHPUnit\Framework\TestCase;

/**
 * Runs the uninstall step against the real database. Afterwards, the next refresh refills the catalogue.
 */
final class RemoveAppDataTest extends TestCase {
	public function testLeavesNoCatalogueSettingsOrJobBehind(): void {
		$repository = Server::get(CatalogueRepository::class);
		$appConfig = Server::get(IAppConfig::class);
		$jobList = Server::get(IJobList::class);
		$repository->replaceAll([self::entry()]);
		$jobList->add(RefreshCatalogueJob::class);
		$appConfig->setValueString('appstore_cards', 'catalogue_etag', '"etag"');
		$appConfig->setValueBool('appstore_cards', 'show_screenshots', false);

		Server::get(RemoveAppData::class)->run($this->createMock(IOutput::class));

		$this->assertSame(0, $repository->count());
		$this->assertFalse($appConfig->hasKey('appstore_cards', 'catalogue_etag'));
		$this->assertFalse($appConfig->hasKey('appstore_cards', 'show_screenshots'));
		$this->assertFalse($jobList->has(RefreshCatalogueJob::class, null));
	}

	public function testKeepsNextcloudsOwnRecordOfTheApp(): void {
		$appConfig = Server::get(IAppConfig::class);

		Server::get(RemoveAppData::class)->run($this->createMock(IOutput::class));

		// Nextcloud stores these under the app's ID too, and needs them to enable the app again.
		$this->assertNotSame('', $appConfig->getValueString('appstore_cards', 'installed_version'));
		$this->assertSame('yes', $appConfig->getValueString('appstore_cards', 'enabled'));
	}

	private static function entry(): CatalogueEntry {
		$entry = new CatalogueEntry();
		$entry->appId = 'deck';
		$entry->name = 'Deck';
		$entry->summary = 'Summary';
		$entry->latestVersion = '1.19.2';
		$entry->platformSpec = '>=35 <=35';
		$entry->categories = [];
		$entry->rating = 0.5;
		$entry->ratingCount = 0;
		$entry->lastModified = new \DateTimeImmutable('2026-11-05T15:54:19Z');
		return $entry;
	}
}
