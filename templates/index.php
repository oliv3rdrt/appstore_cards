<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OCP\Util;

Util::addScript(OCA\AppStoreCards\AppInfo\Application::APP_ID, OCA\AppStoreCards\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\AppStoreCards\AppInfo\Application::APP_ID, OCA\AppStoreCards\AppInfo\Application::APP_ID . '-main');

?>

<div id="appstore_cards"></div>
