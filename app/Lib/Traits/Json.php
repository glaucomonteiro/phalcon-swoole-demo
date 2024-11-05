<?php

namespace Lib\Traits;

use stdClass;

trait Json
{

    protected function encode($value, $default = '{}')
    {
        try {
            if ($value == null) {
                return $default;
            }
            if (!is_string($value)) {
                $value = json_encode($value);
            }
            if ($value == '[]' || !$value)
                $value = $default;
            while (strpos($value, '\u0000') !== false)
                $value = str_replace('\u0000', 'u0000', $value);
            return $value;
        } catch (\Exception $e) {
            return $default;
        }
    }

    protected function decode($value, $default = new stdClass)
    {
        if (is_string($value))
            return json_decode($value);
        if ($value === null)
            return $default;
        return $value;
    }
}
