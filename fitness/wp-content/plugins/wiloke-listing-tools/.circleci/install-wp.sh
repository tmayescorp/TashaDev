#!/usr/bin/env bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
./wp-cli.phar core download --version=latest
./wp-cli.phar config create --dbname=${DB_NAME} --dbuser=${DB_USER} --dbhost=${DB_HOST} --dbpass=${DB_PASSWORD} --extra-php --skip-check

