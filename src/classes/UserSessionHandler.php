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

		protected static $username = '';
		protected static $validLogin = '';
	}

?>