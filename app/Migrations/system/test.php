<?php

use Config\Services;
use Phalcon\Config\Config;

define('BASE_PATH', dirname(__DIR__, 3));
define('APP_PATH', BASE_PATH . '/app');
include APP_PATH . '/loader.php';

$service = new Services();
$service->load();
$service->enable_test();
$service->setup_migration_database();
$config = $service->get_config();
$phalcon = new Config(
    ['database' => (array) $config]
);
$phalcon->get('database')->offsetSet('schema', 'system');
return $phalcon;
