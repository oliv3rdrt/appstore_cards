/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

module.exports = {
	extends: [
		// The Vue 3 preset also parses TypeScript in .vue files.
		'@nextcloud/eslint-config/vue3',
	],
	rules: {
		'jsdoc/require-jsdoc': 'off',
		'vue/first-attribute-linebreak': 'off',
	},
	overrides: [
		{
			// Tests may import development-only packages such as Vitest and Playwright.
			files: ['src/**/*.spec.ts', 'tests/playwright/**', 'playwright.config.ts'],
			rules: {
				'n/no-unpublished-import': 'off',
			},
		},
	],
}
