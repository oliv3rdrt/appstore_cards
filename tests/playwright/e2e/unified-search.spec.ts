/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../support/fixtures/random-user.ts'
import { DECK_URL } from '../support/nextcloud.ts'

test('finds App Store apps in unified search and links to their pages', async ({ page }) => {
	await page.goto('apps/dashboard/')

	await page.getByRole('combobox', { name: 'Apps, files, messages, and more' }).fill('Deck')

	// Nextcloud lists each provider's results under the provider's name.
	const results = page.getByRole('listbox', { name: 'More from App Store' })
	const deck = results.getByRole('option', { name: 'Deck Personal planning and team project organization' })
	await expect(deck).toBeVisible()
	await expect(deck.getByRole('link')).toHaveAttribute('href', DECK_URL)
})
