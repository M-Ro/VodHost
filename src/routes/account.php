<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \VodHost\Entity;
use \VodHost\EntityMapper;
use \VodHost\Task;
use \VodHost\Authentication;

$app->get('/logout', function (Request $request, Response $response, array $args) {

        $response = Authentication\UserSessionHandler::purge($response);

        $response = $response->withRedirect("/");
        return $response;
});

$app->post(
    '/api/signup',
    function (Request $request, Response $response, array $args) {

        $data = $request->getParsedBody();

        $user_data = [
            'username' => filter_var($data['username'], FILTER_SANITIZE_STRING),
            'email' => filter_var($data['email'], FILTER_SANITIZE_STRING),
            'password' => filter_var($data['password'], FILTER_SANITIZE_STRING)
        ];

        // Hash password
        $user_data['password'] = password_hash($user_data['password'], PASSWORD_DEFAULT);

        /* Check whether an account already exists with this email or username */
        $umapper = new EntityMapper\UserMapper($this->em);
        $email_exists = $umapper->getUserByEmail($user_data['email']);
        $username_exists = $umapper->getUserByUsername($user_data['username']);

        if ($email_exists || $username_exists) {
            $message = [
            'state' => 'error',
            'message' => 'An account already exists with this username or email.'
            ];

            return $response->withJson($message, 200);
        }

        /* Create the actual user */
        $user = new Entity\UserEntity($user_data);
        $umapper->save($user);

        $this->logger->info("Registered user: " . $user->getEmail() . PHP_EOL);

        /* Send verification email */
        $activation = new Task\ActivationEmail(
            $this->mq, $user->getEmail(), $user->getUsername(), $user->getHash());

        $activation->publish();

        /* Return success response to client */
        $message = [
            'state' => 'success',
            'message' => ''
        ];

        return $response->withJson($message, 200);
    }
);

$app->post('/api/signin', function (Request $request, Response $response, array $args) {

        $data = $request->getParsedBody();

        $user_data = [
            'email' => filter_var($data['email'], FILTER_SANITIZE_STRING),
            'password' => filter_var($data['password'], FILTER_SANITIZE_STRING)
        ];

        /* Attempt to find user by email address then verify password matches */
        $umapper = new EntityMapper\UserMapper($this->em);
        $user = $umapper->getUserByEmail($user_data['email']);

        if (!$user) {
            $message = [
                'state' => 'error',
                'message' => 'No account found with this Email address.'
            ];

            return $response->withJson($message, 200);
        }

        if (!password_verify($user_data['password'], $user->getPassword())) {
            $message = [
                'state' => 'error',
                'message' => 'Incorrect password'
            ];

            return $response->withJson($message, 200);
        }

        if (!$user->getActivated()) {
            $message = [
                'state' => 'error',
                'message' => 'Account has not yet been activated'
            ];

            return $response->withJson($message, 200);
        }

        $response = Authentication\UserSessionHandler::login($response, $user);

        $message = [
            'state' => 'success',
            'message' => ''
        ];

        return $response->withJson($message, 200);
});

$app->get('/account', function (Request $request, Response $response, array $args) {
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    $username = Authentication\UserSessionHandler::getUsername($request);

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

