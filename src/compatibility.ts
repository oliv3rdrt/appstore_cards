/**
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface MajorVersionRange {
	min: number | null
	max: number | null
}

/**
 * Reads the major Nextcloud versions from an App Store version range such as ">=32 <=35",
 * ">=35.0.2 <=35" or ">=30 <36". A bound the range does not set is null.
 *
 * @param spec the release's raw platform version range
 */
export function majorVersionRange(spec: string): MajorVersionRange {
	const lower = spec.match(/>=\s*(\d+)/)
	const upperInclusive = spec.match(/<=\s*(\d+)/)
	const upperExclusive = spec.match(/<\s*(\d+)(?:\.0)*(?!\.)\b/)

	let max: number | null = null
	if (upperInclusive) {
		max = Number(upperInclusive[1])
	} else if (upperExclusive) {
		max = Number(upperExclusive[1]) - 1
	}

	return { min: lower ? Number(lower[1]) : null, max }
}
