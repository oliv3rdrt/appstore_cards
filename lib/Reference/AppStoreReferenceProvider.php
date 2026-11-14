<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Reference;

use OCA\AppStoreCards\AppInfo\Application;
use OCA\AppStoreCards\Db\CatalogueRepository;
use OCA\AppStoreCards\Search\AppStoreSearchProvider;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\Collaboration\Reference\ADiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IPublicReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\ISearchableReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * Turns links to apps on apps.nextcloud.com into App Store cards, and adds the App Store to the Smart Picker.
 */
final class AppStoreReferenceProvider extends ADiscoverableReferenceProvider implements ISearchableReferenceProvider, IPublicReferenceProvider {
	public const string RICH_OBJECT_TYPE = 'appstore_cards_app';

	private const string LINK_PATTERN = '~^https?://apps\.nextcloud\.com/apps/([a-z0-9_]{1,64})/?(?:[?#].*)?$~';

	public function __construct(
		private readonly CatalogueRepository $repository,
		private readonly ScreenshotCache $screenshots,
		private readonly IL10N $l10n,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getId(): string {
		return Application::APP_ID;
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l10n->t('App Store apps');
	}

	#[\Override]
	public function getOrder(): int {
		return 50;
	}

	#[\Override]
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg'));
	}

	/**
	 * The Smart Picker searches through our unified search provider.
	 */
	#[\Override]
	public function getSupportedSearchProviderIds(): array {
		return [AppStoreSearchProvider::ID];
	}

	#[\Override]
	public function matchReference(string $referenceText): bool {
		return $this->appIdFromLink($referenceText) !== null;
	}

	#[\Override]
	public function resolveReference(string $referenceText): ?IReference {
		$appId = $this->appIdFromLink($referenceText);
		$entry = $appId === null ? null : $this->repository->findByAppId($appId);
		if ($entry === null) {
			// Unknown apps fall back to Nextcloud's generic link preview.
			return null;
		}

		$reference = new Reference($referenceText);
		$reference->setUrl($entry->storeUrl());
		$reference->setTitle($entry->name);
		$reference->setDescription($entry->summary);
		$screenshotUrl = $this->screenshots->urlFor($entry);
		$reference->setImageUrl($screenshotUrl);
		$reference->setRichObject(self::RICH_OBJECT_TYPE, [
			'appId' => $entry->appId,
			'name' => $entry->name,
			'summary' => $entry->summary,
			'url' => $entry->storeUrl(),
			'latestVersion' => $entry->latestVersion,
			'platformSpec' => $entry->platformSpec,
			'categories' => $entry->categories,
			'rating' => $entry->rating,
			'ratingCount' => $entry->ratingCount,
			'lastModified' => $entry->lastModified->format(\DateTimeInterface::ATOM),
			'screenshotUrl' => $screenshotUrl,
		]);

		return $reference;
	}

	/**
	 * The catalogue is public App Store data, so visitors of a public share see the same card.
	 * Nextcloud passes the share token through unchecked. Like its own LinkReferenceProvider,
	 * this provider does not need it, because it reveals nothing that is not already public.
	 */
	#[\Override]
	public function resolveReferencePublic(string $referenceText, string $sharingToken): ?IReference {
		return $this->resolveReference($referenceText);
	}

	#[\Override]
	public function getCachePrefix(string $referenceId): string {
		return $this->appIdFromLink($referenceId) ?? '';
	}

	/**
	 * Cards look the same for everyone, so all users share one cache entry per link.
	 */
	#[\Override]
	public function getCacheKey(string $referenceId): ?string {
		return null;
	}

	#[\Override]
	public function getCacheKeyPublic(string $referenceId, string $sharingToken): ?string {
		return null;
	}

	private function appIdFromLink(string $link): ?string {
		return preg_match(self::LINK_PATTERN, $link, $matches) === 1 ? $matches[1] : null;
	}
}
