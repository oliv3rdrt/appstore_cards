/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect } from '@playwright/test'
import { test } from '../support/fixtures/random-user.ts'
import { appCard, createConversation, DECK_URL, postMessage } from '../support/nextcloud.ts'

test('shows a card for an App Store link in a Talk message', async ({ page, request, user }) => {
	const token = await createConversation(request, user, 'App ideas')
	await postMessage(request, user, token, `Have a look at ${DECK_URL}`)

	await page.goto(`call/${token}`)

	await expect(appCard(page, 'Deck')).toBeVisible()
})
