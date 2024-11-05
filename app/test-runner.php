<?php

declare(strict_types=1);

use OpenSwoole\ExitException;

error_reporting(E_ALL);

define('APP_PATH', '/main/app');

ini_set('date.timezone', "America/Sao_Paulo");

require_once '/main/vendor/autoload.php';
require_once APP_PATH . '/Config/Environment.php';
include_once APP_PATH . '/loader.php';

go(function () {
    global $argc, $argv;
    try {
        require '/main/vendor/bin/behat';
    } catch (ExitException $e) {
    }
});
