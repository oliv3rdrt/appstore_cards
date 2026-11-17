<!--
  - SPDX-FileCopyrightText: 2026 Oliver Anyanwu
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Release checklist

How a version reaches the App Store. [ADR 15](adr/0015-versioning-and-releases.md) explains the approach. The release workflow, `.github/workflows/appstore-build-publish.yml`, packages, signs and uploads the app once a GitHub release is published.

## Once, before the first release

1. **Certificate.** Create a private key and a certificate signing request, then open a pull request that adds `appstore_cards/appstore_cards.csr` to [nextcloud/app-certificate-requests](https://github.com/nextcloud/app-certificate-requests). Nextcloud adds the signed `appstore_cards.crt` to that repository, where the release workflow downloads it. The App Store team may ask for proof that you own the app.

   ```bash
   openssl req -nodes -newkey rsa:4096 -keyout appstore_cards.key -out appstore_cards.csr -subj "/CN=appstore_cards"
   ```

2. **Keep the key safe.** Store `appstore_cards.key` in a password manager or another safe place outside the repository. Anyone with it can sign releases of this app.
3. **Register the app** at [apps.nextcloud.com/developer/apps/new](https://apps.nextcloud.com/developer/apps/new) with the certificate and this signature over the app ID:

   ```bash
   echo -n "appstore_cards" | openssl dgst -sha512 -sign appstore_cards.key | openssl base64
   ```

4. **Release environment.** In the GitHub repository, under Settings > Environments, create `release`:
   - Add yourself as a required reviewer, so every run waits for your approval.
   - Allow only tags matching `v*` to deploy to it.
   - Add two environment secrets: `APP_PRIVATE_KEY`, the whole content of `appstore_cards.key`, and `APPSTORE_TOKEN`, from My account > API token on the App Store.
5. **Private vulnerability reporting.** Turn it on under Settings > Security, so that the link in `SECURITY.md` works.

## Every release

1. **Choose the version** with [semantic versioning](https://semver.org): a fix is a patch, a new feature is a minor version.
2. **Update the version** in `appinfo/info.xml` and `package.json`.
3. **Update the changelog.** Move the entries under `[Unreleased]` into a new `## [X.Y.Z] - YYYY-MM-DD` section with the release date, and update the links at the bottom. The App Store shows this section as the release notes.
4. **Check the screenshots.** If the interface changed, retake the screenshots in `docs/screenshots/`. The App Store loads them from `main`.
5. **Commit, push and wait for CI** to pass on `main`:

   ```bash
   git commit -s -m "chore(release): X.Y.Z"
   git push
   ```

6. **Tag and push the tag.** The tag must be `v` followed by the version in `info.xml`, or the workflow stops.

   ```bash
   git tag -a vX.Y.Z -m "App Store Cards X.Y.Z"
   git push origin vX.Y.Z
   ```

7. **Publish a GitHub release** for the tag, with the changelog section as its notes.
8. **Approve the deployment** when the "Build and publish app release" workflow asks for it.
9. **Check the result:**
   - The GitHub release has `appstore_cards-vX.Y.Z.tar.gz` attached.
   - The App Store shows the new version with its release notes.
   - `occ app:update appstore_cards` on a test instance installs it, and a link preview still works.

## If something goes wrong

- **The workflow fails before uploading:** fix the cause on `main`, delete the GitHub release and the tag, then tag and publish again.
- **A broken version reached the App Store:** release a fixed patch version. Instances that already installed the broken one update to it.
- **The key leaked:** open a new certificate request that replaces the old one, as the [App Store documentation](https://nextcloudappstore.readthedocs.io/en/latest/developer.html) describes, and replace the `APP_PRIVATE_KEY` secret. Registering the new certificate deletes all existing releases from the App Store.
