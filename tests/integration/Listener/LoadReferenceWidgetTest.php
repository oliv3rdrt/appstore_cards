<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Tests\Integration\Listener;

use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Server;
use OCP\Util;
use PHPUnit\Framework\TestCase;

final class LoadReferenceWidgetTest extends TestCase {
	public function testLoadsTheCardWidgetWhenAPageMayShowLinkPreviews(): void {
		Server::get(IEventDispatcher::class)->dispatchTyped(new RenderReferenceEvent());

		$this->assertContains('appstore_cards/js/appstore_cards-reference', Util::getScripts());
	}
}
