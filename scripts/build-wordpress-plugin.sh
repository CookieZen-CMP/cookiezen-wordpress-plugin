#!/usr/bin/env bash
#
# build-wordpress-plugin.sh
# -------------------------
# Buduje poprawny artefakt ZIP wtyczki CookieZen do zgloszenia w katalogu
# WordPress.org oraz (opcjonalnie) kopiuje go do panelu klienta (cmp-app).
#
# Kroki:
#   1. Waliduje spojnosc metadanych (readme.txt <-> cookiezen.php) i reguly WP.org.
#   2. Kopiuje pliki wtyczki do dist/cookiezen/ (slug jako nazwa folderu — wymog WP.org).
#   3. Pakuje do dist/cookiezen.zip (pomija .wporg-assets/, scripts/, pliki dev).
#   4. Opcjonalnie kopiuje ZIP do panelu klienta i/lub odpytuje readme validator.
#
# Uzycie:
#   ./scripts/build-wordpress-plugin.sh
#   ./scripts/build-wordpress-plugin.sh --copy-to-panel ~/Projects/cmp-app/public/cookiezen.zip
#   ./scripts/build-wordpress-plugin.sh --validate-readme
#
# Zwraca kod != 0, jesli ktorakolwiek walidacja nie przejdzie.

set -euo pipefail

# --- Sciezki ---------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
SLUG="cookiezen"
DIST_DIR="${ROOT_DIR}/dist"
BUILD_DIR="${DIST_DIR}/${SLUG}"
ZIP_PATH="${DIST_DIR}/${SLUG}.zip"

PHP_FILE="${ROOT_DIR}/${SLUG}.php"
README_FILE="${ROOT_DIR}/readme.txt"
LICENSE_FILE="${ROOT_DIR}/LICENSE.txt"

# --- Flagi -----------------------------------------------------------------
COPY_TO_PANEL=""
VALIDATE_README="false"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --copy-to-panel)
      COPY_TO_PANEL="${2:-}"
      if [[ -z "${COPY_TO_PANEL}" ]]; then
        echo "Blad: --copy-to-panel wymaga sciezki docelowej." >&2
        exit 2
      fi
      shift 2
      ;;
    --validate-readme)
      VALIDATE_README="true"
      shift
      ;;
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *)
      echo "Nieznana flaga: $1" >&2
      exit 2
      ;;
  esac
done

# --- Kolory / logowanie ----------------------------------------------------
if [[ -t 1 ]]; then
  C_OK="\033[0;32m"; C_ERR="\033[0;31m"; C_INFO="\033[0;36m"; C_RST="\033[0m"
else
  C_OK=""; C_ERR=""; C_INFO=""; C_RST=""
fi

FAILURES=0
pass() { printf "  ${C_OK}[OK]${C_RST}   %s\n" "$1"; }
fail() { printf "  ${C_ERR}[FAIL]${C_RST} %s\n" "$1"; FAILURES=$((FAILURES + 1)); }
info() { printf "${C_INFO}==>${C_RST} %s\n" "$1"; }

# --- Ekstrakcja metadanych -------------------------------------------------
# Naglowek PHP: " * Pole: wartosc"
php_meta() {
  grep -m1 -E "^[[:space:]]*\*[[:space:]]*$1:" "${PHP_FILE}" \
    | sed -E "s/^[[:space:]]*\*[[:space:]]*$1:[[:space:]]*//" \
    | tr -d '\r' | sed -E 's/[[:space:]]+$//'
}
# Naglowek readme: "Pole: wartosc"
readme_meta() {
  grep -m1 -E "^$1:" "${README_FILE}" \
    | sed -E "s/^$1:[[:space:]]*//" \
    | tr -d '\r' | sed -E 's/[[:space:]]+$//'
}

# =====================================================================
# FAZA 1: WALIDACJE (przed budowaniem)
# =====================================================================
info "Walidacja metadanych i regul WordPress.org"

# Sprawdz obecnosc wymaganych plikow
for f in "${PHP_FILE}" "${README_FILE}" "${LICENSE_FILE}"; do
  if [[ ! -f "$f" ]]; then
    fail "Brak wymaganego pliku: $f"
  fi
done
if [[ ${FAILURES} -gt 0 ]]; then
  echo "" ; printf "${C_ERR}Przerwano: brak plikow zrodlowych.${C_RST}\n"; exit 1
fi

PHP_VERSION="$(php_meta 'Version')"
PHP_REQ_AT_LEAST="$(php_meta 'Requires at least')"
PHP_TESTED="$(php_meta 'Tested up to')"
PHP_REQ_PHP="$(php_meta 'Requires PHP')"

RM_STABLE="$(readme_meta 'Stable tag')"
RM_REQ_AT_LEAST="$(readme_meta 'Requires at least')"
RM_TESTED="$(readme_meta 'Tested up to')"
RM_REQ_PHP="$(readme_meta 'Requires PHP')"
RM_TAGS="$(readme_meta 'Tags')"

# (1) Stable tag (readme) == Version (php)
if [[ -n "${PHP_VERSION}" && "${PHP_VERSION}" == "${RM_STABLE}" ]]; then
  pass "Stable tag == Version (${PHP_VERSION})"
else
  fail "Stable tag (readme: '${RM_STABLE}') != Version (php: '${PHP_VERSION}') — zablokuje auto-update u klientow"
fi

# (2) Requires at least
if [[ -n "${PHP_REQ_AT_LEAST}" && "${PHP_REQ_AT_LEAST}" == "${RM_REQ_AT_LEAST}" ]]; then
  pass "Requires at least spojne (${PHP_REQ_AT_LEAST})"
else
  fail "Requires at least niespojne (php: '${PHP_REQ_AT_LEAST}' vs readme: '${RM_REQ_AT_LEAST}')"
fi

# (3) Tested up to
if [[ -n "${PHP_TESTED}" && "${PHP_TESTED}" == "${RM_TESTED}" ]]; then
  pass "Tested up to spojne (${PHP_TESTED})"
else
  fail "Tested up to niespojne (php: '${PHP_TESTED}' vs readme: '${RM_TESTED}')"
fi

# (4) Requires PHP
if [[ -n "${PHP_REQ_PHP}" && "${PHP_REQ_PHP}" == "${RM_REQ_PHP}" ]]; then
  pass "Requires PHP spojne (${PHP_REQ_PHP})"
else
  fail "Requires PHP niespojne (php: '${PHP_REQ_PHP}' vs readme: '${RM_REQ_PHP}')"
fi

# (5) Liczba tagow <= 5
TAG_COUNT=0
if [[ -n "${RM_TAGS}" ]]; then
  TAG_COUNT="$(echo "${RM_TAGS}" | tr ',' '\n' | sed '/^[[:space:]]*$/d' | wc -l | tr -d ' ')"
fi
if [[ "${TAG_COUNT}" -ge 1 && "${TAG_COUNT}" -le 5 ]]; then
  pass "Liczba tagow OK (${TAG_COUNT}/5)"
else
  fail "Liczba tagow poza zakresem 1..5 (jest ${TAG_COUNT}) — WP.org odrzuca >5"
fi

# (6) Brak nazw konkurencji w Tags
COMPETITORS="cookiebot iubenda onetrust complianz cookieyes termly"
FOUND_COMP=""
TAGS_LC="$(echo "${RM_TAGS}" | tr '[:upper:]' '[:lower:]')"
for c in ${COMPETITORS}; do
  if echo "${TAGS_LC}" | tr ',' '\n' | sed -E 's/^[[:space:]]+|[[:space:]]+$//g' | grep -qx "${c}"; then
    FOUND_COMP="${FOUND_COMP} ${c}"
  fi
done
if [[ -z "${FOUND_COMP}" ]]; then
  pass "Brak nazw konkurencji w Tags"
else
  fail "Tagi zawieraja nazwy konkurencji:${FOUND_COMP} (trademark violation)"
fi

if [[ ${FAILURES} -gt 0 ]]; then
  echo ""
  printf "${C_ERR}Walidacja metadanych nie przeszla (${FAILURES} bledow). Przerwano przed budowaniem.${C_RST}\n"
  exit 1
fi

# =====================================================================
# FAZA 2: BUDOWANIE ARTEFAKTU
# =====================================================================
info "Budowanie artefaktu ${SLUG}.zip"

rm -rf "${DIST_DIR}"
mkdir -p "${BUILD_DIR}"

# Kopiujemy wylacznie pliki dystrybucyjne (slug jako folder root)
cp "${PHP_FILE}"     "${BUILD_DIR}/${SLUG}.php"
cp "${ROOT_DIR}/uninstall.php" "${BUILD_DIR}/uninstall.php"
cp "${README_FILE}"  "${BUILD_DIR}/readme.txt"
cp "${LICENSE_FILE}" "${BUILD_DIR}/LICENSE.txt"

# languages/ (Domain Path) — kopiujemy zawartosc, ale bez smieci
mkdir -p "${BUILD_DIR}/languages"
if [[ -d "${ROOT_DIR}/languages" ]]; then
  # kopiuj .gitkeep oraz ewentualne pliki .po/.mo/.pot
  find "${ROOT_DIR}/languages" -maxdepth 1 -type f \
    \( -name '.gitkeep' -o -name '*.po' -o -name '*.mo' -o -name '*.pot' \) \
    -exec cp {} "${BUILD_DIR}/languages/" \;
fi

# Usun ewentualne smieci systemowe, ktore mogly sie przekopiowac
find "${BUILD_DIR}" -name '.DS_Store' -delete 2>/dev/null || true

# =====================================================================
# FAZA 3: WALIDACJE ZAWARTOSCI ZIP-a (przed spakowaniem)
# =====================================================================
info "Walidacja zawartosci artefaktu"

# (7) Brak smieci w buildzie
JUNK="$(find "${BUILD_DIR}" \( \
  -name 'node_modules' -o \
  -name '.git' -o \
  -name '.DS_Store' -o \
  -name '*.map' -o \
  -name '.env' -o -name '.env.*' \
  \) 2>/dev/null || true)"
if [[ -z "${JUNK}" ]]; then
  pass "Brak smieci (node_modules/.git/.DS_Store/*.map/.env)"
else
  fail "Wykryto smieci w buildzie:"
  echo "${JUNK}" | sed 's/^/        /'
fi

# Upewnij sie, ze .wporg-assets NIE trafily do buildu
if [[ ! -d "${BUILD_DIR}/.wporg-assets" ]]; then
  pass ".wporg-assets/ poprawnie pominiete"
else
  fail ".wporg-assets/ nie powinno byc w ZIP-ie (idzie tylko do SVN /assets/)"
fi

# Pakowanie
( cd "${DIST_DIR}" && zip -r -q -X "${SLUG}.zip" "${SLUG}" -x '*.DS_Store' )

# (8) Struktura ZIP-a: dokladnie jeden folder root = slug
ROOTS="$(unzip -Z1 "${ZIP_PATH}" | sed -E 's#/.*##' | sort -u)"
if [[ "${ROOTS}" == "${SLUG}" ]]; then
  pass "Struktura ZIP-a: jeden folder root '${SLUG}/'"
else
  fail "ZIP musi miec dokladnie jeden folder root '${SLUG}/'. Znaleziono: $(echo "${ROOTS}" | tr '\n' ' ')"
fi

if [[ ${FAILURES} -gt 0 ]]; then
  echo ""
  printf "${C_ERR}Walidacja artefaktu nie przeszla (${FAILURES} bledow).${C_RST}\n"
  exit 1
fi

# =====================================================================
# FAZA 4: OPCJE DODATKOWE
# =====================================================================

# (9) Opcjonalny readme validator WP.org
if [[ "${VALIDATE_README}" == "true" ]]; then
  info "Walidacja readme.txt w oficjalnym validatorze WP.org"
  if command -v curl >/dev/null 2>&1; then
    VALIDATOR_URL="https://wordpress.org/plugins/developers/readme-validator/"
    RESP="$(curl -s -X POST "${VALIDATOR_URL}" \
      --data-urlencode "readme_contents@${README_FILE}" \
      --data "text=1" 2>/dev/null || true)"
    if echo "${RESP}" | grep -qiE 'fatal error|errors found|Warnings'; then
      # Wyciagnij czytelne komunikaty (best-effort, HTML parsing)
      echo "${RESP}" | sed -E 's/<[^>]+>/ /g' | grep -iE 'error|warning|fatal' \
        | sed -E 's/[[:space:]]+/ /g' | sed '/^ *$/d' | head -20 | sed 's/^/        /' || true
      if echo "${RESP}" | grep -qiE 'fatal error|errors found'; then
        fail "Readme validator zglosil bledy — popraw readme.txt przed submisja"
      else
        printf "  ${C_INFO}[INFO]${C_RST} Readme validator zglosil ostrzezenia (nie blokuja publikacji)\n"
      fi
    else
      pass "Readme validator: brak bledow"
    fi
  else
    printf "  ${C_INFO}[INFO]${C_RST} Brak 'curl' — pomijam online walidacje readme\n"
  fi
fi

# Kopia do panelu klienta (cmp-app)
if [[ -n "${COPY_TO_PANEL}" ]]; then
  info "Kopiowanie ZIP-a do panelu klienta"
  PANEL_DIR="$(dirname "${COPY_TO_PANEL}")"
  if [[ -d "${PANEL_DIR}" ]]; then
    cp "${ZIP_PATH}" "${COPY_TO_PANEL}"
    pass "Skopiowano do: ${COPY_TO_PANEL}"
  else
    fail "Katalog docelowy nie istnieje: ${PANEL_DIR}"
  fi
fi

# =====================================================================
# PODSUMOWANIE
# =====================================================================
echo ""
if [[ ${FAILURES} -eq 0 ]]; then
  ZIP_SIZE="$(du -h "${ZIP_PATH}" | cut -f1 | tr -d ' ')"
  printf "${C_OK}BUILD OK${C_RST} — %s (%s), wersja %s\n" "${ZIP_PATH}" "${ZIP_SIZE}" "${PHP_VERSION}"
  echo ""
  echo "Zawartosc:"
  unzip -Z1 "${ZIP_PATH}" | sed 's/^/  /'
  exit 0
else
  printf "${C_ERR}BUILD NIEUDANY${C_RST} — ${FAILURES} bledow\n"
  exit 1
fi
