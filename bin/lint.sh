#!/usr/bin/env bash
#
# Run the plugin's linters entirely in Docker — no local PHP/Node needed.
# Uses the same rules as CI: phpcs.xml (PHPCS + WordPress Coding Standards)
# for PHP, and @wordpress/scripts (ESLint + Stylelint) for JS/SCSS.
#
#   bin/lint.sh           # check everything (php + js + css)
#   bin/lint.sh fix       # auto-fix what can be fixed (phpcbf + eslint/stylelint --fix)
#   bin/lint.sh php        # only PHPCS
#   bin/lint.sh php fix    # only PHPCBF (auto-fix PHP)
#   bin/lint.sh assets     # only JS + CSS
#   bin/lint.sh assets fix # auto-fix JS + CSS
#
set -euo pipefail
cd "$(dirname "$0")/.."

TARGET="${1:-all}"
MODE="${2:-check}"
# `bin/lint.sh fix` shorthand → target=all, mode=fix
if [ "$TARGET" = "fix" ]; then
	TARGET="all"
	MODE="fix"
fi

run_php() {
	echo "==> PHP (PHPCS / WordPress Coding Standards)"
	docker run --rm -v "$PWD":/app -w /app php:7.4-cli bash -c '
		set -e
		if [ ! -d vendor ]; then
			apt-get update -qq && apt-get install -y -qq git unzip >/dev/null
			curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer >/dev/null
			composer install --no-interaction --no-progress
		fi
		if [ "'"$MODE"'" = "fix" ]; then
			vendor/bin/phpcbf "$@" || true
		else
			vendor/bin/phpcs "$@"
		fi
	' bash "${@:2}"
}

run_assets() {
	echo "==> JS + CSS (@wordpress/scripts)"
	docker run --rm -v "$PWD":/app -w /app node:16 sh -c '
		[ -d node_modules ] || npm ci --no-audit --no-fund
		if [ "'"$MODE"'" = "fix" ]; then
			npm run lint:js:fix || true
			npm run lint:css:fix || true
		else
			npm run lint:js
			npm run lint:css
		fi
	'
}

case "$TARGET" in
	php) run_php "${@:3}" ;;
	assets) run_assets ;;
	all) run_php "${@:3}"; run_assets ;;
	*) echo "Unknown target: $TARGET (use: all | php | assets)"; exit 1 ;;
esac
