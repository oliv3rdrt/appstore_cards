/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'

/**
 * The App Store's English name for a category ID, translated for the user.
 * Unknown IDs are shown as they are.
 *
 * @param id the category ID, such as "office"
 */
export function categoryLabel(id: string): string {
	switch (id) {
	case 'ai': return t('appstore_cards', 'AI')
	case 'customization': return t('appstore_cards', 'Customization')
	case 'dashboard': return t('appstore_cards', 'Dashboard')
	case 'files': return t('appstore_cards', 'Files')
	case 'games': return t('appstore_cards', 'Games')
	case 'integration': return t('appstore_cards', 'Integration')
	case 'monitoring': return t('appstore_cards', 'Monitoring')
	case 'multimedia': return t('appstore_cards', 'Multimedia')
	case 'office': return t('appstore_cards', 'Office & text')
	case 'organization': return t('appstore_cards', 'Organization')
	case 'search': return t('appstore_cards', 'Search')
	case 'security': return t('appstore_cards', 'Security')
	case 'social': return t('appstore_cards', 'Social & communication')
	case 'tools': return t('appstore_cards', 'Tools')
	case 'workflow': return t('appstore_cards', 'Flow')
	default: return id
	}
}
