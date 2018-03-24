<?php

namespace VodHost\Authentication;

use \Psr\Http\Message\ServerRequestInterface as Request;

class BackendAuthentication
{
    /**
     * Validates if the API key set in the request headers is correct
     * @return true on validation, false if not
     */
    public static function authenticateAPIKey($request, $apikey)
    {
        $request_apikey = $request->getHeaderLine('X-API-KEY');

        return ($request_apikey == $apikey);
    }
}
