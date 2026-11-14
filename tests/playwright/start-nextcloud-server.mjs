/**
 * SPDX-FileCopyrightText: 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 * SPDX-FileCopyrightText: 2026 Oliver Anyanwu
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Starts the Nextcloud container that Playwright tests against, with this app mounted,
// and removes it when Playwright stops this process.

import { docker, startNextcloud, stopNextcloud } from '@nextcloud/e2e-test-server/docker'
import { SERVER_BRANCH } from './support/server-branch.ts'

// The example script derives the branch from max-version, but the app's max-version (36)
// is still the server's master branch.
await startNextcloud(SERVER_BRANCH, true, { exposePort: 8089 })

process.on('beforeExit', async () => {
	await stopNextcloud({ saveLogTo: 'playwright-report/nextcloud.log' })
	await docker.getVolume('apps_writable').remove()
})

// Stay alive until Playwright ends the tests.
while (true) {
	await new Promise((resolve) => setTimeout(resolve, 5000))
}
