<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\AppInfo;

use OCA\AppStoreCards\Reference\AppStoreReferenceProvider;
use OCA\AppStoreCards\Search\AppStoreSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

final class Application extends App implements IBootstrap {
	public const string APP_ID = 'appstore_cards';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerReferenceProvider(AppStoreReferenceProvider::class);
		$context->registerSearchProvider(AppStoreSearchProvider::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
	}
}
