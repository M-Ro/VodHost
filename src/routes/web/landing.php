<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use VodHost\Authentication;

$app->get('/', function (Request $request, Response $response, array $args) {
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    $username = Authentication\UserSessionHandler::getUsername($request);

    $response = $this->view->render(
        $response,
        'index.phtml',
        ['loggedIn' => $loggedIn, 'username' => $username, 'content_url' => $this->get('content_url_root')]
    );

    return $response;
});
