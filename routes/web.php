<?php

use App\Http\Controllers\AdminEnvAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — панель: вход из .env (AdminEnvAuthController), middleware admin.env
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminEnvAuthController::class, 'showLoginForm'])->name('login')->middleware('guest');
    Route::post('login', [AdminEnvAuthController::class, 'login'])
        ->name('login.store')
        ->middleware(['guest', 'throttle:10,1']);
    Route::post('logout', [AdminEnvAuthController::class, 'logout'])->name('logout')->middleware('admin.env');
});

Route::middleware(['admin.env', 'throttle:120,1'])->group(function () {

    Route::group(['namespace' => 'Activate', 'prefix' => 'activate'], function () {
        Route::get('countries', 'CountryController@index')->name('activate.countries.index');
        Route::get('order', 'OrderController@index')->name('activate.order.index');
        Route::get('bot', 'BotController@index')->name('activate.bot.index');
        Route::get('bot/{bot}', 'BotController@show')->name('activate.bot.show');
    });

    Route::group(['namespace' => 'User', 'prefix' => ''], function () {
        Route::get('users', 'UserController@index')->name('users.index');
    });

    Route::get('/', 'HomeController@index')->name('home');

    Route::get('icons', ['as' => 'pages.icons', 'uses' => 'PageController@icons']);
    Route::get('maps', ['as' => 'pages.maps', 'uses' => 'PageController@maps']);
    Route::get('notifications', ['as' => 'pages.notifications', 'uses' => 'PageController@notifications']);
    Route::get('rtl', ['as' => 'pages.rtl', 'uses' => 'PageController@rtl']);
    Route::get('tables', ['as' => 'pages.tables', 'uses' => 'PageController@tables']);
    Route::get('typography', ['as' => 'pages.typography', 'uses' => 'PageController@typography']);
    Route::get('upgrade', ['as' => 'pages.upgrade', 'uses' => 'PageController@upgrade']);
});

Route::get('login', function () {
    return redirect()->route('admin.login');
})->name('login');
