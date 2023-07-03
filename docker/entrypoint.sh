#!/usr/bin/env bash
set -Eeuo pipefail

if ! [ -f "index.php" ]; then
	wp core download
	cp /usr/src/wordpress/wp-config-docker.php wp-config.php
fi

while [ "`wp config 2>&1`" == 'Error: Error establishing a database connection.' ]; do
    echo "Sleeping for 2 seconds while waiting for db startup."
	sleep 2
done

if ! $(wp core is-installed); then
    wp core install -- \
	  --url="${KRAKEN_WORDPRESS_URL}" \
	  --title="${KRAKEN_WORDPRESS_TITLE}" \
	  --admin_user="${KRAKEN_WORDPRESS_USER}" \
	  --admin_email="${KRAKEN_WORDPRESS_EMAIL}" \
	  --admin_password="${KRAKEN_WORDPRESS_PASSWORD}"
fi

/usr/local/bin/docker-entrypoint.sh "apache2-foreground"
