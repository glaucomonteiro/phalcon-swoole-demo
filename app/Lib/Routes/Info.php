<?php

namespace Lib\Routes;

use Phalcon\Mvc\Router\Route;

class Info
{
    const UUID_TEST = '00000000-0000-0000-0000-000000000000';

    public function __construct(
        public string $route,
        public string $call,
        public string $method,
        public array $allowed = []
    ) {}

    public function test()
    {
        $route = new Route($this->route);
        $paths = $route->getPaths($this->route);
        $test = str_replace(Base::UUID, self::UUID_TEST, $this->route);
        $test = str_replace(Base::NUMBER, 0, $test);
        if ($paths) {
            foreach ($paths as $path => $position) {
                $test = $this->str_replace_first('{' . $path, '1', $test);
            }
            $test = str_replace('}', '', $test);
            $test = str_replace('1:', '', $test);
        }
        $test = str_replace(':params', '?params=true', $test);
        return ['test' => $test, 'paths' => $paths];
    }

    protected function str_replace_first($from, $to, $content)
    {
        $pos = strpos($content, $from);
        if ($pos !== false) {
            $newstring = substr_replace($content, $to, $pos, strlen($from));
        }
        return $newstring;
    }
}
