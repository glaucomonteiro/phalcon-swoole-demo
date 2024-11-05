#!/bin/bash

source vars.sh

docker build . --tag $CONTAINER_TAG

cd ..

docker rm --force $CONTAINER_NAME

docker run -d -v ./:/main \
        -w /main \
        -v ./config/php.ini:/usr/local/etc/php/conf.d/local.ini \
        --user $UID --name $CONTAINER_NAME --net $NETWORK --ip $IP --restart unless-stopped  $CONTAINER_TAG
