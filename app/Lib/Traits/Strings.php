<?php

namespace Lib\Traits;

trait Strings
{
    protected function ends_with($string, $endString)
    {
        if (!$string)
            return false;
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }

    protected function starts_with($string, $startString)
    {
        if (!$string)
            return false;
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }
}
