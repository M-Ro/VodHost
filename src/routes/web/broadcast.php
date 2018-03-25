<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use VodHost\EntityMapper;
use VodHost\Authentication;

$app->get('/upload', function (Request $request, Response $response, array $args) {
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    $username = Authentication\UserSessionHandler::getUsername($request);
    if (!$loggedIn) {
        $response = $response->withRedirect("/");
        return $response;
    }

    $response = $this->view->render($response, 'upload.phtml', ['loggedIn' => $loggedIn, 'username' => $username]);
    return $response;
});

$app->get('/view/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $loggedIn = Authentication\UserSessionHandler::isLoggedIn($request);
    $username = Authentication\UserSessionHandler::getUsername($request);

    $response_vars = [
        'loggedIn' => $loggedIn,
        'username' => $username
    ];

    $bmapper = new EntityMapper\BroadcastMapper($this->em);
    $bentity = $bmapper->getBroadcastById($id);
    if (!$bentity) {
        $this->logger->addInfo("/view/ invalid broadcast id: " . $id . PHP_EOL);
    } else {
        $response_vars['media_path'] = $this->get('content_url_root') . "/video/$id.mp4";
        $response_vars['media_title'] = $bentity->getTitle();
        $response_vars['media_date'] = $bentity->getUploadDate();
        $response_vars['media_desc'] = $bentity->getDescription();
        $response_vars['media_views'] = $bentity->getViews();
        $response_vars['media_uploader'] = '[Deleted]';

        $umapper = new EntityMapper\UserMapper($this->em);
        $uploader = $umapper->getUserById($bentity->getUserId());
        if ($uploader) {
            $response_vars['media_uploader'] = $uploader->getUsername();
        }

        $bmapper->incrementBroadcastViews($id);
    }

    $response = $this->view->render($response, 'view.phtml', $response_vars);

    return $response;
});