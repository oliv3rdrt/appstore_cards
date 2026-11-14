/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from '@nextcloud/e2e-test-server'
import type { APIRequestContext, Locator, Page } from '@playwright/test'

import { expect } from '@playwright/test'

export const DECK_URL = 'https://apps.nextcloud.com/apps/deck'

/**
 * The card for an app, found by its link, which names the app and says where it leads.
 *
 * @param page the page that shows the card
 * @param name the app's name, such as "Deck"
 */
export function appCard(page: Page, name: string): Locator {
	return page.getByRole('article').filter({
		has: page.getByRole('link', { name: `${name} (App Store, opens in a new tab)`, exact: true }),
	})
}

/**
 * Headers for requests made as the user with HTTP basic auth, as an API client would,
 * so they need no CSRF token.
 *
 * @param user the user to act as
 */
function asUser(user: User): Record<string, string> {
	return {
		Authorization: 'Basic ' + Buffer.from(`${user.userId}:${user.password}`).toString('base64'),
		'OCS-APIRequest': 'true',
		Accept: 'application/json',
	}
}

/**
 * Uploads a file to the user's root folder over WebDAV and returns its file ID.
 *
 * @param request the test's request context
 * @param user the owner
 * @param name the file name
 * @param content the file content
 */
export async function uploadFile(request: APIRequestContext, user: User, name: string, content: string): Promise<string> {
	const response = await request.put(`/remote.php/dav/files/${user.userId}/${encodeURIComponent(name)}`, {
		headers: asUser(user),
		data: content,
	})
	expect(response.status()).toBe(201)
	// The header is the padded file ID followed by the instance ID, such as "00000042oc1a2b3c4d".
	return String(parseInt(response.headers()['oc-fileid'], 10))
}

/**
 * Shares a file in the user's root folder by public link and returns the share token.
 *
 * @param request the test's request context
 * @param user the owner
 * @param name the file name
 */
export async function sharePublicly(request: APIRequestContext, user: User, name: string): Promise<string> {
	const response = await request.post('/ocs/v2.php/apps/files_sharing/api/v1/shares', {
		headers: asUser(user),
		form: { path: `/${name}`, shareType: 3 },
	})
	expect(response.ok()).toBe(true)
	return (await response.json()).ocs.data.token
}

/**
 * Creates a Talk conversation and returns its token.
 *
 * @param request the test's request context
 * @param user the moderator
 * @param name the conversation name
 */
export async function createConversation(request: APIRequestContext, user: User, name: string): Promise<string> {
	// Type 3 is a public conversation, the simplest one that needs no other participants.
	const response = await request.post('/ocs/v2.php/apps/spreed/api/v4/room', {
		headers: asUser(user),
		form: { roomType: 3, roomName: name },
	})
	expect(response.ok()).toBe(true)
	return (await response.json()).ocs.data.token
}

/**
 * Posts a chat message in a Talk conversation.
 *
 * @param request the test's request context
 * @param user the author
 * @param token the conversation token
 * @param message the message text
 */
export async function postMessage(request: APIRequestContext, user: User, token: string, message: string): Promise<void> {
	const response = await request.post(`/ocs/v2.php/apps/spreed/api/v1/chat/${token}`, {
		headers: asUser(user),
		form: { message },
	})
	expect(response.status()).toBe(201)
}
