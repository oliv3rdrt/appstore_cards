/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { describe, expect, it } from 'vitest'
import { majorVersionRange } from './compatibility.ts'

describe('majorVersionRange', () => {
	it.each([
		['>=32 <=35', { min: 32, max: 35 }],
		['>=35.0.2 <=35', { min: 35, max: 35 }],
		['>=30 <36', { min: 30, max: 35 }],
		['>=30 <36.0.0', { min: 30, max: 35 }],
		['>=33', { min: 33, max: null }],
		['>=30 <36.1.0', { min: 30, max: null }],
		['', { min: null, max: null }],
	])('reads "%s"', (spec, expected) => {
		expect(majorVersionRange(spec)).toEqual(expected)
	})
})
