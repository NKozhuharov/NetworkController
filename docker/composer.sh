#!/usr/bin/env bash

if [ -z "$1" ]; then
  echo "Set a composer command!"
  exit 1
fi

COMPOSER_COMMAND=$1
composer dump-autoload
cd $HOME/Projects/NetworkController/docker/compose

eval $(ssh-agent)
USERNAME=$(whoami)
USER=$(id -u):$(id -g)
COMPOSER_COMMAND=$COMPOSER_COMMAND docker-compose run --rm composer
