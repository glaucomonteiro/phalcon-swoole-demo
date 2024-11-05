<?php

/**
 * Registering an autoloader
 */
$loader = new \Phalcon\Autoload\Loader();
$loader->addNamespace('Config', APP_PATH . '/Config/')
	->addNamespace('Controllers', APP_PATH . '/Controllers/')
	->addNamespace('Models', APP_PATH . '/Models/')
	->addNamespace('Lib', APP_PATH . '/Lib/')
	->addNamespace('Services', APP_PATH . '/Services/')
	->addDirectory(APP_PATH . '/Tasks')
	->register();
