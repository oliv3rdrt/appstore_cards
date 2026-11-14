<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Controller;

use OCA\AppStoreCards\AppInfo\Application;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\IRequest;

/**
 * Serves App Store screenshots from this server. Like Nextcloud's own preview and avatar
 * endpoints, it returns an image rather than OCS data.
 *
 * @psalm-api Reached through its #[FrontpageRoute] attribute.
 */
final class ScreenshotController extends Controller {
	public function __construct(
		IRequest $request,
		private readonly ScreenshotCache $screenshots,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Public, because cards also appear on public share pages. Only apps in the local catalogue
	 * are served, and both logged-in users and visitors are rate limited.
	 */
	#[PublicPage]
	#[NoCSRFRequired]
	#[UserRateLimit(limit: 120, period: 60)]
	#[AnonRateLimit(limit: 60, period: 60)]
	#[FrontpageRoute(verb: 'GET', url: '/screenshots/{appId}', requirements: ['appId' => '[a-z0-9_]{1,64}'])]
	public function show(string $appId): FileDisplayResponse|NotFoundResponse {
		$file = $this->screenshots->get($appId);
		if ($file === null) {
			return new NotFoundResponse();
		}

		$response = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => $file->getMimeType()]);
		// The file name changes when the screenshot does, but the URL does not, so a day is enough.
		$response->cacheFor(24 * 60 * 60, true);
		return $response;
	}
}
