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
	public function testRemovesTheCatalogueScreenshotsSettingsAndJob(): void {
		$repository = $this->createMock(CatalogueRepository::class);
		$repository->expects($this->once())->method('deleteBy')->with([]);
		$screenshots = $this->createMock(ScreenshotCache::class);
		$screenshots->expects($this->once())->method('deleteAll');
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->expects($this->once())->method('deleteApp')->with('appstore_cards');
		$jobList = $this->createMock(IJobList::class);
		$jobList->expects($this->once())->method('remove')->with(RefreshCatalogueJob::class);

		(new RemoveAppData($repository, $screenshots, $appConfig, $jobList))->run($this->createMock(IOutput::class));
	}
}
