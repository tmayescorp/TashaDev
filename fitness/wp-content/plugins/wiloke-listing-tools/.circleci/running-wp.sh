#!/usr/bin/env bash
./wp-cli.phar core install --admin_name=${WP_ADMIN} --admin_password=${WP_PASSWORD} --admin_email=${WP_EMAIL} --url=${WP_URL} --title=WordPress
./wp-cli.phar rewrite structure /%postname%/
