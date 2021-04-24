#!/usr/bin/env bash
set -Eeuo pipefail

if ! [ -f '/usr/local/bin/wp-cmd' ]; then
	curl https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar > /tmp/wp-cmd
	mv /tmp/wp-cmd /usr/local/bin/wp-cmd
	chmod +x /usr/local/bin/wp-cmd
fi

export WP_CLI_CACHE_DIR=/tmp
export WP_CLI_PACKAGES_DIR=/tmp

runuser -p -u www-data /usr/local/bin/wp-cmd "$@"
