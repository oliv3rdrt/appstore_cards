<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Service;

use OCA\AppStoreCards\AppInfo\Application;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClientService;
use OCP\IAppConfig;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Downloads the App Store's list of apps for this Nextcloud version and stores it locally.
 *
 * @final Not declared final so that unit tests can mock it.
 */
class CatalogueRefresher {
	public const string CONFIG_ETAG = 'catalogue_etag';
	public const string CONFIG_CHECKED_AT = 'catalogue_checked_at';

	private const string DEFAULT_STORE_URL = 'https://apps.nextcloud.com/api/v1';
	private const int TIMEOUT_SECONDS = 30;

	public function __construct(
		private readonly IClientService $clientService,
		private readonly IConfig $config,
		private readonly IAppConfig $appConfig,
		private readonly CatalogueParser $parser,
		private readonly CatalogueRepository $repository,
		private readonly ITimeFactory $timeFactory,
		private readonly LoggerInterface $logger,
	) {
	}

	public function refresh(): RefreshResult {
		if (!$this->config->getSystemValueBool('appstoreenabled', true)) {
			return RefreshResult::Disabled;
		}

		$url = $this->catalogueUrl();
		// Without local data, a "not modified" answer would leave the catalogue empty.
		$etag = $this->repository->count() > 0
			? $this->appConfig->getValueString(Application::APP_ID, self::CONFIG_ETAG)
			: '';

		try {
			$response = $this->clientService->newClient()->get($url, [
				'timeout' => self::TIMEOUT_SECONDS,
				'headers' => $etag === '' ? [] : ['If-None-Match' => $etag],
			]);

			if ($response->getStatusCode() === Http::STATUS_NOT_MODIFIED) {
				$this->markChecked();
				return RefreshResult::NotModified;
			}

			$body = $response->getBody();
			$json = is_resource($body) ? (string)stream_get_contents($body) : (string)$body;
			$entries = $this->parser->parse($json);
			if ($entries === []) {
				throw new \UnexpectedValueException('The App Store returned no usable apps');
			}

			$this->repository->replaceAll($entries);
		} catch (\Exception $e) {
			$this->logger->warning('Could not refresh the App Store catalogue from {url}', [
				'url' => $url,
				'exception' => $e,
			]);
			return RefreshResult::Failed;
		}

		$this->appConfig->setValueString(Application::APP_ID, self::CONFIG_ETAG, $response->getHeader('ETag'));
		$this->markChecked();
		return RefreshResult::Updated;
	}

	/**
	 * The endpoint needs the full version number, such as 35.0.3. The installed version,
	 * such as 35.0.3.2, is kept in the system config.
	 */
	private function catalogueUrl(): string {
		$storeUrl = $this->config->getSystemValueString('appstoreurl', self::DEFAULT_STORE_URL);
		$installed = $this->config->getSystemValueString('version', '0.0.0');
		$version = implode('.', array_slice(explode('.', $installed), 0, 3));

		return rtrim($storeUrl, '/') . '/platform/' . $version . '/apps.json';
	}

	private function markChecked(): void {
		$this->appConfig->setValueInt(Application::APP_ID, self::CONFIG_CHECKED_AT, $this->timeFactory->getTime());
	}
}
