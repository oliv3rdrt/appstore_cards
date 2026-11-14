<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import type { AppCardData } from '../types.ts'

import { getCanonicalLocale, n, t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { categoryLabel } from '../categories.ts'
import { majorVersionRange } from '../compatibility.ts'

const props = defineProps<{
	app: AppCardData
}>()

const compatibility = computed(() => {
	const { min, max } = majorVersionRange(props.app.platformSpec)
	if (min === null) {
		return null
	}
	if (max === null) {
		return t('appstore_cards', 'Nextcloud {min} and later', { min })
	}
	if (min === max) {
		return t('appstore_cards', 'Nextcloud {version}', { version: min })
	}
	return t('appstore_cards', 'Nextcloud {min} to {max}', { min, max })
})

/**
 * The rating out of five with one decimal, such as "4.2", in the user's number format.
 * Rounding tenths first avoids float results such as 0.83 * 5 = 4.1499999999999995.
 */
const stars = computed(() => (Math.round(props.app.rating * 50) / 10).toLocaleString(getCanonicalLocale(), {
	minimumFractionDigits: 1,
	maximumFractionDigits: 1,
}))

const ratingDescription = computed(() => n(
	'appstore_cards',
	'Rated {stars} out of 5 by %n person',
	'Rated {stars} out of 5 by %n people',
	props.app.ratingCount,
	{ stars: stars.value },
))

const categories = computed(() => props.app.categories.map(categoryLabel))
</script>

<template>
	<article :class="$style.card">
		<a :class="$style.name"
			:href="app.url"
			target="_blank"
			rel="noopener noreferrer">
			{{ app.name }}
			<span class="hidden-visually">{{ t('appstore_cards', '(App Store, opens in a new tab)') }}</span>
		</a>
		<p :class="$style.summary">
			{{ app.summary }}
		</p>
		<div :class="$style.facts" role="list">
			<div role="listitem">
				{{ t('appstore_cards', 'Version {version}', { version: app.latestVersion }) }}
			</div>
			<div v-if="compatibility" role="listitem">
				{{ compatibility }}
			</div>
			<div v-if="app.ratingCount > 0" role="listitem">
				<span aria-hidden="true">★ {{ stars }} ({{ app.ratingCount }})</span>
				<span class="hidden-visually">{{ ratingDescription }}</span>
			</div>
			<div v-else role="listitem">
				{{ t('appstore_cards', 'No ratings yet') }}
			</div>
		</div>
		<div v-if="categories.length > 0"
			:class="$style.categories"
			role="list"
			:aria-label="t('appstore_cards', 'Categories')">
			<div v-for="category in categories"
				:key="category"
				:class="$style.category"
				role="listitem">
				{{ category }}
			</div>
		</div>
	</article>
</template>

<style module>
.card {
	position: relative;
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	box-sizing: border-box;
	width: 100%;
	padding: calc(3 * var(--default-grid-baseline));
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
	color: var(--color-main-text);
}

.card:hover,
.card:focus-within {
	border-color: var(--color-primary-element);
}

/*
 * Host apps style links and lists inside rich text. Two classes keep the link's look, and the
 * lists use role="list" on divs, which list rules for ul and li in Talk and Text do not reach.
 */
.card .name {
	font-weight: bold;
	color: var(--color-main-text);
	text-decoration: none;
}

.card .name:hover,
.card .name:focus-visible {
	text-decoration: underline;
}

/* The link's hit area covers the whole card, while the accessible name stays short. */
.name::after {
	content: '';
	position: absolute;
	inset: 0;
}

.name:focus-visible {
	outline: none;
}

.summary {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.facts,
.categories {
	display: flex;
	flex-wrap: wrap;
	gap: var(--default-grid-baseline) calc(3 * var(--default-grid-baseline));
	margin: 0;
	padding: 0;
	color: var(--color-text-maxcontrast);
}

.categories {
	gap: var(--default-grid-baseline);
}

.category {
	padding-inline: calc(2 * var(--default-grid-baseline));
	border-radius: var(--border-radius-pill);
	background-color: var(--color-background-dark);
	color: var(--color-main-text);
}
</style>
