#!/usr/bin/env bash
for plugin in "$@"
do
  echo "Downloading $plugin"
#  curl -X POST --header "Content-Type: application/json" -d '{"type":"github-user-key"}' https://circleci.com/api/v1/project/wilokecom/${plugin}/checkout-key?circle-token=b9:bf:9f:b6:19:80:23:a3:c2:9a:5a:bd:cc:41:28:01
#  git clone https://circleci.com/api/v1/project/wilokecom/${plugin} && \
  git clone git@github.com:wilokecom/${plugin} ${PLUGIN_PATH}/${plugin}
done
