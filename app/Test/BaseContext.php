<?php

namespace Test;

use Behat\Behat\Context\Context;
use Config\Services;
use Lib\Logger\Logger;
use Lib\Storage\Storage;
use Phalcon\Logger\Formatter\Line;
use PHPUnit\Framework\TestCase;

/**
 * Defines application features from the specific context.
 */
class BaseContext extends TestCase implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    protected Storage $storage;

    protected Logger $logger;

    protected static Logger $s_logger;

    public function __construct()
    {
        $this->storage = Storage::get_storage();
        $this->logger = Logger::get_instance();
    }

    protected static function init()
    {
        $services = new Services();
        $services->load();
        $services->enable_test();
        self::$s_logger = Logger::get_instance();
        $formatter = new Line();
        self::$s_logger->getAdapter('main')->setFormatter($formatter);
    }
}
