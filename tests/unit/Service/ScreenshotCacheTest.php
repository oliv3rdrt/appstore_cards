<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Service;

use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ScreenshotCacheTest extends TestCase {
	private const string URL = 'https://usercontent.apps.nextcloud.com/deck-screenshot';
	private const string PNG = "\x89PNG\r\n\x1a\n" . 'image data';

	private CatalogueRepository&MockObject $repository;
	private ISimpleFolder&MockObject $folder;
	private IClient&MockObject $client;
	private IClientService&MockObject $clientService;
	private IAppConfig&MockObject $appConfig;
	private LoggerInterface&MockObject $logger;
	private ScreenshotCache $cache;

	protected function setUp(): void {
		$this->repository = $this->createMock(CatalogueRepository::class);
		$this->folder = $this->createMock(ISimpleFolder::class);
		$appData = $this->createMock(IAppData::class);
		$appData->method('getFolder')->with('screenshots')->willReturn($this->folder);
		$this->client = $this->createMock(IClient::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->clientService->method('newClient')->willReturn($this->client);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueBool')->with('appstore_cards', 'show_screenshots', true)->willReturn(true);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$urlGenerator->method('linkToRouteAbsolute')
			->with('appstore_cards.screenshot.show', ['appId' => 'deck'])
			->willReturn('https://cloud.example.org/apps/appstore_cards/screenshots/deck');
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->cache = new ScreenshotCache($this->repository, $appData, $this->clientService, $this->appConfig, $urlGenerator, $this->logger);
	}

	public function testGivesCardsThisServersUrlForAnAppStoreScreenshot(): void {
		$this->assertSame('https://cloud.example.org/apps/appstore_cards/screenshots/deck', $this->cache->urlFor(self::entry('deck', self::URL)));
	}

	/**
	 * @return array<string, array{?string}>
	 */
	public static function unservableUrls(): array {
		return [
			'no screenshot' => [null],
			'another host' => ['https://example.org/deck.png'],
			'plain http' => ['http://usercontent.apps.nextcloud.com/deck-screenshot'],
			'look-alike host' => ['https://usercontent.apps.nextcloud.com.example.org/deck.png'],
		];
	}

	#[DataProvider('unservableUrls')]
	public function testOffersNoScreenshotForOtherAddresses(?string $url): void {
		$this->repository->method('findByAppId')->willReturn(self::entry('deck', $url));
		$this->clientService->expects($this->never())->method('newClient');

		$this->assertNull($this->cache->urlFor(self::entry('deck', $url)));
		$this->assertNull($this->cache->get('deck'));
	}

	public function testOffersNoScreenshotWhenTheAdminTurnedThemOff(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueBool')->willReturn(false);
		$cache = new ScreenshotCache(
			$this->repository, $this->createMock(IAppData::class), $this->clientService, $appConfig,
			$this->createMock(IURLGenerator::class), $this->logger,
		);
		$this->clientService->expects($this->never())->method('newClient');

		$this->assertNull($cache->urlFor(self::entry('deck', self::URL)));
		$this->assertNull($cache->get('deck'));
	}

	public function testServesACachedScreenshotWithoutDownloadingAgain(): void {
		$cached = self::file(self::prefix('deck', self::URL) . '.png');
		$this->repository->method('findByAppId')->willReturn(self::entry('deck', self::URL));
		$this->folder->method('getDirectoryListing')->willReturn([$cached]);
		$this->clientService->expects($this->never())->method('newClient');

		$this->assertSame($cached, $this->cache->get('deck'));
	}

	public function testDownloadsOnceAndReplacesAnOlderScreenshotOfTheSameApp(): void {
		$older = self::file('deck-0000000000000000.jpg');
		$older->expects($this->once())->method('delete');
		$other = self::file('decktools-1111111111111111.png');
		$other->expects($this->never())->method('delete');
		$stored = self::file(self::prefix('deck', self::URL) . '.png');
		$this->repository->method('findByAppId')->willReturn(self::entry('deck', self::URL));
		$this->folder->method('getDirectoryListing')->willReturn([$older, $other]);
		$this->client->expects($this->once())->method('get')
			->with(self::URL, ['timeout' => 10, 'stream' => true])
			->willReturn(self::response(self::PNG));
		$this->folder->expects($this->once())->method('newFile')
			->with(self::prefix('deck', self::URL) . '.png', self::PNG)
			->willReturn($stored);

		$this->assertSame($stored, $this->cache->get('deck'));
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function rejectedResponses(): array {
		return [
			'SVG, which can carry scripts' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', ''],
			'HTML error page' => ['<!DOCTYPE html><title>Not found</title>', ''],
			'announced as too large' => [self::PNG, (string)(ScreenshotCache::MAX_IMAGE_BYTES + 1)],
			'larger than announced' => [self::PNG . str_repeat('x', ScreenshotCache::MAX_IMAGE_BYTES), ''],
		];
	}

	#[DataProvider('rejectedResponses')]
	public function testStoresOnlySmallRasterImages(string $body, string $contentLength): void {
		$this->repository->method('findByAppId')->willReturn(self::entry('deck', self::URL));
		$this->folder->method('getDirectoryListing')->willReturn([]);
		$this->client->method('get')->willReturn(self::response($body, $contentLength));
		$this->folder->expects($this->never())->method('newFile');

		$this->assertNull($this->cache->get('deck'));
	}

	public function testStoresAStreamedDownload(): void {
		$stored = self::file(self::prefix('deck', self::URL) . '.png');
		$this->repository->method('findByAppId')->willReturn(self::entry('deck', self::URL));
		$this->folder->method('getDirectoryListing')->willReturn([]);
		$this->client->method('get')->willReturn($this->response(self::stream(self::PNG)));
		$this->folder->expects($this->once())->method('newFile')
			->with(self::prefix('deck', self::URL) . '.png', self::PNG)
			->willReturn($stored);

		$this->assertSame($stored, $this->cache->get('deck'));
	}

	public function testStopsReadingAStreamedDownloadJustPastTheLimit(): void {
		$stream = self::stream(self::PNG . str_repeat('x', 2 * ScreenshotCache::MAX_IMAGE_BYTES));
		$this->repository->method('findByAppId')->willReturn(self::entry('deck', self::URL));
		$this->folder->method('getDirectoryListing')->willReturn([]);
		$this->client->method('get')->willReturn($this->response($stream));
		$this->folder->expects($this->never())->method('newFile');

		$this->assertNull($this->cache->get('deck'));
		$this->assertSame(ScreenshotCache::MAX_IMAGE_BYTES + 1, ftell($stream));
	}

	public function testLogsAndGivesUpWhenTheDownloadFails(): void {
		$this->repository->method('findByAppId')->willReturn(self::entry('deck', self::URL));
		$this->folder->method('getDirectoryListing')->willReturn([]);
		$this->client->method('get')->willThrowException(new \RuntimeException('Connection timed out'));
		$this->logger->expects($this->once())->method('warning');

		$this->assertNull($this->cache->get('deck'));
	}

	public function testPruningDropsStaleScreenshotsThenTheOldestUntilWithinTheLimit(): void {
		$this->repository->method('yieldAll')->willReturnCallback(static function (): \Generator {
			yield self::entry('deck', self::URL);
			yield self::entry('forms', 'https://usercontent.apps.nextcloud.com/forms-screenshot');
			yield self::entry('calendar', 'https://usercontent.apps.nextcloud.com/calendar-screenshot');
		});
		$removedApp = self::file('polls-0000000000000000.png');
		$replaced = self::file('deck-0000000000000000.png');
		$oldest = self::file(self::prefix('forms', 'https://usercontent.apps.nextcloud.com/forms-screenshot') . '.png', ScreenshotCache::MAX_CACHE_BYTES, 100);
		$newest = self::file(self::prefix('calendar', 'https://usercontent.apps.nextcloud.com/calendar-screenshot') . '.jpg', 1000, 200);
		$this->folder->method('getDirectoryListing')->willReturn([$removedApp, $replaced, $oldest, $newest]);

		$removedApp->expects($this->once())->method('delete');
		$replaced->expects($this->once())->method('delete');
		$oldest->expects($this->once())->method('delete');
		$newest->expects($this->never())->method('delete');

		$this->cache->prune();
	}

	public function testCreatesTheScreenshotFolderOnFirstUse(): void {
		$appData = $this->createMock(IAppData::class);
		$appData->method('getFolder')->willThrowException(new NotFoundException());
		$appData->expects($this->once())->method('newFolder')->with('screenshots')->willReturn($this->folder);
		$this->folder->method('getDirectoryListing')->willReturn([]);

		$this->cacheWith($appData)->prune();
	}

	public function testDeletingAllScreenshotsWhenNoneWereCachedChangesNothing(): void {
		$appData = $this->createMock(IAppData::class);
		$appData->method('getFolder')->willThrowException(new NotFoundException());
		$appData->expects($this->never())->method('newFolder');

		$this->cacheWith($appData)->deleteAll();
	}

	private function cacheWith(IAppData $appData): ScreenshotCache {
		return new ScreenshotCache(
			$this->repository, $appData, $this->clientService, $this->appConfig,
			$this->createMock(IURLGenerator::class), $this->logger,
		);
	}

	/**
	 * @return resource
	 */
	private static function stream(string $contents) {
		$stream = fopen('php://memory', 'r+b');
		fwrite($stream, $contents);
		rewind($stream);
		return $stream;
	}

	private static function prefix(string $appId, string $url): string {
		return $appId . '-' . substr(hash('sha256', $url), 0, 16);
	}

	private static function entry(string $appId, ?string $screenshotUrl): CatalogueEntry {
		$entry = new CatalogueEntry();
		$entry->appId = $appId;
		$entry->screenshotUrl = $screenshotUrl;
		return $entry;
	}

	private function file(string $name, int $size = 1000, int $mtime = 0): ISimpleFile&MockObject {
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getName')->willReturn($name);
		$file->method('getSize')->willReturn($size);
		$file->method('getMTime')->willReturn($mtime);
		return $file;
	}

	/**
	 * @param string|resource $body Nextcloud's client returns a stream when asked to, otherwise a string.
	 */
	private function response(mixed $body, string $contentLength = ''): IResponse&MockObject {
		$response = $this->createMock(IResponse::class);
		$response->method('getHeader')->with('Content-Length')->willReturn($contentLength);
		$response->method('getBody')->willReturn($body);
		return $response;
	}
}
