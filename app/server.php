<?php

declare(strict_types=1);

error_reporting(E_ALL);

define('APP_PATH', '/main/app');

use Config\App;
use Config\Environment;
use Config\Database;
use Config\Services;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Server;
use Lib\Logger\Logger;
use Lib\Swoole\File\Watcher;
use OpenSwoole\Constant;
use OpenSwoole\Coroutine;
use OpenSwoole\Runtime;
use OpenSwoole\Server\Task;
use OpenSwoole\Timer;
use Phalcon\Di\Di;

require_once '../vendor/autoload.php';
require_once './Config/Environment.php';
include_once './loader.php';

global $original_load;


$original_load = [];

$server = new Server('0.0.0.0', intval(Environment::load_env(Environment::PORT)), Server::POOL_MODE);

$server->set([
    'worker_num' => intval(Environment::load_env(Environment::WORKER)),      // The number of worker processes to start
    'task_worker_num' => intval(Environment::load_env(Environment::TASK_WORKER)),  // The amount of task workers to start
    'backlog' => intval(Environment::load_env(Environment::TCP_BACKLOG)),       // TCP backlog connection number,
    'enable_coroutine' => true,
    'dispatch_mode' => intval(Environment::load_env(Environment::DISPATCH)), // 1 or 3 for stateless, 2 or 4 for stateful. Stateless may have memory leaks
    'discard_timeout_request' => false,
    'max_request_execution_time' => 0,
    'max_request' => intval(Environment::load_env(Environment::MAX_REQUEST)),
    'log_file' => '/main/logs/swoole.logs',
    'log_rotation' => Constant::LOG_ROTATION_DAILY,
    'buffer_output_size' => 32 * 1024 * 1024, // value in bytes
    'heartbeat_idle_time' => 600,
    'heartbeat_check_interval' => 60,
    'package_max_length' => 64 * 1024 * 1024,
    'task_enable_coroutine' => true,
    'hook_flags' => Runtime::HOOK_ALL
]);


$server->on('start', function (Server $server) {
    $logger = Logger::get_instance();
    $logger->info("Swoole http server is started at http://" . $server->host . ":" . $server->port);
    $logger->info('Master PID:' . $server->getMasterPid());
    $logger->info('Manager PID:' . $server->getManagerPid());
});

$server->on('Task', function (Server $server, Task $task) {});

$server->on('Finish', function (Server $server, int $taskId, mixed $data) {
    echo "Task#$taskId finished, data_len=" . strlen($data) . PHP_EOL;
});

$server->on('BeforeReload', function (Server $serv) {
    echo 'Reloading' . PHP_EOL;
    // var_dump(get_included_files());
});

$server->on('AfterReload', function (Server $server) {
    echo 'Reloaded' . PHP_EOL;
    if (!Environment::load_env(Environment::PRODUCTION))
        $server->task([]);
    // var_dump(get_included_files());
});


$server->on('Shutdown', function (Server $server) {
    echo 'Shutdown' . PHP_EOL;
    //log callback timer
});

$server->on('WorkerStart', function (Server $server, $workerId) {
    // Files which won't be reloaded
    global $original_load;
    $original_load = get_included_files();
    if ($server->worker_id === 0 && !Environment::load_env(Environment::PRODUCTION)) {
        go(function () use ($server, $original_load) {
            $watcher = new Watcher;
            $watcher->loaded_files($original_load);
            $watcher->ignore_dirs([
                '/main/app/logs',
                '/main/app/features',
                '/main/app/docker',
                '/main/app/Test',
                '/main/app/TestData',
                '/main/app/composer-cache',
                '/main/app/config',
                '/main/app/vendor',
                '/main/app/Migrations'
            ]);
            $watcher->watch_files($server, '/main/app');
        });
    }
    $logger = Logger::get_instance();
    $database = new Database;
    $database->get_pool();
    $logger->info('Worker started', [
        'worker' => $workerId,
        'pid' => $server->getWorkerPid($workerId)
    ]);
    $second = 1000;
    $services = new Services($database);
    $di = $services->load();
    Di::setDefault($di);
    Timer::tick(30 * $second, function () {
        $database = new Database;
        $database->get_pool();
        $database->heartbeat();
    });
    $logger->setLogLevel(intval(Environment::load_env(Environment::LOG_LEVEL)));
});

// Triggered when worker processes are being stopped
$server->on("WorkerStop", function (Server $server, $workerId) {
    $database = new Database;
    $database->get_pool();
    $database->close();
});

$server->on('WorkerError', function ($server, int $workerId, int $workerPid, int $exitCode, int $signal) {
    $logger = Logger::get_instance();
    $logger->warning('Worker ' . $workerId . ' error: ' . $signal, [
        'server-class' => $server::class
    ]);
});

$server->on('request', function (Request $request, Response $response) use ($server) {
    $start = microtime(true);
    $context = Coroutine::getContext();
    $context['server'] = $server;
    $logger = Logger::get_instance();
    $response->header("Access-Control-Allow-Origin", '*');
    $response->header("Access-Control-Allow-Methods", 'GET,PUT,POST,DELETE,OPTIONS');
    $response->header("Access-Control-Allow-Headers", 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization');
    $response->header("Access-Control-Allow-Credentials", 'true');
    if ($request->getMethod() == 'OPTIONS') {
        $response->end();
        return;
    }

    $workerId = $server->getWorkerId();
    $logger->info('Request received by the worker ' . $workerId, [
        'route' => $request->server['request_uri'],
        'method' => $request->getMethod(),
        'worker' => $workerId,
        'coroutine' => Coroutine::getCid()
    ]);
    $app = new App($server);
    $app->setup($request, $response);
    $app->handle();
    $end = microtime(true);
    $total_time = $end - $start;
    if ($total_time > floatval(Environment::load_env(Environment::REQUEST_TIME))) {
        $queries = $context['queries'] ?? [];
        $logger->warning('Request finished in ' . $total_time . ' seconds', [
            'route' => $request->server['request_uri'],
            'worker' => $workerId,
            'method' => $request->getMethod(),
            'queries' => $queries
        ]);
    }
});

$server->start();
