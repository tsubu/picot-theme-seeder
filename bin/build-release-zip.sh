#!/usr/bin/env bash
# Build a WordPress.org plugin submission ZIP (excludes screenshots, dev files, .po/.pot).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN_SLUG="$(basename "$ROOT")"
PARENT="$(dirname "$ROOT")"
OUT_ZIP="${PARENT}/${PLUGIN_SLUG}.zip"
DISTIGNORE="${ROOT}/.distignore"

if [[ ! -f "$DISTIGNORE" ]]; then
	echo "Missing .distignore: $DISTIGNORE" >&2
	exit 1
fi

BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

rsync -a \
	--exclude-from="$DISTIGNORE" \
	--exclude='.idea' \
	--exclude='.vscode' \
	--exclude='.github' \
	"$ROOT/" "$BUILD_DIR/$PLUGIN_SLUG/"

rm -f "$OUT_ZIP"
(
	cd "$BUILD_DIR"
	zip -r -9 "$OUT_ZIP" "$PLUGIN_SLUG" \
		-x "*.DS_Store" "*/._*" "*/screenshot/*"
)

echo "Built: $OUT_ZIP"
ls -lh "$OUT_ZIP"
ZIP_LISTING="$(unzip -l "$OUT_ZIP")"
echo "$ZIP_LISTING" | tail -3

if echo "$ZIP_LISTING" | grep -qE 'screenshot/|\.git/|/bin/|\.pot$|\.po$|messages\.mo'; then
	echo "ERROR: forbidden paths found in zip" >&2
	exit 1
fi

if ! echo "$ZIP_LISTING" | grep -Fq 'assets/vendor/bootstrap/bootstrap.min.css'; then
	echo "ERROR: bundled vendor assets missing from zip" >&2
	exit 1
fi

echo "OK: no screenshot/, .git/, bin/, .po, .pot, or messages.mo in zip"
echo "OK: bundled vendor assets present"
