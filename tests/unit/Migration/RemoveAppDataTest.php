<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Migration;

use OCA\AppStoreCards\BackgroundJob\RefreshCatalogueJob;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Migration\RemoveAppData;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;

final class RemoveAppDataTest extends TestCase {
	public function testRemovesTheCatalogueScreenshotsJobAndOnlyThisAppsOwnSettings(): void {
		$repository = $this->createMock(CatalogueRepository::class);
		$repository->expects($this->once())->method('deleteBy')->with([]);
		$screenshots = $this->createMock(ScreenshotCache::class);
		$screenshots->expects($this->once())->method('deleteAll');
		$appConfig = $this->createMock(IAppConfig::class);
		$deleted = [];
		$appConfig->method('deleteKey')->willReturnCallback(static function (string $app, string $key) use (&$deleted): void {
			$deleted[] = $app . '.' . $key;
		});
		// Deleting every key of the app would also delete Nextcloud's own, such as "installed_version".
		$appConfig->expects($this->never())->method('deleteApp');
		$jobList = $this->createMock(IJobList::class);
		$jobList->expects($this->once())->method('remove')->with(RefreshCatalogueJob::class);

		(new RemoveAppData($repository, $screenshots, $appConfig, $jobList))->run($this->createMock(IOutput::class));

		$this->assertSame([
			'appstore_cards.catalogue_etag',
			'appstore_cards.catalogue_checked_at',
			'appstore_cards.show_screenshots',
		], $deleted);
	}
}
