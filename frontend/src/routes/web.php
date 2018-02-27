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
