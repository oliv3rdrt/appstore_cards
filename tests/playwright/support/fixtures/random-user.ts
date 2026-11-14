/**
 * SPDX-FileCopyrightText: 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'

import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { test as base } from '@playwright/test'

interface RandomUserFixture {
	user: User
}

/**
 * Gives each test a new user, logged in on a page with no stored state.
 */
export const test = base.extend<RandomUserFixture>({
	// eslint-disable-next-line no-empty-pattern
	user: async ({}, use) => {
		await use(await createRandomUser())
	},
	page: async ({ browser, baseURL, user }, use) => {
		const page = await browser.newPage({ storageState: undefined, baseURL })
		await login(page.request, user)
		await use(page)
		await page.close()
	},
})
