<?php

use Config\Services;
use Lib\Response\ApiProblem;
use Phalcon\Cli\Console as ConsoleApp;
use Lib\Logger\Logger;
use OpenSwoole\ExitException;

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('IMAGE_PATH', BASE_PATH . '/public/img');
require_once BASE_PATH . '/vendor/autoload.php';

/**
 * Include Autoloader
 */
include APP_PATH . '/loader.php';

ini_set('memory_limit', -1);

/**
 * Process the console arguments
 */
$arguments = [];

foreach ($argv as $k => $arg) {
    if ($k === 1) {
        $arguments['task'] = $arg;
    } elseif ($k === 2) {
        $arguments['action'] = $arg;
    } elseif ($k >= 3) {
        $arguments['params'][] = $arg;
    }
}
//use go so if you call something that depends on coroutine contexts, it doesn't fail
go(function () use ($arguments) {
    global $argc, $argv;
    try {
        try {
            $services = new Services;
            $di = $services->load(true);
            $services->setup_migration_database();
            $logger = Logger::get_instance();
            $logger->setLogLevel(Logger::INFO);
            $console = new ConsoleApp();
            $console->setDI($di);
            $console->handle($arguments);
        } catch (\Exception $exception) {
            $log = array(
                'task' => $arguments['task'],
                'action' => $arguments['action'],
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            );
            if ($exception instanceof ApiProblem) {
                $messages = $exception->get_message_array();
                $logger = Logger::get_instance();
                $logger->error($exception->getMessage());
                foreach ($messages as $message) {
                    $logger->error($message);
                }
                $log['validations'] = $exception->get_message_array();
                if ($exception->debug_value)
                    $log['extras'] = $exception->debug_value->toArray();
            }
            $logger->error($exception->getMessage(), $log);
            exit(1);
        }
    } catch (ExitException $e) {
    }
});
