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
use OCA\AppStoreCards\Service\CatalogueRefresher;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Removes the app's data. Nextcloud runs uninstall steps whenever the app is disabled, including just
 * before `occ app:remove` deletes its files. `occ app:remove --keep-data` skips this step.
 *
 * Only this app's own config keys are deleted, because Nextcloud keeps its own keys under the same
 * app ID, such as "installed_version". The refresh job is removed so that enabling the app again
 * registers it afresh, and it refills the emptied catalogue at the next cron run instead of a day
 * after its last run. The empty table stays, because only Nextcloud may change its record of
 * applied migrations, and a reinstall reuses the table.
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
		foreach ([CatalogueRefresher::CONFIG_ETAG, CatalogueRefresher::CONFIG_CHECKED_AT, ScreenshotCache::CONFIG_ENABLED] as $key) {
			$this->appConfig->deleteKey(Application::APP_ID, $key);
		}
		$this->jobList->remove(RefreshCatalogueJob::class);
	}
}
