<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Integration\Controller;

use OCA\AppStoreCards\Controller\ScreenshotController;
use OCA\AppStoreCards\Service\ScreenshotCache;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

/**
 * Runs inside Nextcloud, because setting cache headers asks the server for the current time.
 */
final class ScreenshotControllerTest extends TestCase {
	public function testServesTheCachedImageWithItsTypeForADay(): void {
		$file = $this->createMock(ISimpleFile::class);
		$file->method('getMimeType')->willReturn('image/png');
		$cache = $this->createMock(ScreenshotCache::class);
		$cache->method('get')->with('deck')->willReturn($file);

		$response = (new ScreenshotController($this->createMock(IRequest::class), $cache))->show('deck');

		$this->assertInstanceOf(FileDisplayResponse::class, $response);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
		$this->assertSame('image/png', $response->getHeaders()['Content-Type']);
		$this->assertStringContainsString('max-age=86400', $response->getHeaders()['Cache-Control']);
	}
}
