#!/usr/bin/env bash
#
# download-fonts.sh
#
# Downloads the certificate fonts bundled with CertBuilder Pro into
# assets/fonts/. The files and naming here MUST stay in sync with the
# registry in includes/Core/Fonts.php.
#
# Fonts are fetched from the Google Fonts CSS2 API using a legacy
# User-Agent, which makes Google return a single, complete static TrueType
# instance per weight/style (no unicode-range subsetting). Static instances
# are required because TCPDF cannot select a weight from a variable font.
#
# All fonts are licensed under the SIL Open Font License or Apache 2.0 and
# are redistributable. See assets/fonts/README.md for details.
#
# Usage:  bin/download-fonts.sh
#
set -euo pipefail

# Resolve plugin root (parent of this script's directory).
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FONTS_DIR="${ROOT_DIR}/assets/fonts"
USER_AGENT="Mozilla/5.0"
API="https://fonts.googleapis.com/css2"

mkdir -p "${FONTS_DIR}"

# Map a variant name to its CSS2 axis query.
variant_query() {
	case "$1" in
		regular)    echo "wght@400" ;;
		bold)       echo "wght@700" ;;
		italic)     echo "ital,wght@1,400" ;;
		bolditalic) echo "ital,wght@1,700" ;;
		*) echo "" ;;
	esac
}

# Each entry: "<target-basename>|<Google+Family>|<variant>"
# Target basenames match includes/Core/Fonts.php exactly.
FONTS=(
	# Serif
	"playfair-display|Playfair+Display|regular"
	"playfair-display|Playfair+Display|bold"
	"playfair-display|Playfair+Display|italic"
	"playfair-display|Playfair+Display|bolditalic"
	"lora|Lora|regular"
	"lora|Lora|bold"
	"lora|Lora|italic"
	"lora|Lora|bolditalic"
	"merriweather|Merriweather|regular"
	"merriweather|Merriweather|bold"
	"merriweather|Merriweather|italic"
	"merriweather|Merriweather|bolditalic"
	"crimson-text|Crimson+Text|regular"
	"crimson-text|Crimson+Text|bold"
	"crimson-text|Crimson+Text|italic"
	"cormorant-garamond|Cormorant+Garamond|regular"
	"cormorant-garamond|Cormorant+Garamond|bold"
	"cormorant-garamond|Cormorant+Garamond|italic"
	# Sans-serif
	"montserrat|Montserrat|regular"
	"montserrat|Montserrat|bold"
	"montserrat|Montserrat|italic"
	"montserrat|Montserrat|bolditalic"
	"open-sans|Open+Sans|regular"
	"open-sans|Open+Sans|bold"
	"open-sans|Open+Sans|italic"
	"open-sans|Open+Sans|bolditalic"
	"raleway|Raleway|regular"
	"raleway|Raleway|bold"
	"raleway|Raleway|italic"
	"raleway|Raleway|bolditalic"
	"roboto|Roboto|regular"
	"roboto|Roboto|bold"
	"roboto|Roboto|italic"
	"roboto|Roboto|bolditalic"
	"lato|Lato|regular"
	"lato|Lato|bold"
	"lato|Lato|italic"
	"lato|Lato|bolditalic"
	"poppins|Poppins|regular"
	"poppins|Poppins|bold"
	"poppins|Poppins|italic"
	"poppins|Poppins|bolditalic"
	# Handwriting / script
	"great-vibes|Great+Vibes|regular"
	"dancing-script|Dancing+Script|regular"
	"dancing-script|Dancing+Script|bold"
	"pacifico|Pacifico|regular"
	"alex-brush|Alex+Brush|regular"
	"allura|Allura|regular"
	# Display
	"cinzel|Cinzel|regular"
	"cinzel|Cinzel|bold"
)

total=0
ok=0
failed=()

for entry in "${FONTS[@]}"; do
	IFS='|' read -r base family variant <<< "${entry}"
	total=$((total + 1))

	target="${FONTS_DIR}/${base}-${variant}.ttf"
	query="$(variant_query "${variant}")"

	if [[ -z "${query}" ]]; then
		echo "  SKIP  ${base}-${variant} (unknown variant)"
		failed+=("${base}-${variant}")
		continue
	fi

	# Resolve the static TTF URL from the CSS2 response.
	css="$(curl -fsS -H "User-Agent: ${USER_AGENT}" "${API}?family=${family}:${query}&display=swap" || true)"
	url="$(echo "${css}" | grep -oE 'https://[^)]+\.ttf' | head -1 || true)"

	if [[ -z "${url}" ]]; then
		echo "  FAIL  ${base}-${variant} (no TTF in API response)"
		failed+=("${base}-${variant}")
		continue
	fi

	if curl -fsS -o "${target}" "${url}"; then
		size="$(du -h "${target}" | cut -f1)"
		echo "  OK    ${base}-${variant}.ttf (${size})"
		ok=$((ok + 1))
	else
		echo "  FAIL  ${base}-${variant} (download error)"
		failed+=("${base}-${variant}")
	fi
done

echo ""
echo "Downloaded ${ok}/${total} fonts into assets/fonts/"

if [[ ${#failed[@]} -gt 0 ]]; then
	echo "Failed: ${failed[*]}"
	exit 1
fi
