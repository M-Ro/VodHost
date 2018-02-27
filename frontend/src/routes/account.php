<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/logout', function (Request $request, Response $response, array $args) {

        $response = \App\Frontend\UserSessionHandler::purge($response);

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
        $umapper = new \App\Frontend\UserMapper($this->em);
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
        $user = new \App\Frontend\Entity\UserEntity($user_data);
        $umapper->save($user);

        $this->logger->info("Registered user: " . $user->getEmail() . PHP_EOL);

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
        $umapper = new \App\Frontend\UserMapper($this->em);
        $user = $umapper->getUserByEmail($user_data['email']);

        if (!$user) {
            $message = [
                'state' => 'error',
                'message' => 'No account found with this Email address.'
            ];

            return $response->withJson($message, 200);
        }

        if (password_verify($user_data['password'], $user->getPassword())) {
            $response = \App\Frontend\UserSessionHandler::login($response, $user);

            $message = [
                'state' => 'success',
                'message' => ''
            ];

            $response = $response->withJson($message, 200);
        } else {
            $message = [
                'state' => 'error',
                'message' => 'Incorrect password'
            ];

            $response = $response->withJson($message, 200);
        }

        return $response;
});
