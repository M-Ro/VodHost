<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \App\Frontend\BackendAuthentication as BackendAuthentication;

/**
 * Returns a json response containing the md5sum of the unprocessed video
 *
 * @param {id} id of the broadcast
 * @return json array containing broadcast id, md5sum, unprocessed file name for fetching
 *
 */
$app->get('/api/backend/retrieve/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    if(!BackendAuthentication::authenticateAPIKey($request, $this->get('api_key'))) {
        $this->logger->warning('backend/retrieve/ accessed with invalid api key' . PHP_EOL);
        return $response->withStatus(403);
    }
    
    if(!$id) {
        $this->logger->warning('backend/retrieve/ called without valid id' . PHP_EOL);
        return $response->withStatus(400);
    }

    $bmapper = new \App\Frontend\BroadcastMapper($this->em);
    $broadcast = $bmapper->getBroadcastById($id);

    if(!$broadcast) {
        $this->logger->warning('backend/retrieve/ could not fetch broadcast at id ' . $id . PHP_EOL);
        return $response->withStatus(400);
    }

    /* Get the md5sum for this unprocessed broadcast */
    $path = $this->get('upload_directory') . DIRECTORY_SEPARATOR . $broadcast->getFilename();

    if(file_exists($path)) {
        $md5sum = md5_file($path);
    }

    if(!$md5sum) {
        $this->logger->warning('backend/retrieve/ Could not get md5sum of requested file ' . $path . PHP_EOL);
        return $response->withStatus(500);
    }

    $response_data = [
        'id' => $broadcast->getId(),
        'filename' => $broadcast->getFilename(),
        'md5sum' => $md5sum
    ];

    return $response->withJson($response_data, 200);
});