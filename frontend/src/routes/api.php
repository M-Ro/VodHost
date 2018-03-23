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

$app->post('/api/broadcast/editdetails', function (Request $request, Response $response, array $args) {
    $loggedIn = \App\Frontend\UserSessionHandler::isLoggedIn($request);
    $username = \App\Frontend\UserSessionHandler::getUsername($request);
    if (!$loggedIn) {
        return $response->withStatus(403);
    }

    /* Validate post data */
    $data = $request->getParsedBody();
    $broadcast_id = filter_var($data['videoid'], FILTER_SANITIZE_STRING);
    $broadcast_title = filter_var($data['title'], FILTER_SANITIZE_STRING);
    $broadcast_description = filter_var($data['description'], FILTER_SANITIZE_STRING);
    $broadcast_visibility = filter_var($data['visibility'], FILTER_SANITIZE_STRING);

    /* Fetch the user */
    $umapper = new \App\Frontend\UserMapper($this->em);
    $user = $umapper->getUserByUsername($username);
    if (!$user) {
        $this->logger->error("User " . $username . " not found in database" . PHP_EOL);
        return $response->withStatus(500);
    }
    $uid = $user->getId();

    /* Fetch the broadcast */
    $bmapper = new \App\Frontend\BroadcastMapper($this->em);
    $broadcast = $bmapper->getBroadcastById($broadcast_id);
    if (!$broadcast) {
        $this->logger->warning("Could not find broadcast for id: " . $broadcast_id . PHP_EOL);
        return $response->withStatus(500);
    }

    /* Make sure the user is the owner of this video */
    if ($broadcast->getUserId() != $user->getId()) {
        $this->logger->warning("User is not owner for broadcast edit: " . $username . " " . $broadcast_id . PHP_EOL);
        return $response->withStatus(500);
    }

    /* Update the broadcast, translating the Public/Private visibility
     * to true/false bool for the database field */
    $broadcast->setTitle($broadcast_title);
    $broadcast->setDescription($broadcast_description);

    // Translate 'Visibility' from 'Public / Private' to True / False
    $vis = false;
    if ($broadcast_visibility == 'Public') {
        $vis = true;
    }

    $broadcast->setVisibility($vis);

    /* Finalize and return success */
    $bmapper->update($broadcast);

    $this->logger->debug("Edited details for broadcast $broadcast_id (Title: $broadcast_title)
        (Description: $broadcast_description) (Vis: $broadcast_visibility)" . PHP_EOL);
    
    return $response->withStatus(200);
});
