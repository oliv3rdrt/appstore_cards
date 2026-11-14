/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { beforeAll, describe, expect, it, vi } from 'vitest'

const { registerWidget } = vi.hoisted(() => ({ registerWidget: vi.fn() }))
vi.mock('@nextcloud/vue/functions/registerReference', () => ({ registerWidget }))

type Render = (element: HTMLElement, properties: { richObject: unknown }) => void
type Destroy = (element: HTMLElement) => void

const deck = {
	appId: 'deck',
	name: 'Deck',
	summary: 'Personal planning and team project organization',
	url: 'https://apps.nextcloud.com/apps/deck',
	latestVersion: '1.19.2',
	platformSpec: '>=35.0.2 <=35',
	categories: ['office'],
	rating: 0.83,
	ratingCount: 50,
	lastModified: '2026-11-05T15:54:19+00:00',
}

describe('reference widget', () => {
	let registration: [string, Render, Destroy, object]
	let render: Render
	let destroy: Destroy

	beforeAll(async () => {
		await import('./reference.ts')
		// Vitest clears mock calls before each test, so the registration is kept here.
		registration = registerWidget.mock.calls[0] as [string, Render, Destroy, object]
		;[, render, destroy] = registration
	})

	it('registers for the rich object type of App Store cards, without an interactive view', () => {
		expect(registration[0]).toBe('appstore_cards_app')
		expect(registration[3]).toEqual({ hasInteractiveView: false })
	})

	it('mounts a card into the element and removes it again', () => {
		const element = document.createElement('div')

		render(element, { richObject: deck })
		expect(element.querySelector('a')?.getAttribute('href')).toBe('https://apps.nextcloud.com/apps/deck')

		destroy(element)
		expect(element.innerHTML).toBe('')
	})

	it.each([
		['not an object', 'deck'],
		['missing fields', { name: 'Deck' }],
		['a link to another site', { ...deck, url: 'https://example.org/apps/deck' }],
	])('leaves the element alone for %s', (_case, richObject) => {
		const element = document.createElement('div')

		render(element, { richObject })

		expect(element.innerHTML).toBe('')
	})
})
