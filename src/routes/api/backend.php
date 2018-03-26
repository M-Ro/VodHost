<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


use \VodHost\Middleware\Authentication;

use \VodHost\EntityMapper;
use \VodHost\Entity;

/**
 * Returns a json response containing the md5sum of the unprocessed video
 *
 * @param {id} id of the broadcast
 * @return json array containing broadcast id, md5sum, unprocessed file name for fetching
 *
 */
$app->get('/api/backend/broadcast/retrieve/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    if (!$id) {
        $this->logger->warning('backend/retrieve/ called without valid id' . PHP_EOL);
        return $response->withStatus(400);
    }

    $bmapper = new EntityMapper\BroadcastMapper($this->em);
    $broadcast = $bmapper->getBroadcastById($id);

    if (!$broadcast) {
        $this->logger->warning('backend/retrieve/ could not fetch broadcast at id ' . $id . PHP_EOL);
        return $response->withStatus(400);
    }

    /* Get the md5sum for this unprocessed broadcast */
    $path = $this->get('upload_directory') . DIRECTORY_SEPARATOR . $broadcast->getFilename();

    if (file_exists($path)) {
        $md5sum = md5_file($path);
    }

    if (!$md5sum) {
        $this->logger->warning('backend/retrieve/ Could not get md5sum of requested file ' . $path . PHP_EOL);
        return $response->withStatus(500);
    }

    $response_data = [
        'id' => $broadcast->getId(),
        'filename' => $broadcast->getFilename(),
        'md5sum' => $md5sum
    ];

    return $response->withJson($response_data, 200);
})->add(new Authentication\BackendAuthentication($app->getContainer()));


/**
 * Deletes the unprocessed source file uploaded by the user. This function
 * is called by a backend worker after processing of uploaded media is complete.
 *
 * @param {id} id of the broadcast
 * @return HTTP 200 on success, HTTP 4xx on error.
 *
 */
$app->get('/api/backend/broadcast/tagprocessed/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    if (!$id) {
        $this->logger->warning('backend/tagprocessed/ called without valid id' . PHP_EOL);
        return $response->withStatus(400);
    }

    /* Delete unprocessed uploaded file */
    $bmapper = new EntityMapper\BroadcastMapper($this->em);
    $broadcast = $bmapper->getBroadcastById($id);

    if (!$broadcast) {
        $this->logger->warning('backend/tagprocessed/ could not fetch broadcast at id ' . $id . PHP_EOL);
        return $response->withStatus(400);
    }

    /* Get the filepath and delete unprocessed media */
    $path = $this->get('upload_directory') . DIRECTORY_SEPARATOR . $broadcast->getFilename();

    if (file_exists($path)) {
        unlink($path);
    }

    /* Set the broadcast state to processed */
    $broadcast->setState('processed');
    $bmapper->update($broadcast);

    return $response->withStatus(200);
})->add(new Authentication\BackendAuthentication($app->getContainer()));
