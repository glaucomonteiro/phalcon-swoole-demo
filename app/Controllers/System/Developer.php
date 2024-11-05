<?php

namespace Controllers\System;

use Lib\Base\Controller;
use Lib\Base\Logger;
use Lib\Response\ApiMessage;
use Lib\Helpers\HttpStatus;
use Lib\Response\ApiProblem;

class Developer extends Controller
{

	public function restart()
	{
		$logger = Logger::get_instance();
		$logger->warning('Request received to restart the server');
		$server = $this->context('server');
		go(function () use ($server) {
			sleep(1);
			$server->shutdown();
		});
		return new ApiMessage('OK');
	}

	public function reload()
	{
		$logger = Logger::get_instance();
		$logger->warning('Request received to reload the workers');
		$this->context('server')->reload();
		return new ApiMessage('OK');
	}

	public function main()
	{
		return new ApiMessage();
	}

	public function not_found()
	{
		$request = $this->context('request');
		/** @var \Lib\Swoole\Bridge\Request $request */
		$exception = new ApiProblem(
			HttpStatus::NOT_FOUND,
			'URL not found'
		);
		$exception->add_debug(['route' => $request->getURI(), 'method' => $request->getMethod()]);
		throw $exception;
	}
}
