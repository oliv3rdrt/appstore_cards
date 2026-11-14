<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Listener;

use OCA\AppStoreCards\AppInfo\Application;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

/**
 * Loads the card widget only on pages that may show link previews, such as Talk and Text.
 *
 * @template-implements IEventListener<Event>
 */
final class LoadReferenceWidget implements IEventListener {
	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof RenderReferenceEvent) {
			return;
		}

		// addScript() also loads the app's translations for the script.
		Util::addScript(Application::APP_ID, Application::APP_ID . '-reference');
		Util::addStyle(Application::APP_ID, Application::APP_ID . '-reference');
	}
}
