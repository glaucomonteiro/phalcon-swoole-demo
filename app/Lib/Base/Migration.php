<?php

namespace Lib\Sql\Migrations;

use Lib\Logger\Logger;
use Phalcon\Migrations\Mvc\Model\Migration as  PhalconMigration;
use Phalcon\Mvc\Model;
use RuntimeException;

class Migration extends PhalconMigration
{

    protected Model $model;

    protected string $table;

    protected string $schema;

    protected string $version;

    protected Logger $logger;

    public function __construct()
    {
        $this->table  = $this->model->getSource();
        $this->schema = $this->model->getSchema();
        $this->logger = Logger::get_instance();
        $this->logger->info('Starting migration for ' . $this->schema . '.' . $this->table . ' version ' . $this->version);
    }

    protected function import()
    {
        $this->logger->info('Importing data for ' . $this->schema . '.' . $this->table . ' version ' . $this->version);
        if (!defined('APP_PATH')) {
            throw new RuntimeException('APP_PATH is undefined.');
        }
        $file = APP_PATH . '/Migrations/' . $this->schema . '/' . $this->version . '/data/' . $this->table . '.json';
        if (!file_exists($file))
            throw new RuntimeException('File ' . $file . ' unavailable to import data');
        $data = json_decode(file_get_contents($file), true);
        $class = $this->model::class;
        $total = count($data);
        $this->logger->info('Total rows to be imported: ' . $total);
        $absolute = floor($total / 5);
        $percentages = [
            $absolute => '20% done',
            $absolute * 2 => '40% done',
            $absolute * 3 => '60% done',
            $absolute * 4 => '80% done',
            $total - 1 => '100% done'
        ];
        foreach ($data as $key => $item) {
            $model = new $class();
            $model->assign($item);
            try {
                $model->save();
            } catch (\Exception $e) {
                if ($e->getCode() != 55000) {
                    throw $e;
                }
            }
            if (array_key_exists($key, $percentages)) {
                $this->logger->info($percentages[$key]);
            }
        }
    }
}
