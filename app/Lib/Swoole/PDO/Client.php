<?php

declare(strict_types=1);

namespace Lib\Swoole\PDO;

use OpenSwoole\Core\Coroutine\Client\PDOClient as SwoolePDOClient;
use OpenSwoole\Core\Coroutine\Client\PDOConfig;
use PDO;
use PDOException;
use Phalcon\Db\Adapter\Pdo\Postgresql;

class Client extends SwoolePDOClient
{
    public const IO_METHOD_REGEX = '/^query|prepare|exec|beginTransaction|commit|rollback$/i';

    public const IO_ERRORS = [
        '08000',    //CONNECTION EXCEPTION	connection_exception
        '08003',    //CONNECTION DOES NOT EXIST	connection_does_not_exist
        '08006',    //CONNECTION FAILURE	connection_failure
        '08001',    //SQLCLIENT UNABLE TO ESTABLISH SQLCONNECTION	sqlclient_unable_to_establish_sqlconnection
        '08004',    //SQLSERVER REJECTED ESTABLISHMENT OF SQLCONNECTION	sqlserver_rejected_establishment_of_sqlconnection
        '08007',    //TRANSACTION RESOLUTION UNKNOWN	transaction_resolution_unknown
    ];

    /** @var Postgresql */
    protected object $__object;

    /** @var array|null */
    protected $setAttributeContext;

    /** @var int */
    protected $round = 0;

    /** @var PDOConfig */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->makeClient();
        $this->__object->getInternalHandler()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        for ($n = 3; $n--;) {
            $ret = $this->__object->{$name}(...$arguments);
            if ($ret === false) {
                /* non-IO method */
                if (!preg_match(static::IO_METHOD_REGEX, $name)) {
                    break;
                }
                $errorInfo = $this->__object->getErrorInfo();
                /* no more chances or non-IO failures */
                if (
                    !in_array($errorInfo[1], static::IO_ERRORS, true)
                    || $n === 0
                    || $this->__object->isUnderTransaction()
                ) {
                    /* '00000' means “no error.”, as specified by ANSI SQL and ODBC. */
                    if (!empty($errorInfo) && $errorInfo[0] !== '00000') {
                        $exception            = new PDOException($errorInfo[2], $errorInfo[1]);
                        $exception->errorInfo = $errorInfo;
                        throw $exception;
                    }
                    /* no error info, just return false */
                    break;
                }
                $this->reconnect();
                continue;
            }
            /** @var \Phalcon\Db\Result\PdoResult $ret */
            if ((strcasecmp($name, 'prepare') === 0) || (strcasecmp($name, 'query') === 0)) {
                $ret = new StatementProxy($ret, $this->__object, $this);
            }
            break;
        }
        /* @noinspection PhpUndefinedVariableInspection */
        return $ret;
    }

    public function getRound(): int
    {
        return $this->round;
    }

    public function reconnect(): void
    {
        $this->makeClient();
        $this->round++;
        /* restore context */
        if ($this->setAttributeContext) {
            foreach ($this->setAttributeContext as $attribute => $value) {
                $this->__object->getInternalHandler()->setAttribute($attribute, $value);
            }
        }
    }

    public function heartbeat(): void
    {
        $this->__object->query('SELECT 1')->fetch();
    }

    public function setAttribute(int $attribute, $value): bool
    {
        $this->setAttributeContext[$attribute] = $value;
        return $this->__object->getInternalHandler()->setAttribute($attribute, $value);
    }

    public function inTransaction(): bool
    {
        return $this->__object->isUnderTransaction();
    }

    protected function makeClient()
    {
        // $driver = $this->config->getDriver();
        $client = new Postgresql([
            'host' => $this->config->getHost(),
            "dbname"   => $this->config->getDbname(),
            "port"     => $this->config->getPort(),
            "username" => $this->config->getUsername(),
            "password" => $this->config->getPassword(),
        ]);
        $this->__object = $client;
    }
}
