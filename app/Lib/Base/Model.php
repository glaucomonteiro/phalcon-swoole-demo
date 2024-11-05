<?php

namespace Lib\Base;

use Phalcon\Encryption\Security\Random;
use Phalcon\Mvc\Model as PhalconModel;
use Phalcon\Mvc\ModelInterface;

class Model extends PhalconModel implements ModelInterface
{
    public $created_at;
    public $updated_at;
    public $deleted_at;
    public $id;
    public function sanitize() {}

    public function initialize() {}

    public function create($data = null, $whiteList = null): bool
    {
        $this->sanitize();
        return parent::create($data, $whiteList);
    }

    public function save($data = null, $whiteList = null): bool
    {
        $this->sanitize();
        return parent::save($data, $whiteList);
    }

    public function update($data = null, $whiteList = null): bool
    {
        $this->sanitize();
        return parent::update($data, $whiteList);
    }

    public function soft_delete(): bool
    {
        $this->deleted_at = 'now()';
        return parent::update();
    }

    public function delete(): bool
    {
        return parent::delete();
    }

    public function generate_id()
    {
        $random = new Random();
        $this->id = $random->uuid();
    }
}
