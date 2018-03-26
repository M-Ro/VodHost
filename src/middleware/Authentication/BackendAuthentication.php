<?php

namespace VodHost\Middleware\Authentication;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class BackendAuthentication
{
	private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * Validates if the API key set in the request headers is correct
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
    	$request_apikey = $request->getHeaderLine('X-API-KEY');
    	if ($request_apikey == $this->container['api_key']) {
    		$response = $next($request, $response);
    	} else {
    		$routepath = $request->getUri()->getPath();
    		$this->container['logger']->warning($routepath .  ' accessed with invalid api key' . PHP_EOL);
    		$response = $response->withStatus(403);;
    	}

    	return $response;
    }
}
