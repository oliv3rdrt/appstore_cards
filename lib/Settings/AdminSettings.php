<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Settings;

use OCA\AppStoreCards\AppInfo\Application;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Service\CatalogueRefresher;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\IAppConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IUser;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\IDeclarativeSettingsFormWithHandlers;

/**
 * Admin settings, built by Nextcloud from this schema. Nextcloud only lets admins change them,
 * and the form stores its own values so they keep their types.
 */
final class AdminSettings implements IDeclarativeSettingsFormWithHandlers {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly CatalogueRepository $repository,
		private readonly IDateTimeFormatter $dateTimeFormatter,
		private readonly IReferenceManager $referenceManager,
		private readonly IL10N $l10n,
	) {
	}

	#[\Override]
	public function getSchema(): array {
		return [
			// Not prefixed with the app ID: Nextcloud 35's settings page strips "appstore_cards_" from form IDs
			// before saving, so a prefixed ID would never match and changes would be silently dropped.
			'id' => 'admin',
			'priority' => 50,
			'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
			'section_id' => 'additional',
			'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL,
			'title' => $this->l10n->t('App Store Cards'),
			'description' => $this->status(),
			'fields' => [
				[
					'id' => ScreenshotCache::CONFIG_ENABLED,
					'title' => $this->l10n->t('Show screenshots on cards'),
					'description' => $this->l10n->t('This server downloads each screenshot once from the App Store and keeps a copy, so viewers never contact the App Store. Turn this off to stop downloading images. Browsers that already show a card may keep its old look for up to an hour.'),
					'type' => DeclarativeSettingsTypes::CHECKBOX,
					'label' => $this->l10n->t('Show screenshots'),
					'default' => true,
				],
			],
		];
	}

	#[\Override]
	public function getValue(string $fieldId, IUser $user): mixed {
		$this->assertKnownField($fieldId);
		return $this->appConfig->getValueBool(Application::APP_ID, ScreenshotCache::CONFIG_ENABLED, true);
	}

	#[\Override]
	public function setValue(string $fieldId, mixed $value, IUser $user): void {
		$this->assertKnownField($fieldId);
		$enabled = filter_var($value, FILTER_VALIDATE_BOOLEAN);
		if ($enabled === $this->appConfig->getValueBool(Application::APP_ID, ScreenshotCache::CONFIG_ENABLED, true)) {
			return;
		}

		$this->appConfig->setValueBool(Application::APP_ID, ScreenshotCache::CONFIG_ENABLED, $enabled);
		// Nextcloud caches resolved links for an hour, so cards would otherwise keep their old image.
		foreach ($this->repository->yieldAll() as $entry) {
			$this->referenceManager->invalidateCache($entry->appId);
		}
	}

	private function assertKnownField(string $fieldId): void {
		if ($fieldId !== ScreenshotCache::CONFIG_ENABLED) {
			throw new \InvalidArgumentException('Unknown setting ' . $fieldId);
		}
	}

	private function status(): string {
		$checkedAt = $this->appConfig->getValueInt(Application::APP_ID, CatalogueRefresher::CONFIG_CHECKED_AT);
		if ($checkedAt === 0) {
			return $this->l10n->t('The catalogue has not been downloaded yet. It refreshes once a day, or run occ appstore_cards:refresh.');
		}

		return $this->l10n->t('The catalogue has %1$d apps and was last checked on %2$s. It refreshes once a day.', [
			$this->repository->count(),
			$this->dateTimeFormatter->formatDateTime($checkedAt),
		]);
	}
}
