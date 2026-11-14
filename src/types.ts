/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** The rich object that AppStoreReferenceProvider sends for an App Store link. */
export interface AppCardData {
	appId: string
	name: string
	summary: string
	url: string
	latestVersion: string
	/** Nextcloud versions the latest release supports, such as ">=33 <=35". */
	platformSpec: string
	categories: string[]
	/** Average rating from 0 to 1. */
	rating: number
	ratingCount: number
	lastModified: string
	/** This server's URL for the app's screenshot, or null when there is none to show. */
	screenshotUrl?: string | null
}

/**
 * Checks the fields the card relies on, because rich objects arrive as untyped JSON.
 *
 * @param value the rich object from the reference
 */
export function isAppCardData(value: unknown): value is AppCardData {
	if (typeof value !== 'object' || value === null) {
		return false
	}

	const data = value as Record<string, unknown>
	return typeof data.name === 'string'
		&& typeof data.summary === 'string'
		&& typeof data.url === 'string' && data.url.startsWith('https://apps.nextcloud.com/apps/')
		&& typeof data.latestVersion === 'string'
		&& typeof data.platformSpec === 'string'
		&& Array.isArray(data.categories)
		&& typeof data.rating === 'number'
		&& typeof data.ratingCount === 'number'
}
