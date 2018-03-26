<?php
namespace VodHost\Middleware\Authentication;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \VodHost\Authentication\UserSessionHandler as UserSessionHandler;

class UserAuthentication
{
    public const RedirectOnFail = 0;  // Redirect the user to /
    public const RedirectOnPass = 1;  // Redirect the user to /
    public const Passive = 2;   // Passively inject user information to the request
    public const Forbidden = 3; // Return a 403 code

    /**
     * specified action from the constants provided above
     * @var int
     */
    private $action;

    public function __construct($action = 0) {
        $this->action = $action;
    }

    /**
     * Performs the specified action based on whether a user is authenticated
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        switch ($this->action) {
            case self::RedirectOnFail: return $this->authWithRedirectOnFail($request, $response, $next); break;
            case self::RedirectOnPass: return $this->authWithRedirectOnPass($request, $response, $next); break;
            case self::Passive: return $this->authPassive($request, $response, $next); break;
            case self::Forbidden: return $this->authWithForbidden($request, $response, $next); break;
        }
    }

    /**
     * Only calls $next if the we authenticate the user session.
     * Redirects client to / on failure.
     */
    private function authWithRedirectOnFail($request, $response, $next)
    {
        if (UserSessionHandler::isLoggedIn($request)) {
            $request = $this->injectUserData($request);
            $response = $next($request, $response);
        } else {
            $response = $response->withRedirect("/");
        }

        return $response;
    }

    /**
     * Redirects if the user is authenticated
     */
    private function authWithRedirectOnPass($request, $response, $next)
    {
        if (!UserSessionHandler::isLoggedIn($request)) {
            $request = $this->injectUserData($request);
            $response = $next($request, $response);
        } else {
            $response = $response->withRedirect("/");
        }

        return $response;
    }

    /**
     * Injects user session data if user successfully auths
     */
    private function authPassive($request, $response, $next)
    {
        if (UserSessionHandler::isLoggedIn($request)) {
            $request = $this->injectUserData($request);
        } else {
            $request = $request->withAttribute('user', ['logged_in' => false]);
        }

        return $next($request, $response);
    }

    /**
     * Only calls $next if the we authenticate the user session.
     * Returns a 403 forbidden on failure.
     */
    private function authWithForbidden($request, $response, $next)
    {
        if (UserSessionHandler::isLoggedIn($request)) {
            $request = $this->injectUserData($request);
            $response = $next($request, $response);
        } else {
            $response = $response->withStatus(403);
        }

        return $response;
    }

    /**
     * Responsible for injecting the user session variables
     * into the $request attributes.
     */
    private function injectUserData($request)
    {
        $user_params = [
            'logged_in' => UserSessionHandler::isLoggedIn($request),
            'id' => UserSessionHandler::getId($request),
            'username' => UserSessionHandler::getUsername($request),
            'email' => UserSessionHandler::getEmail($request),
            'admin' => UserSessionHandler::getAdmin($request)
        ];

        $request = $request->withAttribute('user', $user_params);

        return $request;
    }
}
