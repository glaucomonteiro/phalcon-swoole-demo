<?php

namespace Models\System;

use Lib\Traits\Json;

class Role extends \Lib\Swoole\PDO\Model
{
    use Json;
    const ALL = '*';

    const GUEST = '0';

    const ADMIN = '1';

    const FREE_USER = '2';

    const PAID_USER = '3';

    public $id;

    public $role;

    public $feature_flags;

    public function initialize()
    {
        parent::initialize();
        $this->setSchema("system");
        $this->setSource("roles");
    }

    public function sanitize()
    {
        $this->feature_flags = $this->encode($this->feature_flags);
    }
}
