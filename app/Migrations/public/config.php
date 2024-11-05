<?php

use Config\Services;

define('BASE_PATH', dirname(__DIR__, 3));
define('APP_PATH', BASE_PATH . '/app');
include APP_PATH . '/loader.php';


$service = new Services();
$service->load();
$service->setup_migration_database();
$config = $service->get_config();
unset($config->poolsize);
return $config;
