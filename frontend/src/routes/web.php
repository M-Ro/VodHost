<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

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
    $loggedIn = \App\Backend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Backend\UserSessionHandler::getUsername($request);

    $response_vars = [
        'loggedIn' => $loggedIn,
        'username' => $username
    ];

    $bmapper = new \App\Backend\BroadcastMapper($this->db);
    $bentity = $bmapper->getBroadcastById($id);
    if(!$bentity)
    {
        $this->logger->addInfo("/view/ invalid broadcast id: " . $id . PHP_EOL);
    }
    else
    {
        // fixme set uploaddir in config
        $response_vars['media_path'] = '/uploads' . DIRECTORY_SEPARATOR . $bentity->getFilename();
        $response_vars['media_title'] = $bentity->getTitle();
    }

    $response = $this->view->render($response, 'view.phtml', $response_vars);

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


?>