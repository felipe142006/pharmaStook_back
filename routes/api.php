<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
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

    // Productos
    Route::prefix('products')->group(function () {
        Route::get('/getProducts', [ProductController::class, 'listProducts']);
        Route::post('/createProducts', [ProductController::class, 'createProducts']);
        Route::get('/alertsProducts', [ProductController::class, 'alerts']);
        Route::get('/getProducts/{id}', [ProductController::class, 'showProducts']);
        Route::put('/updateProducts/{id}', [ProductController::class, 'updateProducts']);
        Route::delete('/deleteProducts/{id}', [ProductController::class, 'deleteProducts']);
    });

    Route::prefix('customers')->group(function () {
        Route::get('/getCustomers', [CustomerController::class, 'listCustomer']);
        Route::get('/getCustomers/{id}', [CustomerController::class, 'showCustomer']);
        Route::post('/createCustomers', [CustomerController::class, 'createCustomer']);
        Route::put('/updateCustomers/{id}', [CustomerController::class, 'updateCustomer']);
        Route::delete('/deleteCustomers/{id}', [CustomerController::class, 'deleteCustomer']);
    });

    // Ventas / Facturas
    Route::prefix('sales')->group(function () {
        Route::get('/getSales', [SaleController::class, 'listSale']);
        Route::get('/getProducts/{id}', [ProductController::class, 'showSale']);
        Route::post('/createSales', [SaleController::class, 'createSale']);
        Route::get('/updateSales/{id}', [SaleController::class, 'showSale']);
        Route::get('/getSales/{id}/print', [SaleController::class, 'print']);
    });
});
