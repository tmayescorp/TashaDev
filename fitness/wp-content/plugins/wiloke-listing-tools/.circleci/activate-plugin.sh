#!/usr/bin/env bash
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
./wp-cli.phar plugin activate ${PLUGIN_NAME}

## Activating a list of plugins
i=1;
for plugin in "$@"
do
    ./wp-cli.phar plugin activate $plugin
    composer dump-autoload
    i=$((i+1))
done
