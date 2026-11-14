/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig, devices } from '@playwright/test'

/**
 * End-to-end tests against a throwaway Nextcloud 35 container, based on the example
 * in @nextcloud/e2e-test-server. See https://playwright.dev/docs/test-configuration.
 */
export default defineConfig({
	testDir: './tests/playwright',
	outputDir: './tests/playwright/results',

	forbidOnly: !!process.env.CI,
	retries: process.env.CI ? 2 : 0,
	// The container is one server on SQLite, and creating test users in parallel fails there.
	workers: 1,
	reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'html',

	use: {
		baseURL: 'http://localhost:8089/index.php/',
		trace: 'on-first-retry',
	},

	projects: [
		{
			// Installs the apps and fills the catalogue once the container answers.
			name: 'setup',
			testMatch: /support\/setup\.ts$/,
		},
		{
			name: 'chromium',
			testMatch: /e2e\/.*\.spec\.ts$/,
			use: { ...devices['Desktop Chrome'] },
			dependencies: ['setup'],
		},
	],

	webServer: {
		command: 'npm run start:nextcloud',
		reuseExistingServer: !process.env.CI,
		url: 'http://127.0.0.1:8089',
		stderr: 'pipe',
		stdout: 'pipe',
		// Creating the container and checking out the server branch takes a few minutes.
		timeout: 5 * 60 * 1000,
	},
})
