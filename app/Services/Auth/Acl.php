<?php

namespace Services\Auth;

use Controllers\System\Developer;
use Lib\Routes\Base;
use Models\System\Role;
use Lib\Helpers\HttpStatus;
use Lib\Logger\Logger;
use Lib\Response\ApiProblem;
use Lib\Swoole\Bridge\Request;
use OpenSwoole\Coroutine;
use OpenSwoole\Coroutine\Context;
use Phalcon\Acl\Adapter\Memory;
use Phalcon\Acl\Enum;
use Phalcon\Mvc\Router;

class Acl
{
    private Memory $acl;
    private Context $context;
    private Logger $logger;

    public function __construct(private Request $request, private Router $router)
    {
        $this->acl = new Memory;
        $this->context = Coroutine::getContext();
        $this->logger = Logger::get_instance();
        $this->build();
    }

    public function call()
    {
        $headers = $this->request->getHeaders();
        $controller = $this->router->getControllerName();
        $method = $this->router->getActionName();
        $auth = new User();
        $user = $auth->get_user($headers);
        $this->context['auth'] = $user;
        $this->logger->debug('Request received by the acl:', [
            'role_id' => $user->role_id,
            'controller' => $controller,
            'method' => $method,
            'coroutine' => Coroutine::getCid()
        ]);
        $this->is_allowed($user->role_id, $controller, $method);
        return true;
    }

    public function is_allowed($role, $controller, $action)
    {
        if (!$this->acl->isAllowed($role . '', $controller, $action)) {
            $exception = new ApiProblem(
                HttpStatus::FORBIDDEN,
                'You do not have the required privileges to do this.',
            );
            $exception->add_debug('Unauthorized access to ' . $controller . '->' . $action . ' by role ' . $role);
            throw $exception;
        }
        return true;
    }

    /**
     * Build the list from routes list
     */
    public function build()
    {
        $this->acl->setDefaultAction(Enum::DENY);
        $this->acl->addRole(Role::GUEST);
        $this->acl->addRole(Role::ADMIN);
        $this->acl->addRole(Role::FREE_USER, Role::GUEST);
        $this->acl->addRole(Role::PAID_USER, Role::GUEST);
        $base = new Base();
        $controllers = $base->list_routes();
        foreach ($controllers as $controller => $info_array) {
            $access_list = [];
            $roles = [];
            foreach ($info_array as $info) {
                /** @var \Lib\Routes\Info $info */
                $access_list[] = $info->call;
                foreach ($info->allowed as $role) {
                    $roles[$role][] = $info->call;
                }
            }
            $this->acl->addComponent($controller, $access_list);
            foreach ($roles as $role => $allowed) {
                $this->acl->allow($role, $controller, $allowed);
            }
        }
        $this->acl->addComponent(Developer::class, 'not_found');
        $this->acl->allow(Role::ALL, Developer::class, '*');
        $this->acl->allow(Role::ADMIN, '*', '*');
    }
}
