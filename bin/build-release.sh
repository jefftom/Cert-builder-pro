#!/usr/bin/env bash
#
# build-release.sh
#
# Produces an installable CertBuilder Pro plugin zip in dist/.
#
# It runs the three setup steps that generate the artifacts kept out of git
# (Composer vendor/, the compiled React builder, and the TTF fonts), then
# copies only the runtime files into a clean staging directory and zips it.
#
# Usage:  bin/build-release.sh
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="certbuilder-pro"
DIST_DIR="${ROOT_DIR}/dist"
STAGE_DIR="${DIST_DIR}/${SLUG}"

VERSION="$(grep -oiE "Version:\s*[0-9][0-9a-zA-Z.\-]*" "${ROOT_DIR}/${SLUG}.php" | head -1 | awk '{print $2}')"
VERSION="${VERSION:-dev}"

echo "==> Building ${SLUG} ${VERSION}"

# 1. PHP dependencies (production only).
echo "==> composer install (--no-dev)"
( cd "${ROOT_DIR}" && composer install --no-dev --optimize-autoloader --no-interaction --quiet )

# 2. Compile the React builder.
echo "==> Building visual builder (npm)"
( cd "${ROOT_DIR}/builder" && npm ci --silent && npm run build --silent )

# 3. Fetch fonts.
echo "==> Downloading fonts"
"${ROOT_DIR}/bin/download-fonts.sh" > /dev/null

# 4. Stage runtime files only (tar copy with excludes; rsync may be absent).
echo "==> Staging files"
rm -rf "${STAGE_DIR}"
mkdir -p "${STAGE_DIR}"

tar -cf - -C "${ROOT_DIR}" \
	--exclude='./.git' \
	--exclude='./.github' \
	--exclude='./.gitignore' \
	--exclude='./dist' \
	--exclude='./bin' \
	--exclude='./node_modules' \
	--exclude='./builder/src' \
	--exclude='./builder/node_modules' \
	--exclude='./builder/package.json' \
	--exclude='./builder/package-lock.json' \
	--exclude='./builder/webpack.config.js' \
	--exclude='./composer.lock' \
	--exclude='./tests' \
	--exclude='*.map' \
	. | tar -xf - -C "${STAGE_DIR}"

# 5. Zip it.
echo "==> Creating zip"
ZIP_PATH="${DIST_DIR}/${SLUG}-${VERSION}.zip"
rm -f "${ZIP_PATH}"
( cd "${DIST_DIR}" && zip -rq "${ZIP_PATH}" "${SLUG}" )
rm -rf "${STAGE_DIR}"

echo ""
echo "==> Done: ${ZIP_PATH} ($(du -h "${ZIP_PATH}" | cut -f1))"
