/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AppCardData } from '../types.ts'

import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppCard from './AppCard.vue'

const deck: AppCardData = {
	appId: 'deck',
	name: 'Deck',
	summary: 'Personal planning and team project organization',
	url: 'https://apps.nextcloud.com/apps/deck',
	latestVersion: '1.19.2',
	platformSpec: '>=35.0.2 <=35',
	categories: ['office', 'organization'],
	rating: 0.83,
	ratingCount: 50,
	lastModified: '2026-11-05T15:54:19+00:00',
}

function render(overrides: Partial<AppCardData> = {}) {
	return mount(AppCard, { props: { app: { ...deck, ...overrides } } })
}

describe('AppCard', () => {
	it('links to the app on the App Store in a new tab', () => {
		const link = render().get('a')

		expect(link.attributes('href')).toBe('https://apps.nextcloud.com/apps/deck')
		expect(link.attributes('target')).toBe('_blank')
		expect(link.attributes('rel')).toBe('noopener noreferrer')
		expect(link.text()).toContain('Deck')
		expect(link.text()).toContain('(App Store, opens in a new tab)')
	})

	it('shows the summary, latest version and supported Nextcloud version', () => {
		const text = render().text()

		expect(text).toContain('Personal planning and team project organization')
		expect(text).toContain('Version 1.19.2')
		expect(text).toContain('Nextcloud 35')
	})

	it('shows a range of supported Nextcloud versions', () => {
		expect(render({ platformSpec: '>=32 <=35' }).text()).toContain('Nextcloud 32 to 35')
		expect(render({ platformSpec: '>=33' }).text()).toContain('Nextcloud 33 and later')
	})

	it('shows the rating and describes it in words for screen readers', () => {
		const wrapper = render()

		expect(wrapper.get('[aria-hidden="true"]').text()).toBe('★ 4.2 (50)')
		expect(wrapper.text()).toContain('Rated 4.2 out of 5 by 50 people')
	})

	it('uses the singular for a single rating', () => {
		expect(render({ ratingCount: 1, rating: 1 }).text()).toContain('Rated 5.0 out of 5 by 1 person')
	})

	it('says when an app has no ratings yet', () => {
		const wrapper = render({ ratingCount: 0 })

		expect(wrapper.text()).toContain('No ratings yet')
		expect(wrapper.find('[aria-hidden="true"]').exists()).toBe(false)
	})

	it('names the categories, keeping unknown ones as they are', () => {
		const categories = render({ categories: ['office', 'workflow', 'new_category'] }).get('[role="list"][aria-label="Categories"]')

		expect(categories.findAll('[role="listitem"]').map((item) => item.text())).toEqual(['Office & text', 'Flow', 'new_category'])
	})

	it('shows the screenshot from this server with alt text, lazily', () => {
		const image = render({ screenshotUrl: 'https://cloud.example.org/apps/appstore_cards/screenshots/deck' }).get('img')

		expect(image.attributes('src')).toBe('https://cloud.example.org/apps/appstore_cards/screenshots/deck')
		expect(image.attributes('alt')).toBe('Screenshot of Deck')
		expect(image.attributes('loading')).toBe('lazy')
	})

	it('shows no image when there is no screenshot or it fails to load', async () => {
		expect(render({ screenshotUrl: null }).find('img').exists()).toBe(false)

		const wrapper = render({ screenshotUrl: 'https://cloud.example.org/apps/appstore_cards/screenshots/deck' })
		await wrapper.get('img').trigger('error')
		expect(wrapper.find('img').exists()).toBe(false)
	})

	it('leaves out the categories list when there are none', () => {
		expect(render({ categories: [] }).find('[role="list"][aria-label="Categories"]').exists()).toBe(false)
	})
})
