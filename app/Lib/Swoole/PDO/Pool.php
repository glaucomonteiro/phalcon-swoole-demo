<?php

declare(strict_types=1);

namespace Lib\Swoole\PDO;

use OpenSwoole\Coroutine\Channel;

class Pool
{
    public const DEFAULT_SIZE = 16;

    private $pool;

    private $num;

    private $active;

    private $shutdown = false;

    public function __construct(private $factory, private $config, private int $size = self::DEFAULT_SIZE)
    {
        $this->pool    = new Channel($this->size);
        $this->num     = 0;
    }

    public function fill(): void
    {
        while ($this->size > $this->num) {
            $this->make();
        }
    }

    public function get(float $timeout = -1)
    {
        if ($this->pool->isEmpty() && $this->num < $this->size) {
            $this->make();
        }

        $this->active++;

        return $this->pool->pop($timeout);
    }

    public function put($connection, $isNew = false): void
    {
        if ($this->pool === null) {
            return;
        }
        if ($connection !== null) {
            $this->pool->push($connection);

            if (!$isNew) {
                $this->active--;
            }
        } else {
            $this->num -= 1;
            $this->make();
        }
    }

    public function close()
    {
        if (!$this->pool) {
            return false;
        }
        while (1) {
            if ($this->active > 0) {
                sleep(1);
                continue;
            }
            if (!$this->pool->isEmpty()) {
                $client = $this->pool->pop();
                $client->close();
            } else {
                break;
            }
        }

        $this->pool->close();
        $this->pool = null;
        $this->num  = 0;
    }

    public function shutdown()
    {
        $this->shutdown = true;
        if (!$this->pool) {
            return false;
        }
        go(function () {

            while (1) {
                if ($this->active > 0) {
                    sleep(1);
                    continue;
                }
                if (!$this->pool->isEmpty()) {
                    $client = $this->pool->pop();
                    $client->close();
                } else {
                    break;
                }
            }

            $this->pool->close();
            $this->pool = null;
            $this->num  = 0;
        });
    }

    protected function make()
    {
        $this->num++;
        $client = $this->factory::make($this->config);
        $this->put($client, true);
    }

    public function heartbeat()
    {
        if ($this->pool && !$this->shutdown) {
            $clients = [];
            while ($this->pool->length() > 0) {
                $client = $this->get();
                $client->heartbeat();
                $clients[] = $client;
            }
            foreach ($clients as $client) {
                $this->put($client);
            }
        }
    }
}
