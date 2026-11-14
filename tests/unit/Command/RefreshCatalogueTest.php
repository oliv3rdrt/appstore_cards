<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Command;

use OCA\AppStoreCards\Command\RefreshCatalogue;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Service\CatalogueRefresher;
use OCA\AppStoreCards\Service\RefreshResult;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RefreshCatalogueTest extends TestCase {
	/**
	 * @return array<string, array{RefreshResult, string, ExitCode}>
	 */
	public static function results(): array {
		return [
			'updated' => [RefreshResult::Updated, 'The catalogue was updated (550 apps).', ExitCode::Success],
			'not modified' => [RefreshResult::NotModified, 'The catalogue is up to date (550 apps).', ExitCode::Success],
			'disabled' => [RefreshResult::Disabled, 'App Store access is disabled on this server, so nothing was fetched.', ExitCode::Success],
			'failed' => [RefreshResult::Failed, 'The refresh failed. The previous catalogue was kept; see the Nextcloud log for details.', ExitCode::Failure],
		];
	}

	#[DataProvider('results')]
	public function testReportsTheOutcome(RefreshResult $result, string $message, ExitCode $exitCode): void {
		$refresher = $this->createMock(CatalogueRefresher::class);
		$refresher->method('refresh')->willReturn($result);
		$repository = $this->createMock(CatalogueRepository::class);
		$repository->method('count')->willReturn(550);
		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())->method('writeln')->with($message);

		$this->assertSame($exitCode, (new RefreshCatalogue($refresher, $repository))($output));
	}
}
