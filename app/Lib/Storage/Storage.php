<?php

namespace Lib\Storage;

use Phalcon\Filter\Filter;
use RuntimeException;

/**
 * Shared storage to receive parameters globally
 */
class Storage
{
    /**
     * @var self|null
     */
    protected static $instance;

    /**
     * @var \Exception
     */
    public $error;

    /**
     * @var mixed
     */
    public $response;

    /**
     * @var array
     */
    private $storage = [];

    /**
     * Used for accessing latest resource.
     *
     * @var string|null
     */
    private $latestKey;

    /**
     * @param int $type
     * @return self
     */
    public static function get_storage()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get_all()
    {
        return $this->storage;
    }

    public function get_filtered($key, array $filters)
    {
        $filter = new Filter();
        $value = $this->get($key);
        return $filter->sanitize($value, $filters);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->exists($key)) {
            throw new RuntimeException(sprintf('Item with key "%s" does not exist', $key));
        }

        return $this->storage[$key];
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get_with_default($key, $default)
    {
        if (!isset($this->storage[$key])) {
            return $default;
        }

        return $this->storage[$key];
    }

    /**
     * @param string $key
     * @param mixed $resource
     */
    public function set($key, $resource)
    {
        $this->storage[$key] = $resource;
        $this->latestKey = $key;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    /**
     * @param string|int $key
     */
    public function clear($key): void
    {
        if ($this->exists($key)) {
            unset($this->storage[$key]);
        }
    }

    /**
     * Clean all previously saved data
     */
    public function clean(): void
    {
        $this->storage = [];
        $this->latestKey = null;
        $this->response = null;
        $this->error = null;
    }

    public function set_response($data)
    {
        $this->response = json_decode(json_encode($data));
    }

    /**
     * Get the resource that was the latest one to be set into the storage.
     *
     * @return mixed
     */
    public function get_latest_resource()
    {
        if (!array_key_exists($this->latestKey, $this->storage)) {
            throw new RuntimeException(sprintf('Latest resource with key "%s" does not exist.', $this->latestKey));
        }

        return $this->storage[$this->latestKey];
    }
}
