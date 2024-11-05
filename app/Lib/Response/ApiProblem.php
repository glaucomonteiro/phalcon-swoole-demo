<?php

namespace Lib\Response;

use Lib\Helpers\HttpStatus;
use Phalcon\Mvc\Model;
use Throwable;

class ApiProblem extends \Exception
{

    protected $message_array;
    protected $model_data;
    public $debug_value;
    public function __construct($code, $message = '', ?Throwable $previous = null)
    {
        $this->message_array = array();
        $this->model_data = [];
        if (strlen($message) == 0)
            switch ($code) {
                case HttpStatus::BAD_REQUEST:
                    $message = 'Bad Request';
                    break;
                case HttpStatus::FORBIDDEN:
                case HttpStatus::UNAUTHENTICATED:
                    $message = 'Unauthorized';
                case HttpStatus::NOT_FOUND:
                    $message = 'Not found';
                    break;
                case HttpStatus::NOT_IMPLEMENTED:
                    $message = 'Not implemented';
                    break;
                case HttpStatus::CONFLICT:
                    $message = 'Conflict';
                    break;
                default:
                    $message = 'Internal server error';
                    $message .= '\n\n\n' . json_encode($this->getTrace());
                    break;
            }
        parent::__construct($message, $code, $previous);
    }

    public function add_debug($info)
    {
        $this->debug_value = $info;
    }

    public function set_model(Model $model)
    {
        $messages = $model->getMessages();
        foreach ($messages as $msg) {
            $this->message_array[] = (string) $msg;
        }
        $this->model_data = $model->toArray();
    }

    public function get_message_array()
    {
        return $this->message_array;
    }

    public function get_model_data()
    {
        return $this->model_data;
    }
}
