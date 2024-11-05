<?php

namespace Lib\Response;

use Lib\Helpers\HttpStatus;

class ApiMessage
{
    public function __construct(public $message = 'Success', public $code = HttpStatus::SUCCESS) {}

    public static function success($message = 'Success')
    {
        return new self($message, HttpStatus::SUCCESS);
    }
}
