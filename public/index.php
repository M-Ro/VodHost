<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }

    error_reporting(E_ALL); // debug
}

use PhpAmqpLib\Connection\AMQPStreamConnection;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../settings.php';

$app = new \Slim\App(['settings' => $config]);

$container = $app->getContainer();

$container['upload_directory'] = $config['upload_directory'];
$container['temp_directory'] = $config['temp_directory'];
$container['api_key'] = $config['api_key'];
$container['content_url_root'] = $config['content_url_root'];

$container['view'] = new \Slim\Views\PhpRenderer(__DIR__ . '/../assets/templates/');

$container['logger'] = function ($c) {
    global $config;

    $logger = new \Monolog\Logger('Log');

    $client = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => $config['log_host'],
        'port'   => $config['log_port'],
        'password' => $config['log_pass']
    ]);
    $redis_handler = new \Monolog\Handler\RedisHandler($client, 'frontend_logs');

    $logger->pushHandler($redis_handler);
    return $logger;
};

/* Doctrine */
$container['em'] = function ($c) {
    global $config;

    $doctrine_conf = \Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
        ['../src/classes/Entity'],
        true,
        __DIR__ . '/cache/proxies',
        null,
        false
    );
    
    return \Doctrine\ORM\EntityManager::create($config['db_connection'], $doctrine_conf);
};

/* AMQP */
$container['mq'] = function ($c) {
    global $config;

    $c = new AMQPStreamConnection(
        $config['jobq_host'],
        $config['jobq_port'],
        $config['jobq_user'],
        $config['jobq_pass']
    );

    $channel = $c->channel();
    $channel->queue_declare('vprocessing', false, true, false, false);
    
    return $channel;
};

require __DIR__ . '/../src/routes/web/broadcast.php';
require __DIR__ . '/../src/routes/web/landing.php';
require __DIR__ . '/../src/routes/web/user.php';

require __DIR__ . '/../src/routes/api/backend.php';
require __DIR__ . '/../src/routes/api/broadcast.php';
require __DIR__ . '/../src/routes/api/user.php';

$app->run();
