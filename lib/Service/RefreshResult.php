<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AppStoreCards\Service;

enum RefreshResult {
	/** The admin has turned off App Store access. */
	case Disabled;
	/** The App Store reported no change since the last refresh. */
	case NotModified;
	/** The catalogue was replaced with the App Store's current list. */
	case Updated;
	/** The refresh failed and the previous catalogue was kept. */
	case Failed;
}
