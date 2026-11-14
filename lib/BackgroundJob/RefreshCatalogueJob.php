<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\BackgroundJob;

use OCA\AppStoreCards\Service\CatalogueRefresher;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

/**
 * Refreshes the catalogue once a day, at a quiet time of Nextcloud's choosing.
 *
 * @psalm-api Registered in appinfo/info.xml.
 */
final class RefreshCatalogueJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private readonly CatalogueRefresher $refresher,
		private readonly ScreenshotCache $screenshots,
	) {
		parent::__construct($time);
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->setAllowParallelRuns(false);
	}

	#[\Override]
	protected function run(mixed $argument): void {
		$this->refresher->refresh();
		$this->screenshots->prune();
	}
}
