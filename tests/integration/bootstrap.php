<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Integration tests run inside the Nextcloud server this app is installed in.
require_once __DIR__ . '/../../../../lib/base.php';

\OCP\Server::get(\OCP\App\IAppManager::class)->loadApp('appstore_cards');
