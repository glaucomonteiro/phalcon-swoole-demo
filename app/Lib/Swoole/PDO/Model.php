<?php

declare(strict_types=1);

namespace Lib\Swoole\PDO;

use Lib\Models\Base;
use Lib\Swoole\Query\Builder;
use OpenSwoole\Coroutine;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Model\ResultsetInterface;

class Model extends Base
{

    public function getColumnMap()
    {
        return $this->getModelsMetaData()->getColumnMap($this);
    }

    /**
     * @param \Phalcon\Mvc\Model\MetaDataInterface $metaData
     * @param \Lib\Swoole\PDO\Client | \Phalcon\Db\Adapter\AdapterInterface $connection
     */
    protected function has($metaData, $connection): bool
    {
        if ($connection::class === Client::class)
            return parent::has($metaData, $connection->__getObject());
        return parent::has($metaData, $connection);
    }

    /**
     * @param \Phalcon\Mvc\Model\MetaDataInterface $metaData
     * @param \Lib\Swoole\PDO\Client | \Phalcon\Db\Adapter\AdapterInterface $connection
     */
    protected function doLowInsert($metaData, $connection, $table, $identityField): bool
    {
        if ($connection::class === Client::class)
            return parent::doLowInsert($metaData, $connection->__getObject(), $table, $identityField);
        return parent::doLowInsert($metaData, $connection, $table, $identityField);
    }


    /**
     * Sends a pre-build UPDATE SQL statement to the relational database system
     *
     * @param string|array $table
     * @param \Phalcon\Mvc\Model\MetaDataInterface $metaData
     * @param \Lib\Swoole\PDO\Client | \Phalcon\Db\Adapter\AdapterInterface $connection
     * @return bool
     */
    protected function doLowUpdate(\Phalcon\Mvc\Model\MetaDataInterface $metaData, $connection, $table): bool
    {
        if ($connection::class === Client::class)
            return parent::doLowUpdate($metaData, $connection->__getObject(), $table);
        return parent::doLowUpdate($metaData, $connection, $table);
    }

    public static function find($parameters = null): ResultsetInterface
    {
        $class = static::class;
        $model = new $class();
        $query = new Builder($model->getSchema() . '.');
        $query->set_model($model)
            ->from($model->getSource())
            ->set_values($parameters['bind'] ?? [])
            ->limit($parameters['limit'] ?? 9999, $parameters['offset'] ?? 0)
            ->set_map($model->getColumnMap());
        if (isset($parameters['conditions']))
            $query->where($parameters['conditions']);
        if (isset($parameters['order']))
            $query->order([
                $parameters['order']
            ]);
        return $query->execute(true);
    }

    public static function findFirst($parameters = null)
    {
        $class = static::class;
        $model = new $class();
        $query = new Builder($model->getSchema() . '.');
        $query->set_model($model)
            ->set_map($model->getColumnMap())
            ->from($model->getSource())
            ->set_values($parameters['bind'] ?? [])
            ->limit(1, 0);
        if (isset($parameters['conditions']))
            $query->where($parameters['conditions']);
        $results = $query->execute(true);
        if ($results->count() == 0)
            return false;
        $model->setup($results->toArray()[0]);
        return $model;
    }

    public function getDI(): DiInterface
    {
        if (Coroutine::getCid() == -1)
            return parent::getDI();
        $context = Coroutine::getContext();
        return $context['di'];
    }
}
