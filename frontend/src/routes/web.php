<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Frontend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Frontend\UserSessionHandler::getUsername($request);

    $response = $this->view->render($response, 'index.phtml', ['loggedIn' => $loggedIn, 'username' => $username,]);
    return $response;
});

$app->get('/login', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Frontend\UserSessionHandler::isLoggedIn($request);
    if ($loggedIn == true) {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'login.phtml', ['loggedIn' => $loggedIn]);
    return $response;
});

$app->get('/register', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Frontend\UserSessionHandler::isLoggedIn($request);
    if ($loggedIn == true) {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'register.phtml', ['loggedIn' => $loggedIn]);
    return $response;
});

$app->get('/upload', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Frontend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Frontend\UserSessionHandler::getUsername($request);
    if (!$loggedIn) {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'upload.phtml', ['loggedIn' => $loggedIn, 'username' => $username]);
    return $response;
});

$app->get('/view/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $loggedIn = \App\Frontend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Frontend\UserSessionHandler::getUsername($request);

    $response_vars = [
        'loggedIn' => $loggedIn,
        'username' => $username
    ];

    $bmapper = new \App\Frontend\BroadcastMapper($this->em);
    $bentity = $bmapper->getBroadcastById($id);
    if (!$bentity) {
        $this->logger->addInfo("/view/ invalid broadcast id: " . $id . PHP_EOL);
    } else {
        // fixme set uploaddir in config
        $response_vars['media_path'] = '/uploads' . DIRECTORY_SEPARATOR . $bentity->getFilename();
        $response_vars['media_title'] = $bentity->getTitle();
    }

    $response = $this->view->render($response, 'view.phtml', $response_vars);

    return $response;
});

$app->get('/logout', function (Request $request, Response $response, array $args) {

    $response = \App\Frontend\UserSessionHandler::purge($response);

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

    $this->logger->addInfo("Creating user " . $user_data['username'] . " - " . $user_data['email'] . PHP_EOL);

    $user = new \App\Frontend\Entity\UserEntity($user_data);
    $user_mapper = new \App\Frontend\UserMapper($this->em);
    $user_mapper->save($user);

    $response->getBody()->write("Hello, " . $user_data['username']);
    return $response;
});

$app->post('/attempt_login', function (Request $request, Response $response, array $args) {

    $data = $request->getParsedBody();

    $user_data = [];
    $user_data['email'] = filter_var($data['email'], FILTER_SANITIZE_STRING);
    $user_data['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING);

    $this->logger->debug("User attempting to login: " . $user_data['email'] . PHP_EOL);

    /* Attempt to find user by email address then verify password matches */
    $user_mapper = new \App\Frontend\UserMapper($this->em);
    $user = $user_mapper->getUserByEmail($user_data['email']);

    if (!$user) {
        $this->logger->debug("Could not find user matching email address: " . $user_data['email'] . PHP_EOL);
    }

    if (password_verify($user_data['password'], $user->getPassword())) {
        $this->logger->debug("Password matched. " . PHP_EOL);
        $response = \App\Frontend\UserSessionHandler::login($response, $user);
        $response = $response->withRedirect("/");
    } else {
        $response = $response->withRedirect("/login?login=failed");
        $this->logger->debug("Password did not match. " . PHP_EOL);
    }

    return $response;
});
