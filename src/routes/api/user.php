<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \VodHost\Entity;
use \VodHost\EntityMapper;
use \VodHost\Authentication;

use VodHost\Middleware\Authentication\UserAuthentication as UserAuthentication;

$app->post(
    '/api/user/signup',
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

$app->post('/api/user/signin', function (Request $request, Response $response, array $args) {

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

$app->get('/api/user/getinfo', function (Request $request, Response $response, array $args) {
    $user = $request->getAttribute('user');

    $umapper = new EntityMapper\UserMapper($this->em);
    $user = $umapper->getUserByUsername($user['username']);

    if (!$user) {
        return $response->withStatus(403);
    }

    // User account information
    $user_data = [
        'username' => $user->getUsername(),
        'email' => $user->getEmail(),
        'activated' => $user->getActivated(),
        'dateRegistered' => $user->getDateRegistered()->format('Y-m-d')
    ];

    // User uploaded video information
    $bmapper = new EntityMapper\BroadcastMapper($this->em);
    $broadcasts = $bmapper->getBroadcastsByUserId($user->getId());

    $arr = [
        'user' => $user_data,
        'broadcasts' => $broadcasts
    ];

    $message = json_encode($arr);

    return $response->withJson($message, 200);
})->add(new UserAuthentication(UserAuthentication::Forbidden));