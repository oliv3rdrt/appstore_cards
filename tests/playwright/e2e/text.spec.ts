/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../support/fixtures/random-user.ts'
import { appCard, DECK_URL, uploadFile } from '../support/nextcloud.ts'

test('shows a card for an App Store link in a Text document', async ({ page, request, user }) => {
	// Text shows a link preview for a link whose title is "(preview)".
	const fileId = await uploadFile(request, user, 'Apps.md', `[${DECK_URL}](${DECK_URL} (preview))\n`)

	await page.goto(`apps/files/files/${fileId}?dir=/&openfile=true`)

	const card = appCard(page, 'Deck')
	await expect(card).toBeVisible()
	await expect(card.getByRole('link')).toHaveAttribute('href', DECK_URL)
	await expect(card).toContainText(/Version \d+\.\d+\.\d+/)
})
