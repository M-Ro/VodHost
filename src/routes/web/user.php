<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use VodHost\Authentication;

$app->get('/user/login', function (Request $request, Response $response, array $args) {
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    if ($loggedIn == true) {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'login.phtml', ['loggedIn' => $loggedIn]);
    return $response;
});

$app->get('/user/register', function (Request $request, Response $response, array $args) {
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    if ($loggedIn == true) {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'register.phtml', ['loggedIn' => $loggedIn]);
    return $response;
});

$app->get('/logout', function (Request $request, Response $response, array $args) {

        $response = $response->withRedirect("/");
        $response = Authentication\UserSessionHandler::purge($response);

        return $response;
});

$app->get('/user/account', function (Request $request, Response $response, array $args) {
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    $username = Authentication\UserSessionHandler::getUsername($request);

    if ($loggedIn == false) {
        //return $response->withRedirect("/");
    }

    $response = $this->view->render(
        $response,
        'account.phtml',
        ['loggedIn' => $loggedIn, 'username' => $username, 'content_url' => $this->get('content_url_root')]
    );

    return $response;
});

$app->get('/user/activate/{hash}', function (Request $request, Response $response, array $args) {
    $hash = $args['hash'];
    if(!$hash) {
        $response->withStatus(400);
    }

    $umapper = new EntityMapper\UserMapper($this->em);
    $user = $umapper->findUserByActivationHash($hash);

    $validCode = false;

    if ($user) {
        $validCode = true;

        $user->setActivated(true);
        $umapper->update($user);
    }

    return $this->view->render(
        $response,
        'account_validated.phtml',
        ['validCode' => $validCode, 'loggedIn' => false, 'content_url' => $this->get('content_url_root')]
    );
});
