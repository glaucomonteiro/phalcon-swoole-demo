<?php

namespace Lib\Routes;

use Controllers\System\Developer;
use Models\System\Role;

class General extends Base
{
    public function list_routes()
    {
        return array(
            Developer::class => array(
                new Info(
                    '/',
                    'main',
                    'GET',
                    [
                        Role::GUEST
                    ]
                ),
                new Info(
                    '/test',
                    'test',
                    'post',
                    [
                        Role::GUEST
                    ]
                ),
                new Info(
                    '/restart',
                    'restart',
                    'POST',
                    [
                        Role::ADMIN
                    ]
                ),
                new Info(
                    '/reload',
                    'reload',
                    'POST',
                    [
                        Role::ADMIN
                    ]
                ),
            ),
        );
    }
}
