<?php

namespace Lib\Logger;

use Phalcon\Logger\Adapter\AdapterInterface;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Logger\Logger as PhalconLogger;

class Logger extends PhalconLogger
{

    protected $transaction_active;

    protected static $instance;

    public static function get_instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {
        $stream = new Stream('php://stdout');
        $stream->setFormatter(new Formatter());
        parent::__construct('logger', [
            'main' => $stream
        ]);
        $this->transaction_active = false;
    }

    public function test()
    {
        $stream = new Stream('php://stdout');
        $stream->setFormatter(new Formatter());
        parent::__construct('logger', [
            'main' => $stream
        ]);
    }

    protected function encode_message($message)
    {
        if (is_string($message)) {
            return $message;
        }
        return json_encode($message);
    }

    public function log($level, $message = null, array $context = null): void
    {
        if ($this->getLogLevel() < $level)
            return;
        $message = $this->encode_message($message);
        parent::log($level, $message, $context);
    }

    public function info($message, array $context = []): void
    {
        if ($this->getLogLevel() < PhalconLogger::INFO)
            return;
        $message = $this->encode_message($message);
        parent::info($message, $context);
    }

    public function debug($message, array $context = []): void
    {
        if ($this->getLogLevel() < PhalconLogger::DEBUG)
            return;
        $message = $this->encode_message($message);
        parent::debug($message, $context);
    }

    public function alert($message, array $context = []): void
    {
        if ($this->getLogLevel() < PhalconLogger::ALERT)
            return;
        $message = $this->encode_message($message);
        parent::alert($message, $context);
    }

    public function critical($message, array $context = []): void
    {
        if ($this->getLogLevel() < PhalconLogger::CRITICAL)
            return;
        $message = $this->encode_message($message);
        parent::critical($message, $context);
    }

    public function emergency($message, array $context = []): void
    {
        if ($this->getLogLevel() < PhalconLogger::EMERGENCY)
            return;
        $message = $this->encode_message($message);
        parent::emergency($message, $context);
    }

    public function error($message, array $context = []): void
    {
        if ($this->getLogLevel() < PhalconLogger::ERROR)
            return;
        $message = $this->encode_message($message);
        parent::error($message, $context);
    }

    public function notice($message, array $context = []): void
    {
        if ($this->getLogLevel() < PhalconLogger::NOTICE)
            return;
        $message = $this->encode_message($message);
        parent::notice($message, $context);
    }

    public function warning($message, array $context = []): void
    {
        if ($this->getLogLevel() < PhalconLogger::WARNING)
            return;
        $message = $this->encode_message($message);
        parent::warning($message, $context);
    }


    public function begin(): AdapterInterface
    {
        $this->transaction_active = true;
        return $this->getAdapter('main')->begin();
    }

    public function commit(): AdapterInterface
    {
        if ($this->transaction_active) {
            $this->transaction_active = false;
            return $this->getAdapter('main')->commit();
        }
        return $this->getAdapter('main');
    }
}
