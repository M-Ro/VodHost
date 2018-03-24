<?php

namespace VodHost\TaskHandler;

require 'vendor/autoload.php';
require '../frontend/src/settings.php';

class TaskHandlerFactory
{
	public function __construct($taskhandler)
	{
		global $config;

		$classname = '\\VodHost\\TaskHandler\\' . $taskhandler;

		$worker = new $classname($config);
		$worker->run();
	}
}