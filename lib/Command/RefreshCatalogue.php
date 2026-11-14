<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Command;

use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Service\CatalogueRefresher;
use OCA\AppStoreCards\Service\RefreshResult;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;

/**
 * @psalm-api Registered in appinfo/info.xml and run by occ.
 */
#[AsCommand(
	name: 'appstore_cards:refresh',
	description: 'Refresh the local copy of the App Store catalogue now',
)]
final class RefreshCatalogue {
	public function __construct(
		private readonly CatalogueRefresher $refresher,
		private readonly CatalogueRepository $repository,
	) {
	}

	public function __invoke(IOutput $output): ExitCode {
		$result = $this->refresher->refresh();
		$output->writeln(match ($result) {
			RefreshResult::Disabled => 'App Store access is disabled on this server, so nothing was fetched.',
			RefreshResult::NotModified => sprintf('The catalogue is up to date (%d apps).', $this->repository->count()),
			RefreshResult::Updated => sprintf('The catalogue was updated (%d apps).', $this->repository->count()),
			RefreshResult::Failed => 'The refresh failed. The previous catalogue was kept; see the Nextcloud log for details.',
		});

		return $result === RefreshResult::Failed ? ExitCode::Failure : ExitCode::Success;
	}
}
