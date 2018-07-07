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
Route::get('/user/verify/{token}', 'Auth\RegisterController@verify');

Auth::routes();

/* Admin Routes */
Route::middleware('App\Http\Middleware\Admin')->prefix('administration')->group(function () {
    Route::get('/', 'AdminController@dashboard')->name('admin');
    Route::get('/users', 'AdminController@users')->name('users');
    Route::get('/content', 'AdminController@content')->name('content');
    Route::get('/storage', 'AdminController@storage')->name('storage');

});


/* /Broadcast routes */

Route::get('/broadcast/recent', 'BroadcastController@recent');
