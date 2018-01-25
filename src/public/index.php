<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'src/public/settings.php';

$app = new \Slim\App(['settings' => $config]);;
$container = $app->getContainer();

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('Log');
    $file_handler = new \Monolog\Handler\StreamHandler('logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$app->get('/', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Default Index Page");
    return $response;
});

$app->get('/login', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Login Page");
    return $response;
});

$app->get('/register', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Register Page");
    return $response;
});

$app->get('/view/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Hello, $id");
    return $response;
});
$app->run();
