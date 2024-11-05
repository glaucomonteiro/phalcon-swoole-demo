#!/bin/bash

source $(dirname "$0")/docker/vars.sh

docker exec $CONTAINER_NAME vendor/bin/phalcon-migrations run --config=app/Migrations/system/config.php --migrations=app/Migrations/system
docker exec $CONTAINER_NAME vendor/bin/phalcon-migrations run --config=app/Migrations/public/config.php --migrations=app/Migrations/public

