<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post('/api/upload', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Frontend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Frontend\UserSessionHandler::getUsername($request);
    if (!$loggedIn) {
        return $response->withStatus(403);
    }

    $uploadHandler = new \App\Frontend\UploadHandler(
        $this->get('upload_directory'),
        $this->get('temp_directory'),
        $this->logger,
        $this->em,
        $this->mq
    );

    return $uploadHandler->handleChunk($request, $response);
});

/** Return a json response containing all recent broadcasts to the client.
 *
 */
// FIXME: This needs to return recentvideos, currently returns all videos
// FIXME just convert this all to string array instead of modifying the entity object
$app->get('/api/fetch/recentvideos', function (Request $request, Response $response, array $args) {
    $bmapper = new \App\Frontend\BroadcastMapper($this->em);
    $umapper = new \App\Frontend\UserMapper($this->em);

    $broadcasts = $bmapper->getBroadcasts();
    foreach ($broadcasts as $b) {
        $u = $umapper->getUserById($b->getUserId());
        if ($u) {
            $b->uploader = $u->getUsername();
        } else {
            $b->uploader = '[Deleted]';
        }
    }

    $message = json_encode($broadcasts);

    return $response->withJson($message, 200);
});

$app->get('/api/account/getinfo', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Frontend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Frontend\UserSessionHandler::getUsername($request);
    if (!$loggedIn) {
        return $response->withStatus(403);
    }

    $umapper = new \App\Frontend\UserMapper($this->em);
    $user = $umapper->getUserByUsername($username);

    if (!$user) {
        return $response->withStatus(403);
    }

    // User account information
    $user_data = [
        'username' => $user->getUsername(),
        'email' => $user->getEmail(),
        'activated' => $user->getActivated()
    ];

    // User uploaded video information
    $bmapper = new \App\Frontend\BroadcastMapper($this->em);
    $broadcasts = $bmapper->getBroadcastsByUserId($user->getId());

    $arr = [
        'user' => $user_data,
        'broadcasts' => $broadcasts
    ];

    $message = json_encode($arr);

    return $response->withJson($message, 200);
});
