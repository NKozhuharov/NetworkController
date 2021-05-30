#!/bin/bash
if [[ $(docker ps -q) ]]; then
    docker stop $(docker ps -q)
else
    echo "docker-stop-all: no running docker container found"
fi
