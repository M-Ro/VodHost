<?php
if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Dflydev\FigCookies\Cookie;

require 'vendor/autoload.php';
require 'src/public/settings.php';

$app = new \Slim\App(['settings' => $config]);;
$container = $app->getContainer();

$container['view'] = new \Slim\Views\PhpRenderer('src/templates/');

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('Log');
    $file_handler = new \Monolog\Handler\StreamHandler('logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    $pdo = new PDO("sqlite:" . $config['SQLiteFilePath']);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
};

$app->get('/', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Backend\UserSessionHandler::isLoggedIn($request);

    $response = $this->view->render($response, 'index.phtml', ['loggedIn' => $loggedIn]);
    return $response;
});

$app->get('/login', function (Request $request, Response $response, array $args) {
    $response = $this->view->render($response, 'login.phtml');
    return $response;
});

$app->get('/register', function (Request $request, Response $response, array $args) {
    $response = $this->view->render($response, 'register.phtml');
    return $response;
});

$app->get('/view/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Hello, $id");
    return $response;
});

$app->post('/attempt_signup', function (Request $request, Response $response, array $args) {

    $data = $request->getParsedBody();

    $user_data = [];
    $user_data['username'] = filter_var($data['username'], FILTER_SANITIZE_STRING);
    $user_data['email'] = filter_var($data['email'], FILTER_SANITIZE_STRING);
    $user_data['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING);

    $user = new \App\Backend\UserEntity($user_data);
    $user_mapper = new \App\Backend\UserMapper($this->db);
    $user_mapper->save($ticket);

    $response->getBody()->write("Hello, " . $user_data['username']);
    return $response;
});
$app->run();
