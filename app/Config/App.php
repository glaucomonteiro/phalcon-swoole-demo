<?php

namespace Config;

use Controllers\System\Developer;
use Exception;
use Lib\Routes\Base;
use Lib\Traits\Json;
use Models\System\Logs;
use Models\System\LogType;
use Lib\Helpers\HttpStatus;
use Lib\Response\ApiProblem;
use Services\Auth\Acl;
use Lib\Swoole\Bridge\Request as BridgeRequest;
use Lib\Swoole\Bridge\Response as BridgeResponse;
use Lib\Logger\Logger;
use OpenSwoole\Coroutine;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use PDOException;
use Phalcon\Di\Di;
use Phalcon\Mvc\Router;

class App
{

    use Json;

    private Logger $logger;

    private BridgeRequest $bridge_request;

    private BridgeResponse $bridge_response;

    private Response $swoole_response;

    private Router $router;

    public function __construct()
    {
        $this->logger = Logger::get_instance();
    }

    protected function add_routes()
    {

        $context = Coroutine::getContext();
        $router = new Router(false);
        $di = Di::getDefault();
        $router->removeExtraSlashes(true);
        $router->setDI($di);
        $context['router'] = $router;
        $routes = new Base();
        // $micro->notFound() causes a seg fault, so there is a catch all redirecting to a not_found handler on Developer controller
        $router->add('/:params', ['controller' => Developer::class, 'action' => 'not_found'], ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
        $router = $routes->add_routes($router);
        $this->router = $router;
    }

    protected function set_parameters()
    {
        $params = $this->router->getParams();
        $context = Coroutine::getContext();
        foreach ($params as $key => $param) {
            $this->bridge_request->set($key, $param);
        }

        /**
         * Set the params for the request based on json
         */
        try {
            if ($this->bridge_request->getHeader('Content-Type') === 'application/json') {
                $this->bridge_request->set_params($this->decode($this->bridge_request->getJsonRawBody()));
            } else if ($this->bridge_request->getHeader('Content-Type') === 'multipart/form-data') {
                $this->bridge_request->set_params($this->bridge_request->getPost());
            } else {
                $this->bridge_request->set_params($this->bridge_request->get());
            }
        } catch (Exception $e) {
            throw new ApiProblem(json_last_error(), 'Json error: ' . json_last_error_msg());
        }
        $context['request'] = $this->bridge_request;
        return true;
    }

    protected function middlewares()
    {
        $this->maintenance_check();
        $this->acl();
    }

    protected function maintenance_check()
    {
        if (Environment::load_env(Environment::MAINTENANCE))
            throw new ApiProblem(
                HttpStatus::MAINTENANCE,
                Environment::load_env(Environment::MAINTENANCE_MESSAGE)
            );
    }

    protected function acl()
    {
        $acl = new Acl($this->bridge_request, $this->router);
        return $acl->call();
    }

    protected function handle_response()
    {
        $controller = $this->router->getControllerName();
        $content = call_user_func_array([new $controller, $this->router->getActionName()], $this->router->getParams());
        $context = Coroutine::getContext();
        $this->logger->debug('response', ['content' => $content, 'context' => $context]);
        $this->bridge_response
            ->setJsonContent($content)
            ->send();
        $this->swoole_response->end(json_encode($content));
    }

    /**
     * Error handler
     */
    protected function errors($exception, $handler)
    {
        $model = new Logs();
        $serverErrorCodes = array(
            HttpStatus::BAD_REQUEST,
            HttpStatus::UNAUTHENTICATED,
            HttpStatus::PAYMENT_REQUIRED,
            HttpStatus::FORBIDDEN,
            HttpStatus::NOT_FOUND,
            HttpStatus::NOT_IMPLEMENTED,
            HttpStatus::CONFLICT,
            HttpStatus::MAINTENANCE
        );
        $log_codes = Environment::load_env(Environment::LOG_CODES);
        $code = in_array($exception->getCode(), $serverErrorCodes) ? $exception->getCode() : HttpStatus::SERVER_ERROR;
        $logerror = in_array($code, $log_codes);
        $production = Environment::load_env(Environment::PRODUCTION);
        $context = Coroutine::getContext();
        /** @var \Lib\Swoole\Bridge\Request $request */
        $error = array(
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTrace(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'handler' => $handler,
            'context' => $context,
        );
        $model->code = $exception->getCode();
        $model->trace = $exception->getTrace();
        $model->headers = $this->bridge_request->getHeaders();
        $model->full_request = $this->bridge_request->get();
        $model->route = $this->bridge_request->getMethod() . ' - ' . $this->bridge_request->getURI();
        $model->debug = [];
        if (isset($context['auth']))
            $model->user_id = $context['auth']->id;
        if ($exception instanceof ApiProblem) {
            if ($exception->getCode())
                $error['validations'] = $exception->get_message_array();
            $error['debug'] = $exception->debug_value;
            $model->debug['debug'] = $exception->debug_value;
        }
        if ($exception instanceof PDOException) {
            $error['message'] = 'Erro interno no servidor!';
            $model->code = 500;
            $error['debug'] = $exception->getMessage();
            $model->debug['PDO'] = $exception->getMessage();
        }
        $transaction = $model->getWriteConnection();
        if ($transaction->isUnderTransaction()) {
            $transaction->rollback();
            $previous = $exception->getPrevious();
            $error['transaction'] = true;
            if ($previous) {
                $logerror = true;
                $error['previous-error'] = [
                    'message' => $previous->getMessage(),
                    'trace' => $previous->getTrace()
                ];
            }
        }
        $model->details = $error;
        $model->type_id = LogType::ERROR;
        if ($logerror)
            $this->logger->error($error['message'], $error);
        $model->create();
        if ($production) {
            unset($error['trace']);
            unset($error['debug']);
            unset($error['file']);
            unset($error['line']);
            unset($error['handler']);
            unset($error['context']);
        }
        $this->bridge_response->setStatusCode($code, 'Error')
            ->setJsonContent($error)
            ->send();
        $this->swoole_response->end(json_encode($error));
    }

    public function setup(Request $request, Response $response)
    {
        $context = Coroutine::getContext();
        $this->bridge_request = new BridgeRequest($request);
        $this->bridge_response = new BridgeResponse($response);
        $context['request'] = $this->bridge_request;
        $context['response'] = $this->bridge_response;
        $this->swoole_response = $response;
        $this->add_routes();
        $this->set_parameters();
    }

    protected function cleanup()
    {
        $context = Coroutine::getContext();
        if (isset($context['db'])) {
            $this->logger->debug('Request complete, returning db connection to the pool');
            $connection = $context['db'];
            $database = new Database;
            $database->get_pool()->put($connection);
        }
    }

    public function handle()
    {
        try {
            $uri = str_replace('//', '/', $this->bridge_request->getURI());
            $this->router->handle($uri);
            $controller = $this->router->getControllerName();
            $action = $this->router->getActionName();
            $params = $this->router->getParams();
            $handler = [
                'controller' => $controller,
                'action' => $action,
                'params' => $params,
                'uri' => $uri,
                'coroutine' => Coroutine::getCid(),
                'request-headers' => $this->bridge_request->getHeaders(),
                'original-uri' => $this->bridge_request->getURI()
            ];
            $this->logger->debug('Request info: ', $handler);
            $this->middlewares();
            $this->handle_response();
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), [$e->getTrace(), $uri]);
            $this->errors($e, $handler);
        }
        $this->cleanup();
    }
}
