<?php

namespace Config;

use Lib\Helpers\HttpStatus;
use Lib\Logger\Logger;
use Lib\Response\ApiProblem;
use OpenSwoole\Coroutine;
use Phalcon\Db\Adapter\Pdo\Postgresql;
use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;
use Phalcon\Di\FactoryDefault\Cli;

class Services
{

    protected static $envs;

    protected ?Di $di = null;
    /**
     * @var \Phalcon\Config\Config
     */
    protected $config;

    protected ?Database $database;

    public function __construct(?Database $database = null)
    {
        $this->database = $database;
    }

    public function load($cli = false)
    {
        if ($cli)
            $this->di = new Cli;
        else
            $this->di = new FactoryDefault;
        $this->setup_di();
        return $this->di;
    }

    public function enable_test()
    {
        $this->base_config(Environment::load_object(Environment::DATABASE_TEST));
        $this->setup_database();
    }

    public function get_config()
    {
        return $this->config;
    }

    protected function base_config($config)
    {
        defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
        defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');
        $this->config = $config;
    }


    public function setup_migration_database()
    {
        Di::setDefault($this->di);
        $config = $this->config;
        $config = new \Phalcon\Config\Config([
            'database' => [
                'adapter' => $config->adapter,
                'host' => $config->host,
                'port' => $config->port,
                'username' => $config->username,
                'password' => $config->password,
                'dbname' => $config->dbname
            ],
            'application' => [
                'logInDb' => true,
            ],
        ]);
        $this->config = $config;
        $this->di->set('config', function () use ($config) {
            return $config;
        });

        $this->di->setShared('db', function () use ($config) {
            $params = [
                'host' => $config->database->host,
                'username' => $config->database->username,
                'password' => $config->database->password,
                'dbname' => $config->database->dbname,
            ];

            $connection = new Postgresql($params);

            return $connection;
        });
    }

    protected function setup_database()
    {
        if (!$this->database)
            return;
        $pool = $this->database->get_pool();
        $this->di->set('db', function () use ($pool) {
            $context = Coroutine::getContext();
            $logger = Logger::get_instance();
            if (!isset($context['db'])) {
                $context['db'] = $pool->get(floatval(Environment::load_env(Environment::TIMEOUT_DB, 5)));
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
        }, false);

        $this->di->set('modelsManager',  function () {
            $manager = new Manager();
            $manager->set_timeout(Environment::load_env(Environment::TIMEOUT_DB, 5));
            return $manager;
        });

        $this->di->set('request', function () {
            $context = Coroutine::getContext();
            return $context['request'];
        });
        $this->di->set('response', function () {
            $context = Coroutine::getContext();
            return $context['response'];
        });
    }

    protected function setup_di()
    {
        $this->base_config(Environment::load_object(Environment::DATABASE));
        $this->setup_database();
    }
}
