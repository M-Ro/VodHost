<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomeController@index')->name('home');

Route::get('/upload', 'BroadcastController@upload')->name('upload')->middleware('auth');

Route::get('/user/account', 'UserController@account')->name('account')->middleware('auth');

Auth::routes();

Route::get('/home', 'HomeController@index');

/* /Broadcast routes */

Route::get('/broadcast/recent', 'BroadcastController@recent');
