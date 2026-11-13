<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\AppStoreCards\AppInfo\Application::APP_ID, OCA\AppStoreCards\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\AppStoreCards\AppInfo\Application::APP_ID, OCA\AppStoreCards\AppInfo\Application::APP_ID . '-main');

?>

<div id="appstore_cards"></div>
