/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

export default defineConfig({
	plugins: [vue()],
	test: {
		environment: 'jsdom',
		include: ['src/**/*.spec.ts'],
		coverage: {
			provider: 'v8',
			include: ['src/**'],
			reporter: ['text', 'lcov'],
			// ADR 13: at least 80% line coverage.
			thresholds: { lines: 80 },
		},
		server: {
			deps: {
				// Nextcloud packages ship CSS imports that Node cannot load on its own.
				inline: [/@nextcloud\//],
			},
		},
	},
})
