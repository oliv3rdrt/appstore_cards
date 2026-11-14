/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../support/fixtures/random-user.ts'
import { appCard, DECK_URL, uploadFile } from '../support/nextcloud.ts'

test('inserts an app with the Smart Picker in Text, which then shows its card', async ({ page, request, user }) => {
	const fileId = await uploadFile(request, user, 'Notes.md', '')
	await page.goto(`apps/files/files/${fileId}?dir=/&openfile=true`)

	// Text's slash menu lists each Smart Picker provider by its title, and a space would close it.
	await page.getByRole('dialog', { name: 'Notes.md' }).getByRole('textbox').click()
	await page.keyboard.type('/App')
	await expect(page.getByText('App Store apps', { exact: true })).toBeVisible()
	await page.keyboard.press('Enter')

	// The picker opens with its search field focused.
	const picker = page.getByRole('dialog').filter({ has: page.getByRole('heading', { name: 'App Store apps' }) })
	await expect(picker.getByRole('combobox', { name: 'Search' })).toBeFocused()
	await page.keyboard.type('deck')
	await picker.getByRole('option', { name: 'Deck Personal planning and team project organization' }).click()

	await expect(picker).toBeHidden()
	const card = appCard(page, 'Deck')
	await expect(card).toBeVisible()
	await expect(card.getByRole('link')).toHaveAttribute('href', DECK_URL)
})
