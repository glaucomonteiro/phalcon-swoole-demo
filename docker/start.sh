#!/bin/bash

cd /main

composer install

# vendor/bin/phalcon-migrations run --config=app/Migrations/public/config.php --migrations=app/Migrations/public
# vendor/bin/phalcon-migrations run --config=app/Migrations/system/config.php --migrations=app/Migrations/system

cd app

php server.php