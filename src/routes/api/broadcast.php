<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use VodHost\Entity;
use VodHost\EntityMapper;
use VodHost\Authentication;
use VodHost\Task;
use VodHost\Middleware\Authentication\UserAuthentication as UserAuthentication;

$app->post('/api/broadcast/upload', function (Request $request, Response $response, array $args) {
    $uploadHandler = new \VodHost\UploadHandler(
        $this->get('upload_directory'),
        $this->get('temp_directory'),
        $this->logger,
        $this->em,
        $this->mq
    );

    return $uploadHandler->handleChunk($request, $response);
})->add(new UserAuthentication(UserAuthentication::Forbidden));

/**
 *  Return a json response containing all recent broadcasts to the client.
 */
$app->get('/api/broadcast/fetchrecent', function (Request $request, Response $response, array $args) {
    $bmapper = new EntityMapper\BroadcastMapper($this->em);
    $umapper = new EntityMapper\UserMapper($this->em);

    $broadcasts = $bmapper->getRecentBroadcasts();
    $broadcasts_arr = array();

    foreach ($broadcasts as $b) {
        $item = (array)$b->jsonSerialize();

        $u = $umapper->getUserById($b->getUserId());
        if ($u) {
            $item['uploader'] = $u->getUsername();
        } else {
            $item['uploader'] = '[Deleted]';
        }

        $broadcasts_arr[] = $item;
    }

    $message = json_encode($broadcasts_arr);

    return $response->withJson($message, 200);
});

$app->post('/api/broadcast/editdetails', function (Request $request, Response $response, array $args) {
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    $username = Authentication\UserSessionHandler::getUsername($request);

    /* Validate post data */
    $data = $request->getParsedBody();
    $broadcast_id = filter_var($data['videoid'], FILTER_SANITIZE_STRING);
    $broadcast_title = filter_var($data['title'], FILTER_SANITIZE_STRING);
    $broadcast_description = filter_var($data['description'], FILTER_SANITIZE_STRING);
    $broadcast_visibility = filter_var($data['visibility'], FILTER_SANITIZE_STRING);

    if (!isset($broadcast_id) || !isset($broadcast_title) ||
        !isset($broadcast_description) || !isset($broadcast_visibility)) {
        $this->logger->warning("/api/broadcast/editdetails invalid postdata provided" . PHP_EOL);
        return $response->withStatus(400);
    }

    /* Fetch the user */
    $umapper = new EntityMapper\UserMapper($this->em);
    $user = $umapper->getUserByUsername($username);
    if (!$user) {
        $this->logger->error("User " . $username . " not found in database" . PHP_EOL);
        return $response->withStatus(500);
    }
    $uid = $user->getId();

    /* Fetch the broadcast */
    $bmapper = new EntityMapper\BroadcastMapper($this->em);
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
})->add(new UserAuthentication(UserAuthentication::Forbidden));

$app->post('/api/broadcast/remove', function (Request $request, Response $response, array $args) {
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    $username = Authentication\UserSessionHandler::getUsername($request);
    if (!$loggedIn) {
        return $response->withStatus(403);
    }

    /* Validate post data */
    $data = $request->getParsedBody();
    $broadcast_id = filter_var($data['videoid'], FILTER_SANITIZE_STRING);

    if (!isset($broadcast_id)) {
        $this->logger->warning("/api/broadcast/remove invalid postdata provided" . PHP_EOL);
        return $response->withStatus(400);
    }

    /* Fetch the user */
    $umapper = new EntityMapper\UserMapper($this->em);
    $user = $umapper->getUserByUsername($username);
    if (!$user) {
        $this->logger->error("User " . $username . " not found in database" . PHP_EOL);
        return $response->withStatus(500);
    }
    $uid = $user->getId();

    /* Fetch the broadcast */
    $bmapper = new EntityMapper\BroadcastMapper($this->em);
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

    /* Create a job to purge assets for this video */
    $task = new Task\PurgeBroadcastTask($this->mq, $broadcast_id);
    $task->publish();

    // Delete the broadcast
    $bmapper->delete($broadcast);

    // Finalize and return
    $this->logger->debug("Removed broadcast $broadcast_id" . PHP_EOL);

    return $response->withStatus(200);
})->add(new UserAuthentication(UserAuthentication::Forbidden));
