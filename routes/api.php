<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    //Cerrar sesion
    Route::post('/logout', [AuthController::class, 'logout']);
    
    //Usuarios
    Route::prefix('user')->middleware('auth:api')->group(function () {
        Route::get('/listUser', [UserController::class, 'listUser']);
        Route::post('/createUser', [UserController::class, 'createUser']);
        Route::put('/editUser/{id?}', [UserController::class, 'editUser']);
        Route::delete('/deleteUser/{id?}', [UserController::class, 'deleteUser']);
    });
});