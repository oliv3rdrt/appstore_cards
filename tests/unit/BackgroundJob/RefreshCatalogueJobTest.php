<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\BackgroundJob;

use OCA\AppStoreCards\BackgroundJob\RefreshCatalogueJob;
use OCA\AppStoreCards\Service\CatalogueRefresher;
use OCA\AppStoreCards\Service\RefreshResult;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\TestCase;

final class RefreshCatalogueJobTest extends TestCase {
	public function testRunsOnceADayAtAQuietTime(): void {
		$job = new RefreshCatalogueJob(
			$this->createMock(ITimeFactory::class),
			$this->createMock(CatalogueRefresher::class),
		);

		$this->assertSame(86400, $job->getInterval());
		$this->assertFalse($job->isTimeSensitive());
	}

	public function testRefreshesTheCatalogue(): void {
		$refresher = $this->createMock(CatalogueRefresher::class);
		$refresher->expects($this->once())->method('refresh')->willReturn(RefreshResult::Updated);
		$job = new RefreshCatalogueJob($this->createMock(ITimeFactory::class), $refresher);

		// run() is what Nextcloud calls when the job is due.
		(fn () => $this->run(null))->call($job);
	}
}
