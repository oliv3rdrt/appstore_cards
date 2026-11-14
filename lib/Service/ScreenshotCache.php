<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Service;

use OCA\AppStoreCards\AppInfo\Application;
use OCA\AppStoreCards\Db\CatalogueEntry;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * Fetches each app's first screenshot from the App Store once and keeps it in the app's data folder,
 * so viewers' browsers only ever talk to this server.
 *
 * @final Not declared final so that unit tests can mock it.
 */
class ScreenshotCache {
	public const string FOLDER = 'screenshots';
	public const string CONFIG_ENABLED = 'show_screenshots';
	public const int MAX_IMAGE_BYTES = 2 * 1024 * 1024;
	public const int MAX_CACHE_BYTES = 100 * 1024 * 1024;

	private const string ALLOWED_HOST = 'usercontent.apps.nextcloud.com';
	private const int TIMEOUT_SECONDS = 10;

	public function __construct(
		private readonly CatalogueRepository $repository,
		private readonly IAppData $appData,
		private readonly IClientService $clientService,
		private readonly IAppConfig $appConfig,
		private readonly IURLGenerator $urlGenerator,
		private readonly LoggerInterface $logger,
	) {
	}

	public function isEnabled(): bool {
		return $this->appConfig->getValueBool(Application::APP_ID, self::CONFIG_ENABLED, true);
	}

	/**
	 * The URL a card uses for the app's screenshot, or null when there is none to show.
	 */
	public function urlFor(CatalogueEntry $entry): ?string {
		if (!$this->isEnabled() || !$this->isAllowed($entry->screenshotUrl)) {
			return null;
		}

		return $this->urlGenerator->linkToRouteAbsolute('appstore_cards.screenshot.show', ['appId' => $entry->appId]);
	}

	/**
	 * The cached screenshot of an app, downloading it first if needed. Callers pass an app ID,
	 * never a URL: the image address always comes from the local catalogue.
	 */
	public function get(string $appId): ?ISimpleFile {
		if (!$this->isEnabled()) {
			return null;
		}

		$url = $this->repository->findByAppId($appId)?->screenshotUrl;
		if ($url === null || !$this->isAllowed($url)) {
			return null;
		}

		$folder = $this->folder();
		$prefix = self::cachePrefix($appId, $url);
		foreach ($folder->getDirectoryListing() as $file) {
			if (str_starts_with($file->getName(), $prefix)) {
				return $file;
			}
		}

		$image = $this->download($url);
		if ($image === null) {
			return null;
		}

		$this->deleteFilesOf($folder, $appId);
		return $folder->newFile($prefix . $image['extension'], $image['data']);
	}

	/**
	 * Deletes screenshots of apps that left the catalogue or changed their screenshot,
	 * then the oldest ones until the cache is within its size limit.
	 */
	public function prune(): void {
		$folder = $this->folder();
		$current = [];
		foreach ($this->repository->yieldAll() as $entry) {
			if ($entry->screenshotUrl !== null) {
				$current[self::cachePrefix($entry->appId, $entry->screenshotUrl)] = true;
			}
		}

		$kept = [];
		foreach ($folder->getDirectoryListing() as $file) {
			$prefix = substr($file->getName(), 0, (int)strrpos($file->getName(), '.'));
			if (isset($current[$prefix])) {
				$kept[] = $file;
			} else {
				$file->delete();
			}
		}

		usort($kept, static fn (ISimpleFile $a, ISimpleFile $b): int => $a->getMTime() <=> $b->getMTime());
		$total = array_sum(array_map(static fn (ISimpleFile $file): int => (int)$file->getSize(), $kept));
		foreach ($kept as $file) {
			if ($total <= self::MAX_CACHE_BYTES) {
				break;
			}
			$total -= (int)$file->getSize();
			$file->delete();
		}
	}

	/**
	 * Only the App Store's own image host over HTTPS, so the server never fetches other addresses.
	 */
	private function isAllowed(?string $url): bool {
		return $url !== null
			&& parse_url($url, PHP_URL_SCHEME) === 'https'
			&& parse_url($url, PHP_URL_HOST) === self::ALLOWED_HOST;
	}

	/**
	 * @return array{data: string, extension: string}|null
	 */
	private function download(string $url): ?array {
		try {
			$response = $this->clientService->newClient()->get($url, [
				'timeout' => self::TIMEOUT_SECONDS,
				'stream' => true,
			]);
			$length = $response->getHeader('Content-Length');
			if ($length !== '' && (int)$length > self::MAX_IMAGE_BYTES) {
				return null;
			}

			$body = $response->getBody();
			$data = is_resource($body)
				? (string)stream_get_contents($body, self::MAX_IMAGE_BYTES + 1)
				: (string)$body;
		} catch (\Exception $e) {
			$this->logger->warning('Could not download the App Store screenshot {url}', ['url' => $url, 'exception' => $e]);
			return null;
		}

		if (strlen($data) > self::MAX_IMAGE_BYTES) {
			return null;
		}

		// The App Store sends every image as application/octet-stream, so the type comes from the bytes.
		$extension = self::imageExtension($data);
		return $extension === null ? null : ['data' => $data, 'extension' => $extension];
	}

	/**
	 * Recognises raster formats only. SVG is never accepted, because it can contain scripts.
	 */
	private static function imageExtension(string $data): ?string {
		return match (true) {
			str_starts_with($data, "\x89PNG\r\n\x1a\n") => '.png',
			str_starts_with($data, "\xFF\xD8\xFF") => '.jpg',
			str_starts_with($data, 'GIF87a'), str_starts_with($data, 'GIF89a') => '.gif',
			str_starts_with($data, 'RIFF') && substr($data, 8, 4) === 'WEBP' => '.webp',
			default => null,
		};
	}

	/**
	 * File names include a hash of the URL, so a new screenshot replaces the old one.
	 */
	private static function cachePrefix(string $appId, string $url): string {
		return $appId . '-' . substr(hash('sha256', $url), 0, 16);
	}

	private function deleteFilesOf(ISimpleFolder $folder, string $appId): void {
		foreach ($folder->getDirectoryListing() as $file) {
			if (str_starts_with($file->getName(), $appId . '-')) {
				$file->delete();
			}
		}
	}

	private function folder(): ISimpleFolder {
		try {
			return $this->appData->getFolder(self::FOLDER);
		} catch (NotFoundException) {
			return $this->appData->newFolder(self::FOLDER);
		}
	}
}
