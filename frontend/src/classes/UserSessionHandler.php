<?php
namespace App\Frontend;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\SetCookie;

class UserSessionHandler
{

    public static function isLoggedIn(\Slim\Http\Request $request)
    {
        $validLogin = FigRequestCookies::get($request, 'logged_in');

        if ($validLogin == null || $validLogin->getValue() == false) {
            return false;
        } else {
            return true;
        }
    }

    /* Removes the user session as an effective log-out. */
    public static function purge(\Slim\Http\Response $response)
    {
        $response = FigResponseCookies::expire($response, 'uid');
        $response = FigResponseCookies::expire($response, 'username');
        $response = FigResponseCookies::expire($response, 'email');
        $response = FigResponseCookies::expire($response, 'admin');
        $response = FigResponseCookies::expire($response, 'logged_in');

        return $response;
    }

    public static function login(\Slim\Http\Response $response, Entity\UserEntity $user)
    {
        $response = FigResponseCookies::set(
            $response,
            SetCookie::create('uid')->withValue($user->getId())->withPath('/')->rememberForever()
        );
        $response = FigResponseCookies::set(
            $response,
            SetCookie::create('username')->withValue($user->getUsername())->withPath('/')->rememberForever()
        );
        $response = FigResponseCookies::set(
            $response,
            SetCookie::create('email')->withValue($user->getEmail())->withPath('/')->rememberForever()
        );
        $response = FigResponseCookies::set(
            $response,
            SetCookie::create('admin')->withValue($user->getAdmin())->withPath('/')->rememberForever()
        );
        $response = FigResponseCookies::set(
            $response,
            SetCookie::create('logged_in')->withValue(true)->withPath('/')->rememberForever()
        );

        return $response;
    }

    public static function getId(\Slim\Http\Request $request)
    {
        $uid = FigRequestCookies::get($request, 'uid');
        if ($uid) {
            return (int)$uid->getValue();
        } else {
            return -1;
        }
    }

    public static function getUsername(\Slim\Http\Request $request)
    {
        $username = FigRequestCookies::get($request, 'username');
        if ($username) {
            return $username->getValue();
        }
    }

    public static function getEmail(\Slim\Http\Request $request)
    {
        $email = FigRequestCookies::get($request, 'email');
        if ($email) {
            return $email->getValue();
        }
    }

    public static function getAdmin(\Slim\Http\Request $request)
    {
        $admin = FigRequestCookies::get($request, 'admin');
        if ($admin) {
            return $admin->getValue();
        }
    }
}
