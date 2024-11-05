<?php

namespace Config;

use Lib\Swoole\PDO\Factory;
use Lib\Swoole\PDO\Pool;
use Lib\Logger\Logger;
use OpenSwoole\Core\Coroutine\Client\PDOConfig;

class Database
{

    protected static ?Pool $dbpool = null;

    protected Logger $logger;

    public function __construct()
    {
        $this->logger = Logger::get_instance();
    }

    public function get_pool()
    {
        if (self::$dbpool)
            return self::$dbpool;
        $database = Environment::load_object(Environment::DATABASE);
        $pdo = new PDOConfig;
        $pdo->withHost($database->host);
        $pdo->withUsername($database->username);
        $pdo->withPassword($database->password);
        $pdo->withPort($database->port);
        $pdo->withDbname($database->dbname);
        $pdo->withDriver('pgsql');
        $this->logger->debug('Connection pool created');
        self::$dbpool = new Pool(Factory::class, $pdo, $database->poolsize);
        return self::$dbpool;
    }

    public function close()
    {
        $this->logger->debug('Connection pool closed');
        self::$dbpool->shutdown();
    }

    public function heartbeat()
    {
        $this->logger->debug('Heartbeat on connections');
        self::$dbpool->heartbeat();
    }
}
