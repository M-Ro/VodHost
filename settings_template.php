<?php

$config = [
    // PHP Settings
    'displayErrorDetails' => true,
    'addContentLengthHeader' => false,

    // URL the website runs on
    'server_domain' => 'mywebsite.com',

    // Path to the SQLite database file
    'SQLiteFilePath' => 'db/sqlite.db',

    // Directory where chunked uploads will be held
    'temp_directory' => __DIR__ . '/temp',

    // Directory where videos will be delivered from
    'upload_directory' => __DIR__ . '/processing',

    // Directory to store videos after processing
    'processed_directory' => __DIR__ . '/final',

    // URL where the final processed content is located, this can be a simple
    // local path, web server, or cloud storage URL like cloudfront.
    'content_url_root' => __DIR__ . '/content',

    // Job Queue -- RabbitMQ Configuration
    'jobq_host' => 'localhost',
    'jobq_port' => 5672,
    'jobq_user' => 'guest',
    'jobq_pass' => 'guest',

    // Log Server -- Redis Configuration
    'log_host' => 'localhost',
    'log_port' => 6379,
    'log_pass' => 'password',

    // Database connection
    'db_connection' => [
        'driver'   => 'pdo_mysql',
        'host'     => 'localhost',
        'dbname'   => 'vodhost',
        'user'     => 'vodhost',
        'password' => 'your-password',
    ],

    // Internal API Key - MUST BE CHANGED
    'api_key' => 'key',

    'storage' => [
		'engine' => 's3',

		's3_bucket' => '',
		's3_region' => '',
		's3_key' => '',
		's3_secret' => ''
	],

    'mail' => [
        'host' => 'myhost',
        'port' => 587,
        'username' => 'username',
        'password' => 'password',
        'security' => 'tls'
    ]
];
