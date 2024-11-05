<?php

use Config\Services;
use Phalcon\Config\Config;

define('BASE_PATH', dirname(__DIR__, 3));
define('APP_PATH', BASE_PATH . '/app');
include APP_PATH . '/loader.php';

$service = new Services();
$service->load();
$service->setup_migration_database();
$service->enable_test();

$config = $service->get_config();
$phalcon = new Config(
    ['database' => (array) $config]
);
return $phalcon;
