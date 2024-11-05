<?php

declare(strict_types=1);

namespace Lib\Swoole\PDO;

use OpenSwoole\Core\Coroutine\Client\ClientFactoryInterface;

final class Factory implements ClientFactoryInterface
{
    public static function make($config)
    {
        return new Client($config);
    }
}
