<?php
namespace App\Backend;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\SetCookie;

	class UserSessionHandler {

		public static function isLoggedIn(\Slim\Http\Request $request)
		{
			$validLogin = FigRequestCookies::get($request, 'logged_in');

			if($validLogin == null || $validLogin->getValue() == false)
				return false;
			else
				return true;
		}

		/* Removes the user session as an effective log-out. */
		public static function Purge(\Slim\Http\Response $response)
		{
			$response = FigResponseCookies::expire($response, 'uid');
			$response = FigResponseCookies::expire($response, 'username');
			$response = FigResponseCookies::expire($response, 'email');
			$response = FigResponseCookies::expire($response, 'logged_in');

			return $response;
		}

		public static function Login(\Slim\Http\Response $response, UserEntity $user)
		{
			$response = FigResponseCookies::set($response, SetCookie::create('uid')->withValue($user->getId())->rememberForever());
			$response = FigResponseCookies::set($response, SetCookie::create('username')->withValue($user->getUsername())->rememberForever());
			$response = FigResponseCookies::set($response, SetCookie::create('email')->withValue($user->getEmail())->rememberForever());
			$response = FigResponseCookies::set($response, SetCookie::create('logged_in')->withValue(true)->rememberForever());

			return $response;
		}

		public static function GetId(\Slim\Http\Request $request)
		{
			$uid = FigRequestCookies::get($request, 'uid');
			if($uid)
				return (int)$uid->getValue();
			else
				return -1;
		}

		public static function GetUsername(\Slim\Http\Request $request)
		{
			$username = FigRequestCookies::get($request, 'username');
			if($username)
				return $username->getValue();
		}

		public static function GetEmail(\Slim\Http\Request $request)
		{
			$email = FigRequestCookies::get($request, 'email');
			if($email)
				return $email->getValue();
		}
	}
?>