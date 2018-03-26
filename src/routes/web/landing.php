<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use VodHost\Middleware\Authentication\UserAuthentication as UserAuthentication;

$app->get('/', function (Request $request, Response $response, array $args) {
	$user = $request->getAttribute('user');

    $response = $this->view->render(
        $response,
        'index.phtml',
        ['loggedIn' => $user['logged_in'], 'username' => $user['username'], 'content_url' => $this->get('content_url_root')]
    );

    return $response;
})->add(new UserAuthentication(UserAuthentication::Passive));
