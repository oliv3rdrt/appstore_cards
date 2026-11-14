/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { categoryLabel } from './categories.ts'

describe('categoryLabel', () => {
	// The App Store's English names, which differ from the IDs for some categories.
	it.each([
		['ai', 'AI'],
		['customization', 'Customization'],
		['dashboard', 'Dashboard'],
		['files', 'Files'],
		['games', 'Games'],
		['integration', 'Integration'],
		['monitoring', 'Monitoring'],
		['multimedia', 'Multimedia'],
		['office', 'Office & text'],
		['organization', 'Organization'],
		['search', 'Search'],
		['security', 'Security'],
		['social', 'Social & communication'],
		['tools', 'Tools'],
		['workflow', 'Flow'],
	])('names the %s category "%s"', (id, label) => {
		expect(categoryLabel(id)).toBe(label)
	})

	it('shows an unknown category ID as it is', () => {
		expect(categoryLabel('robotics')).toBe('robotics')
	})
})
