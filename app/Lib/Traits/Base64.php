<?php

namespace Lib\Traits;

trait Base64
{
    public function encode_url($string)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    public function decode_url($string)
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }
}
