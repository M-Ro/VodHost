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
use Slim\Http\UploadedFile;

require 'vendor/autoload.php';
require 'src/public/settings.php';

$app = new \Slim\App(['settings' => $config]);;
$container = $app->getContainer();

$container['upload_directory'] = __DIR__ . '/uploads';
$container['temp_directory'] = __DIR__ . '/temp';

$container['view'] = new \Slim\Views\PhpRenderer('src/templates/');

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('Log');
    $file_handler = new \Monolog\Handler\StreamHandler('logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    global $config;
    $pdo = new PDO("sqlite:" . $config['SQLiteFilePath']);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
};

$app->get('/', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Backend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Backend\UserSessionHandler::getUsername($request);

    $response = $this->view->render($response, 'index.phtml', ['loggedIn' => $loggedIn, 'username' => $username,]);
    return $response;
});

$app->get('/login', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Backend\UserSessionHandler::isLoggedIn($request);
    if($loggedIn == true)
    {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'login.phtml', ['loggedIn' => $loggedIn]);
    return $response;
});

$app->get('/register', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Backend\UserSessionHandler::isLoggedIn($request);
    if($loggedIn == true)
    {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'register.phtml', ['loggedIn' => $loggedIn]);
    return $response;
});

$app->get('/upload', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Backend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Backend\UserSessionHandler::getUsername($request);
    if(!$loggedIn)
    {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'upload.phtml', ['loggedIn' => $loggedIn, 'username' => $username]);
    return $response;
});

$app->get('/view/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Hello, $id");
    return $response;
});

// FIXME: Temporary hack to create db tables, this should obviouslyh not be accessible later.
$app->get('/admin_createdb', function (Request $request, Response $response, array $args) {
    
    $this->logger->addInfo("Creating Users table" . PHP_EOL);
    $user_mapper = new \App\Backend\UserMapper($this->db);
    $user_mapper->createUsersTable();

    $this->logger->addInfo("Creating broadcasts table" . PHP_EOL);
    $broadcast_mapper = new \App\Backend\BroadcastMapper($this->db);
    $broadcast_mapper->createBroadcastsTable();

    $response->getBody()->write("Done");
    return $response;
});

$app->get('/logout', function (Request $request, Response $response, array $args) {

    $response = \App\Backend\UserSessionHandler::purge($response);

    $response = $response->withRedirect("/");
    return $response;
});

$app->post('/attempt_signup', function (Request $request, Response $response, array $args) {

    $data = $request->getParsedBody();

    $user_data = [];
    $user_data['username'] = filter_var($data['username'], FILTER_SANITIZE_STRING);
    $user_data['email'] = filter_var($data['email'], FILTER_SANITIZE_STRING);
    $user_data['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING);

    // Hash password
    $user_data['password'] = password_hash($user_data['password'], PASSWORD_DEFAULT);

    $this->logger->addInfo("Attempting to create user " . $user_data['username'] . " - " . $user_data['email'] . PHP_EOL);

    $user = new \App\Backend\UserEntity($user_data);
    $user_mapper = new \App\Backend\UserMapper($this->db);
    $user_mapper->save($user);

    $response->getBody()->write("Hello, " . $user_data['username']);
    return $response;
});

$app->post('/attempt_login', function (Request $request, Response $response, array $args) {

    $data = $request->getParsedBody();

    $user_data = [];
    $user_data['email'] = filter_var($data['email'], FILTER_SANITIZE_STRING);
    $user_data['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING);

    $this->logger->addInfo("User attempting to login: " . $user_data['email'] . PHP_EOL);

    /* Attempt to find user by email address then verify password matches */
    $user_mapper = new \App\Backend\UserMapper($this->db);
    $user = $user_mapper->getUserByEmail($user_data['email']);

    if(!$user)
        $this->logger->addInfo("Could not find user matching email address: " . $user_data['email'] . PHP_EOL);

    if(password_verify($user_data['password'], $user->getPassword()))
    {
        $this->logger->addInfo("Password matched. " . PHP_EOL);
        $response = \App\Backend\UserSessionHandler::login($response, $user);
        $response = $response->withRedirect("/");
    }
    else
    {
        $response = $response->withRedirect("/login?login=failed");
        $this->logger->addInfo("Password did not match. " . PHP_EOL);
    }

    return $response;
});

$app->post('/api/upload', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Backend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Backend\UserSessionHandler::getUsername($request);
    if(!$loggedIn)
    {
        $this->logger->addInfo("/api/upload: " . "Invalid Session" . PHP_EOL);
        $response->write("Invalid Session");
        return $response;
    }

    $req = new \Flow\Request();

    $dest = $this->get('upload_directory');
    $chunks_temp_folder = $this->get('temp_directory');

    if (!is_dir($chunks_temp_folder) || !is_writable($chunks_temp_folder)) {
      $this->logger->addInfo("/api/upload: " . "Error: " . $chunks_temp_folder . "not writable" . PHP_EOL);

      return $response->withStatus(500); // we dun goofed
    }

    if (!is_dir($dest) || !is_writable($dest)) {
      $this->logger->addInfo("/api/upload: " . "Error: " . $dest . "not writable" . PHP_EOL);

      return $response->withStatus(500); // we dun goofed
    }

    $config = new \Flow\Config(['tempDir' => $chunks_temp_folder ]);

    $dest .= DIRECTORY_SEPARATOR . $req->getFileName();

    $message = [
      'type' => 'error',
      'message' =>'no data'
    ];

    if (\Flow\Basic::save($dest, $config, $req)) {

      $file = $req->getFile();

      $message = [
        "type" => "success",
        "name" => $file['name'],
        "filetype" => $file['type'],
        "error" => $file['error'],
        "size" => $file['size'],
        "fullpath" => $dest
      ];

      $this->logger->addInfo("/api/upload: " . "Saving file: " . $file['name'] . PHP_EOL);
    } else {
      //return $response->withStatus(400);
    }

    return $response->withJson($message, 200);
});


$app->run();
