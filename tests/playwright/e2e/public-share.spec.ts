/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../support/fixtures/random-user.ts'
import { appCard, DECK_URL, sharePublicly, uploadFile } from '../support/nextcloud.ts'

test('shows a card to a visitor of a public share who is not logged in', async ({ browser, baseURL, request, user }) => {
	await uploadFile(request, user, 'Apps.md', `[${DECK_URL}](${DECK_URL} (preview))\n`)
	const shareToken = await sharePublicly(request, user, 'Apps.md')

	const visitor = await browser.newPage({ storageState: undefined, baseURL })
	await visitor.goto(`s/${shareToken}`)

	await expect(appCard(visitor, 'Deck')).toBeVisible()
	await visitor.close()
})
