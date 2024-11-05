<?php

namespace Lib\Base;

use OpenSwoole\Coroutine;
use OpenSwoole\Coroutine\Context;
use Phalcon\Mvc\Controller as PhalconController;


class Controller extends PhalconController
{

    private ?Context $_context = null;

    public function context($info)
    {
        if (!$this->_context) {
            $this->_context = Coroutine::getContext();
        }
        return $this->_context[$info];
    }
}
