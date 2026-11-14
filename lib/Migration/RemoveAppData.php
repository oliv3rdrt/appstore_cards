<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Migration;

use OCA\AppStoreCards\AppInfo\Application;
use OCA\AppStoreCards\BackgroundJob\RefreshCatalogueJob;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Removes the app's data when an admin removes the app. Nextcloud deletes only the app's files,
 * and `occ app:remove --keep-data` skips this step.
 *
 * The empty table stays, because only Nextcloud may change its record of applied migrations,
 * and a reinstall reuses the table.
 *
 * @psalm-api Registered in appinfo/info.xml as an uninstall step.
 */
final class RemoveAppData implements IRepairStep {
	public function __construct(
		private readonly CatalogueRepository $repository,
		private readonly ScreenshotCache $screenshots,
		private readonly IAppConfig $appConfig,
		private readonly IJobList $jobList,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Remove the App Store catalogue, cached screenshots, settings and background job';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$this->repository->deleteBy([]);
		$this->screenshots->deleteAll();
		$this->appConfig->deleteApp(Application::APP_ID);
		$this->jobList->remove(RefreshCatalogueJob::class);
	}
}
