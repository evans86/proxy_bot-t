<?php

use App\Http\Controllers\Api\v1\BotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\CountryController;
use App\Http\Controllers\Api\v1\ProxyController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Проверка соединения
 */
Route::get('pingProxy', [CountryController::class, 'pingProxy']);

/**
 * Получение данных
 */
Route::get('getProxy', [ProxyController::class, 'getProxy']);
Route::get('getCountry', [CountryController::class, 'getCountry']);
Route::get('getCount', [ProxyController::class, 'getCount']);
Route::get('getPrice', [ProxyController::class, 'getPrice']);

/**
 * Покупка прокси
 */
Route::get('buyProxy', [ProxyController::class, 'buyProxy'])->middleware('throttle_user_secret_key');
Route::get('getOrders', [ProxyController::class, 'getOrders']);

/**
 * Работа с активными заказами
 */
Route::get('checkWork', [ProxyController::class, 'checkWork'])->middleware('throttle_user_secret_key');
Route::get('updateType', [ProxyController::class, 'updateType'])->middleware('throttle_user_secret_key');
Route::get('deleteProxy', [ProxyController::class, 'deleteProxy'])->middleware('throttle_user_secret_key');
Route::get('prolongProxy', [ProxyController::class, 'prolongProxy'])->middleware('throttle_user_secret_key');


/**
 * Роуты API (пользователи)
 */
Route::get('setLanguage', [UserController::class, 'setLanguage'])->middleware('throttle_user_secret_key');
Route::get('getUser', [UserController::class, 'getUser']);

/**
 * Роуты API (боты)
 */
Route::get('ping', [BotController::class, 'ping']);
Route::get('create', [BotController::class, 'create']);
Route::get('error', [BotController::class, 'error']);
Route::get('get', [BotController::class, 'get']);
Route::post('update', [BotController::class, 'update']);
Route::get('delete', [BotController::class, 'delete']);
Route::get('getSettings', [BotController::class, 'getSettings']);


