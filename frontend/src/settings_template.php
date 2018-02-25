<?php

$config = [
	// PHP Settings
	'displayErrorDetails' => true,
	'addContentLengthHeader' => false,

	// Path to the SQLite database file
	'SQLiteFilePath' => 'db/sqlite.db',

	// Directory where chunked uploads will be held
	'temp_directory' => __DIR__ . '/temp',

	// Directory where videos will be delivered from
	'upload_directory' => __DIR__ . '/processing',

	// Job Queue -- RabbitMQ Configuration
	'jobq_server' => 'localhost',
	'jobq_port' => 5672,
	'jobq_user' => 'guest',
	'jobq_pass' => 'guest',

	// Log Server -- Redis Configuration
	'log_server' => 'localhost',
	'log_port' => 6379,
	'log_pass' => 'password'
];
