<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Service;

use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Service\CatalogueParser;
use OCA\AppStoreCards\Service\CatalogueRefresher;
use OCA\AppStoreCards\Service\RefreshResult;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class CatalogueRefresherTest extends TestCase {
	private const string URL = 'https://apps.nextcloud.com/api/v1/platform/35.0.3/apps.json';

	private IClient&MockObject $client;
	private IClientService&MockObject $clientService;
	private IAppConfig&MockObject $appConfig;
	private CatalogueRepository&MockObject $repository;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		$this->client = $this->createMock(IClient::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->clientService->method('newClient')->willReturn($this->client);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->repository = $this->createMock(CatalogueRepository::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	public function testDoesNothingWhenAppStoreAccessIsDisabled(): void {
		$this->clientService->expects($this->never())->method('newClient');

		$refresher = $this->refresher(['appstoreenabled' => false]);

		$this->assertSame(RefreshResult::Disabled, $refresher->refresh());
	}

	public function testDownloadsAndStoresTheCatalogueWithItsEtag(): void {
		$this->repository->method('count')->willReturn(0);
		$this->client->expects($this->once())->method('get')
			->with(self::URL, ['timeout' => 30, 'headers' => []])
			->willReturn($this->response(200, self::fixture(), '"etag-2"'));
		$this->repository->expects($this->once())->method('replaceAll')
			->with($this->callback(static fn (array $entries): bool => count($entries) === 6
				&& $entries[0] instanceof CatalogueEntry && $entries[0]->appId === 'deck'));
		$this->appConfig->expects($this->once())->method('setValueString')
			->with('appstore_cards', CatalogueRefresher::CONFIG_ETAG, '"etag-2"');
		$this->appConfig->expects($this->once())->method('setValueInt')
			->with('appstore_cards', CatalogueRefresher::CONFIG_CHECKED_AT, 1_800_000_000);

		$this->assertSame(RefreshResult::Updated, $this->refresher()->refresh());
	}

	public function testSendsTheStoredEtagAndKeepsDataWhenNothingChanged(): void {
		$this->repository->method('count')->willReturn(550);
		$this->appConfig->method('getValueString')
			->with('appstore_cards', CatalogueRefresher::CONFIG_ETAG)
			->willReturn('"etag-1"');
		$this->client->expects($this->once())->method('get')
			->with(self::URL, ['timeout' => 30, 'headers' => ['If-None-Match' => '"etag-1"']])
			->willReturn($this->response(304, '', '"etag-1"'));
		$this->repository->expects($this->never())->method('replaceAll');
		$this->appConfig->expects($this->once())->method('setValueInt');

		$this->assertSame(RefreshResult::NotModified, $this->refresher()->refresh());
	}

	public function testIgnoresTheStoredEtagWhenTheCatalogueIsEmpty(): void {
		$this->repository->method('count')->willReturn(0);
		$this->appConfig->expects($this->never())->method('getValueString');
		$this->client->expects($this->once())->method('get')
			->with(self::URL, ['timeout' => 30, 'headers' => []])
			->willReturn($this->response(200, self::fixture(), '"etag-2"'));

		$this->assertSame(RefreshResult::Updated, $this->refresher()->refresh());
	}

	public function testUsesTheAdminsAppStoreUrl(): void {
		$this->client->expects($this->once())->method('get')
			->with('https://store.example.org/api/v1/platform/35.0.3/apps.json')
			->willReturn($this->response(304, '', ''));

		$refresher = $this->refresher(['appstoreurl' => 'https://store.example.org/api/v1/']);

		$this->assertSame(RefreshResult::NotModified, $refresher->refresh());
	}

	public function testKeepsThePreviousCatalogueWhenTheRequestFails(): void {
		$this->client->method('get')->willThrowException(new \RuntimeException('Connection timed out'));
		$this->repository->expects($this->never())->method('replaceAll');
		$this->appConfig->expects($this->never())->method('setValueString');
		$this->logger->expects($this->once())->method('warning');

		$this->assertSame(RefreshResult::Failed, $this->refresher()->refresh());
	}

	public function testKeepsThePreviousCatalogueWhenTheResponseHasNoUsableApps(): void {
		$this->client->method('get')->willReturn($this->response(200, '[]', '"etag-2"'));
		$this->repository->expects($this->never())->method('replaceAll');
		$this->logger->expects($this->once())->method('warning');

		$this->assertSame(RefreshResult::Failed, $this->refresher()->refresh());
	}

	/**
	 * @param array{appstoreenabled?: bool, appstoreurl?: string} $systemConfig
	 */
	private function refresher(array $systemConfig = []): CatalogueRefresher {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')
			->with('appstoreenabled', true)
			->willReturn($systemConfig['appstoreenabled'] ?? true);
		$config->method('getSystemValueString')->willReturnMap([
			['appstoreurl', 'https://apps.nextcloud.com/api/v1', $systemConfig['appstoreurl'] ?? 'https://apps.nextcloud.com/api/v1'],
			['version', '0.0.0', '35.0.3.2'],
		]);
		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')->willReturn(1_800_000_000);

		return new CatalogueRefresher(
			$this->clientService, $config, $this->appConfig, new CatalogueParser(),
			$this->repository, $timeFactory, $this->logger,
		);
	}

	private static function fixture(): string {
		return (string)file_get_contents(__DIR__ . '/../../fixtures/apps-35.0.3.json');
	}

	private function response(int $status, string $body, string $etag): IResponse&MockObject {
		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn($status);
		$response->method('getBody')->willReturn($body);
		$response->method('getHeader')->with('ETag')->willReturn($etag);
		return $response;
	}
}
