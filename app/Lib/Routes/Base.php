<?php

namespace Lib\Routes;

use Phalcon\Mvc\Router;

class Base
{

    const UUID = '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}';
    const NUMBER = '\d+';

    public function list_routes()
    {
        $list = [];
        $routes = new General();
        $list = array_merge($list, $routes->list_routes());
        return $list;
    }

    public function add_routes(Router $router)
    {
        foreach ($this->list_routes() as $controller => $routes) {
            foreach ($routes as $route) {
                $router->add(
                    $route->route,
                    [
                        'controller' => $controller,
                        'action' => $route->call
                    ],
                    [
                        $route->method
                    ]
                );
            }
        }
        return $router;
    }
}
