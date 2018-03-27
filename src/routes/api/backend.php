<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


use \VodHost\Middleware\Authentication;

use \VodHost\EntityMapper;
use \VodHost\Entity;

/**
 * Returns a json response containing the metadata of the unprocessed video
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

    $response_data = [
        'id' => $broadcast->getId(),
        'filename' => $broadcast->getFilename()
    ];

    return $response->withJson($response_data, 200);
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
