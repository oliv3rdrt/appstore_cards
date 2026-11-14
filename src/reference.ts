/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { App } from 'vue'

// This entry leaves out the Smart Picker modal that functions/reference also bundles,
// which keeps this script about a third of the size.
import { registerWidget } from '@nextcloud/vue/functions/registerReference'
import { createApp } from 'vue'
import AppCard from './components/AppCard.vue'
import { isAppCardData } from './types.ts'

/** Must match AppStoreReferenceProvider::RICH_OBJECT_TYPE. */
export const RICH_OBJECT_TYPE = 'appstore_cards_app'

const mountedCards = new WeakMap<HTMLElement, App>()

registerWidget(RICH_OBJECT_TYPE, (element, { richObject }) => {
	// Without the expected fields, Nextcloud's default preview stays in place.
	if (!isAppCardData(richObject)) {
		return
	}

	const card = createApp(AppCard, { app: richObject })
	card.mount(element)
	mountedCards.set(element, card)
}, (element) => {
	mountedCards.get(element)?.unmount()
	mountedCards.delete(element)
}, { hasInteractiveView: false })
