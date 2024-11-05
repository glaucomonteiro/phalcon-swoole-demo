<?php

namespace Config;

class Environment
{

    protected static $envs;

    /** running environment */
    const PRODUCTION = 'PRODUCTION';
    const MAINTENANCE = 'MAINTENANCE';
    const MAINTENANCE_MESSAGE = 'MAINTENANCE_MESSAGE';
    const TIMEOUT_DB = 'TIMEOUT_DB';

    /** objects */
    const DATABASE = 'DATABASE';
    const DATABASE_TEST = 'DATABASE_TEST';

    /** Swoole vars */
    const DOCKER_IP = 'DOCKER_IP';
    const PORT = 'PORT';
    const WORKER = 'WORKER';
    const TASK_WORKER = 'TASK_WORKER';
    const TCP_BACKLOG = 'TCP_BACKLOG';
    const DISPATCH = 'DISPATCH';
    const MAX_REQUEST = 'MAX_REQUEST';

    /** Logging */
    const REQUEST_TIME = 'REQUEST_TIME';
    const LOG_LEVEL = 'LOG_LEVEL';
    const LOG_CODES = 'LOG_CODES';

    public static function load_env($key, $default = null)
    {
        if (!self::$envs) {
            self::$envs = parse_ini_file('.env', true);
        }
        if (empty(self::$envs[$key]))
            return $default;
        $data = self::$envs[$key];
        return $data;
    }

    public static function load_object($key, $default = null)
    {
        if (!self::$envs) {
            self::$envs = parse_ini_file('.env', true);
        }
        if (empty(self::$envs[$key]))
            return $default;
        $data = self::$envs[$key];
        return (object) $data;
    }
}
