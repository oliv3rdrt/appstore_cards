<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Unit\Controller;

use OCA\AppStoreCards\Controller\ScreenshotController;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

final class ScreenshotControllerTest extends TestCase {
	public function testAnswersNotFoundWhenThereIsNoScreenshot(): void {
		$cache = $this->createMock(ScreenshotCache::class);
		$cache->method('get')->willReturn(null);

		$response = (new ScreenshotController($this->createMock(IRequest::class), $cache))->show('deck');

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
	}
}
