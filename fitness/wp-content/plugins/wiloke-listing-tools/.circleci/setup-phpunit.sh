#!/usr/bin/env bash
cd ./${MY_PLUGIN_PATH} && ./vendor/bin/wilokecli make:unittest plugins ${PLUGIN_NAME} \
--homeurl=${WP_URL} --rb=${REST_BASE} --testnamespace=${TEST_NAMESPACE} --admin_username=${WP_ADMIN} --admin_password=${WP_ADMIN}
