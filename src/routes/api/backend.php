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
$app->get('/api/backend/broadcast/removesource/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    if (!$id) {
        $this->logger->warning('backend/removesource/ called without valid id' . PHP_EOL);
        return $response->withStatus(400);
    }

    /* Delete unprocessed uploaded file */
    $bmapper = new EntityMapper\BroadcastMapper($this->em);
    $broadcast = $bmapper->getBroadcastById($id);

    if (!$broadcast) {
        $this->logger->warning('backend/removesource/ could not fetch broadcast at id ' . $id . PHP_EOL);
        return $response->withStatus(400);
    }

    /* Get the filepath and delete unprocessed media */
    $path = $this->get('upload_directory') . DIRECTORY_SEPARATOR . $broadcast->getFilename();

    if (file_exists($path)) {
        unlink($path);
    }

    return $response->withStatus(200);
})->add(new Authentication\BackendAuthentication($app->getContainer()));

/**
 * Modifies entity fields based on information provided by the Backend
 * Useful for setting broadcast fields (video length, filesize) that are only
 * available after processing.
 *
 * @param {id} id of the broadcast
 * @return HTTP 200 on success, HTTP 4xx on error.
 *
 */
$app->post('/api/backend/broadcast/modify/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    if (!$id) {
        $this->logger->warning('backend/broadcast/modify/ called without valid id' . PHP_EOL);
        return $response->withStatus(400);
    }

    $bmapper = new EntityMapper\BroadcastMapper($this->em);
    $broadcast = $bmapper->getBroadcastById($id);

    $data = json_decode($request->getBody(), true);
    if ($data && $broadcast) {
        foreach ($data as $key => $val) {
            switch ($key) {
                case "state": $broadcast->setState($val); break;
                case "filesize": $broadcast->setFilesize($val); break;
                case "length": $broadcast->setLength($val); break;
            }
        }

        $bmapper->update($broadcast);
        $response = $response->withStatus(200);
    } else {
        $response = $response->withStatus(400);
    }

    return $response;
})->add(new Authentication\BackendAuthentication($app->getContainer()));
