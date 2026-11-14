<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Service;

use OCA\AppStoreCards\Db\CatalogueEntry;

/**
 * Turns the App Store's list of apps for one Nextcloud version into catalogue entries.
 *
 * Apps without a valid ID, an English name or a non-nightly release are skipped.
 */
final class CatalogueParser {
	private const string APP_ID_PATTERN = '/^[a-z0-9_]{1,64}$/';

	/**
	 * @return list<CatalogueEntry>
	 * @throws \JsonException if the body is not valid JSON
	 * @throws \UnexpectedValueException if the JSON is not a list
	 */
	public function parse(string $json): array {
		$apps = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($apps) || !array_is_list($apps)) {
			throw new \UnexpectedValueException('The App Store response is not a list of apps');
		}

		$entries = [];
		foreach (self::arrayItems($apps) as $app) {
			$entry = $this->parseApp($app);
			if ($entry !== null) {
				$entries[] = $entry;
			}
		}

		return $entries;
	}

	/**
	 * @param array<array-key, mixed> $app
	 */
	private function parseApp(array $app): ?CatalogueEntry {
		$appId = self::string($app, 'id');
		$english = self::array(self::array($app, 'translations'), 'en');
		$name = self::string($english, 'name');
		$release = $this->latestRelease(self::array($app, 'releases'));
		$lastModified = self::date(self::string($app, 'lastModified'));

		if ($appId === null || preg_match(self::APP_ID_PATTERN, $appId) !== 1
			|| $name === null || $name === '' || $release === null || $lastModified === null) {
			return null;
		}

		$entry = new CatalogueEntry();
		$entry->appId = $appId;
		$entry->name = mb_substr($name, 0, 255);
		$entry->summary = mb_substr(self::string($english, 'summary') ?? '', 0, 512);
		$entry->latestVersion = mb_substr($release['version'], 0, 64);
		$entry->platformSpec = mb_substr($release['platformSpec'], 0, 64);
		$entry->categories = self::stringItems(self::array($app, 'categories'));
		$entry->rating = max(0.0, min(1.0, self::number($app, 'ratingOverall')));
		$entry->ratingCount = max(0, (int)self::number($app, 'ratingNumOverall'));
		$entry->screenshotUrl = $this->firstScreenshotUrl(self::array($app, 'screenshots'));
		$entry->lastModified = $lastModified;

		return $entry;
	}

	/**
	 * Picks the highest stable release, or the highest pre-release when there is no stable one.
	 * Releases are not sorted in the App Store response, so versions are compared.
	 *
	 * @param array<array-key, mixed> $releases
	 * @return array{version: string, platformSpec: string}|null
	 */
	private function latestRelease(array $releases): ?array {
		$stable = null;
		$preRelease = null;
		foreach (self::arrayItems($releases) as $release) {
			if (($release['isNightly'] ?? false) === true) {
				continue;
			}

			$version = self::string($release, 'version');
			$platformSpec = self::string($release, 'rawPlatformVersionSpec');
			if ($version === null || $platformSpec === null) {
				continue;
			}

			$candidate = ['version' => $version, 'platformSpec' => $platformSpec];
			if (str_contains($version, '-')) {
				if ($preRelease === null || version_compare($version, $preRelease['version'], '>')) {
					$preRelease = $candidate;
				}
			} elseif ($stable === null || version_compare($version, $stable['version'], '>')) {
				$stable = $candidate;
			}
		}

		return $stable ?? $preRelease;
	}

	/**
	 * @param array<array-key, mixed> $screenshots
	 */
	private function firstScreenshotUrl(array $screenshots): ?string {
		$first = self::arrayItems($screenshots)[0] ?? null;
		$url = $first === null ? null : self::string($first, 'url');
		if ($url === null || strlen($url) > 1024 || !str_starts_with($url, 'https://')) {
			return null;
		}

		return $url;
	}

	/**
	 * The helpers below read untyped JSON without ever assigning an unchecked value.
	 *
	 * @param array<array-key, mixed> $data
	 */
	private static function string(array $data, string $key): ?string {
		return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
	}

	/**
	 * @param array<array-key, mixed> $data
	 * @return array<array-key, mixed>
	 */
	private static function array(array $data, string $key): array {
		return isset($data[$key]) && is_array($data[$key]) ? $data[$key] : [];
	}

	/**
	 * @param array<array-key, mixed> $data
	 */
	private static function number(array $data, string $key): float {
		return isset($data[$key]) && (is_int($data[$key]) || is_float($data[$key])) ? (float)$data[$key] : 0.0;
	}

	/**
	 * @param array<array-key, mixed> $data
	 * @return list<array<array-key, mixed>>
	 */
	private static function arrayItems(array $data): array {
		$items = [];
		foreach (array_keys($data) as $key) {
			if (is_array($data[$key])) {
				$items[] = $data[$key];
			}
		}

		return $items;
	}

	/**
	 * @param array<array-key, mixed> $data
	 * @return list<string>
	 */
	private static function stringItems(array $data): array {
		$items = [];
		foreach (array_keys($data) as $key) {
			if (is_string($data[$key])) {
				$items[] = $data[$key];
			}
		}

		return $items;
	}

	private static function date(?string $value): ?\DateTimeImmutable {
		if ($value === null) {
			return null;
		}

		try {
			return new \DateTimeImmutable($value);
		} catch (\Exception) {
			return null;
		}
	}
}
