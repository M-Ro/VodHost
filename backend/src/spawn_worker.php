<?php

require 'vendor/autoload.php';
require '../frontend/src/settings.php';

if($argc != 2)
{
	echo "Usage: $argv[0] <worker_class>" . PHP_EOL;
	echo "Example: $argv[0] VProcessorWorker" . PHP_EOL;
	return 1;
}

$classname = '\\VodHost\\Backend\\' . $argv[1];

$worker = new $classname($config);
$worker->run();