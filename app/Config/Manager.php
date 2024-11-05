<?php

namespace Config;

use Lib\Helpers\HttpStatus;
use Lib\Logger\Logger;
use Lib\Response\ApiProblem;
use OpenSwoole\Coroutine;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Mvc\Model\Manager as ModelManager;
use Phalcon\Mvc\ModelInterface;

class Manager extends ModelManager
{
    private float $timeout = 0.5;

    public function set_timeout(float $timeout)
    {
        $this->timeout = $timeout;
    }

    public function getWriteConnection(ModelInterface $model): AdapterInterface
    {
        $context = Coroutine::getContext();
        $logger = Logger::get_instance();
        if (!isset($context['db'])) {
            $database = new Database();
            $pool = $database->get_pool();
            $context['db'] = $pool->get($this->timeout);
            $logger->debug('Getting new db connection');
        } else {
            $logger->debug('Reusing db connection');
        }
        $db = $context['db'];
        if (!$db) {
            $context['db-error'] = true;
            $exception = new ApiProblem(
                HttpStatus::SERVER_ERROR,
                'Internal server error',
            );
            $exception->add_debug('Failed to get the database connection');
            throw $exception;
        }
        /** @var \Lib\Swoole\PDO\Client $db */
        return $db->__getObject();
    }

    public function getReadConnection(ModelInterface $model): AdapterInterface
    {
        $context = Coroutine::getContext();
        $logger = Logger::get_instance();
        if (!isset($context['db'])) {
            $database = new Database();
            $pool = $database->get_pool();
            $context['db'] = $pool->get($this->timeout);
            $logger->debug('Getting new db connection');
        } else {
            $logger->debug('Reusing db connection');
        }
        $db = $context['db'];
        if (!$db) {
            $context['db-error'] = true;
            $exception = new ApiProblem(
                HttpStatus::SERVER_ERROR,
                'Internal server error',
            );
            $exception->add_debug('Failed to get the database connection');
            throw $exception;
        }
        /** @var \Lib\Swoole\PDO\Client $db */
        return $db->__getObject();
    }
}
