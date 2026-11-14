/**
 * SPDX-FileCopyrightText: 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { configureNextcloud, runOcc } from '@nextcloud/e2e-test-server/docker'
import { expect, test as setup } from '@playwright/test'
import { SERVER_BRANCH } from './server-branch.ts'

/**
 * Prepares the container once the web server answers, which happens before it is configured.
 * Text and Viewer come from the server's branch, Talk from the App Store.
 */
setup('Configure Nextcloud', async () => {
	setup.setTimeout(5 * 60 * 1000)

	// The container keeps its master branch when checking out SERVER_BRANCH fails, for example on a
	// network error, so make sure the tests really run on Nextcloud 35.
	const { stdout } = await runOcc(['status', '--output=json'])
	expect(JSON.parse(stdout).versionstring, 'Nextcloud version of the test container').toMatch(/^35\./)

	// The helper only remembers the branch in the process that started the container, so pass it.
	await configureNextcloud(['viewer', 'text', 'spreed', 'appstore_cards'], SERVER_BRANCH)

	// The cards need the catalogue, so this downloads it from the real App Store once.
	await runOcc(['appstore_cards:refresh'], { verbose: true })
})
