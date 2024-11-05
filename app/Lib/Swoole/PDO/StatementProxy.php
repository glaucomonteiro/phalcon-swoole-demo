<?php

declare(strict_types=1);

namespace Lib\Swoole\PDO;

use OpenSwoole\Core\Coroutine\Client\ClientProxy;
use PDO;
use PDOException;
use Phalcon\Db\Adapter\Pdo\Postgresql;
use \Phalcon\Db\Result\PdoResult;

class StatementProxy extends ClientProxy
{
    /** @var  PdoResult */
    protected object $__object;

    /** @var array|null */
    protected $setAttributeContext;

    /** @var array|null */
    protected $setFetchModeContext;

    /** @var array|null */
    protected $bindParamContext;

    /** @var array|null */
    protected $bindColumnContext;

    /** @var array|null */
    protected $bindValueContext;

    /** @var PDO|Client */
    protected $parent;

    /** @var int */
    protected $parentRound;

    protected Postgresql $adapter;

    public function __construct(PdoResult $object, Postgresql $adapter, Client $parent)
    {
        parent::__construct($object);
        $this->adapter = $adapter;
        $this->parent      = $parent;
        $this->parentRound = $parent->getRound();
    }

    public function __call(string $name, array $arguments)
    {
        for ($n = 3; $n--;) {
            $ret = @$this->__object->{$name}(...$arguments);
            if ($ret === false) {
                /* no IO */
                if (strtolower($name) !== 'execute') {
                    break;
                }
                /* no more chances or non-IO failures or in transaction */
                if (
                    !in_array($this->adapert->getErrorInfo()[1], $this->parent::IO_ERRORS, true)
                    || $n === 0
                    || $this->parent->inTransaction()
                ) {
                    $errorInfo = $this->adapter->getErrorInfo();

                    /* '00000' means “no error.”, as specified by ANSI SQL and ODBC. */
                    if (!empty($errorInfo) && $errorInfo[0] !== '00000') {
                        $exception            = new PDOException($errorInfo[2], $errorInfo[1]);
                        $exception->errorInfo = $errorInfo;
                        throw $exception;
                    }
                    /* no error info, just return false */
                    break;
                }
                if ($this->parent->getRound() === $this->parentRound) {
                    /* if not equal, parent has reconnected */
                    $this->parent->reconnect();
                }
                $parent         = $this->parent->__getObject();
                $this->__object = $parent->prepare($this->adapter->getInternalHandler()->queryString);
                if ($this->__object === false) {
                    $errorInfo            = $parent->errorInfo();
                    $exception            = new PDOException($errorInfo[2], $errorInfo[1]);
                    $exception->errorInfo = $errorInfo;
                    throw $exception;
                }
                if ($this->setAttributeContext) {
                    foreach ($this->setAttributeContext as $attribute => $value) {
                        $this->__object->setAttribute($attribute, $value);
                    }
                }
                if ($this->setFetchModeContext) {
                    $this->__object->setFetchMode(...$this->setFetchModeContext);
                }
                if ($this->bindParamContext) {
                    foreach ($this->bindParamContext as $param => $item) {
                        $this->__object->bindParam($param, ...$item);
                    }
                }
                if ($this->bindColumnContext) {
                    foreach ($this->bindColumnContext as $column => $item) {
                        $this->__object->bindColumn($column, ...$item);
                    }
                }
                if ($this->bindValueContext) {
                    foreach ($this->bindValueContext as $value => $item) {
                        $this->__object->bindParam($value, ...$item);
                    }
                }
                continue;
            }
            break;
        }
        /* @noinspection PhpUndefinedVariableInspection */
        return $ret;
    }

    public function setAttribute(int $attribute, $value): bool
    {
        $this->setAttributeContext[$attribute] = $value;
        return $this->adapter->getInternalHandler()->setAttribute($attribute, $value);
    }

    public function setFetchMode(int $mode, ...$args): bool
    {
        $this->setFetchModeContext = func_get_args();
        return $this->__object->setFetchMode(...$this->setFetchModeContext);
    }

    public function bindParam($parameter, &$variable, $data_type = PDO::PARAM_STR, $length = 0, $driver_options = null): bool
    {
        $this->bindParamContext[$parameter] = [$variable, $data_type, $length, $driver_options];
        return $this->adapter->getInternalHandler()->bindParam($parameter, $variable, $data_type, $length, $driver_options);
    }

    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null): bool
    {
        $this->bindColumnContext[$column] = [$param, $type, $maxlen, $driverdata];
        return $this->adapter->getInternalHandler()->bindColumn($column, $param, $type, $maxlen, $driverdata);
    }

    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR): bool
    {
        $this->bindValueContext[$parameter] = [$value, $data_type];
        return $this->adapter->getInternalHandler()->bindValue($parameter, $value, $data_type);
    }
}
