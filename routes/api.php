<?php

use App\Http\Controllers\Admin\CategoryManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



/*
|--------------------------------------------------------------------------
| UTIL ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/categories/{id}/breadcrumb', [CategoryManagementController::class, 'breadcrumb']);



/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Cart
    Route::get('cart', [CartController::class, 'show']);
    Route::post('cart/add', [CartController::class, 'add']);
    Route::post('cart/update', [CartController::class, 'update']);
    Route::post('cart/remove', [CartController::class, 'remove']);

    // Orders
    Route::post('orders', [OrderController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| PUBLIC PRODUCT ROUTES
|--------------------------------------------------------------------------
*/
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);
Route::get('/products/categories/{path?}', [ProductController::class, 'byCategory'])
    ->where('path', '.*');


Route::post('admin/users', [UserManagementController::class, 'store']);
Route::get('admin/users', [UserManagementController::class, 'index']);
Route::post('admin/products', [ProductManagementController::class, 'store']);
Route::put('admin/products/{id}', [ProductManagementController::class, 'update']);
Route::delete('admin/products/{id}', [ProductManagementController::class, 'destroy']);
Route::get('admin/products', [ProductManagementController::class, 'index']);
Route::get('admin/categories', [CategoryManagementController::class, 'index']);
Route::post('admin/categories', [CategoryManagementController::class, 'store']);
Route::delete('admin/categories/{id}', [CategoryManagementController::class, 'destroy']);


/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (Protected by is_admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // ADMIN ROUTES
    Route::middleware('is_admin')->group(function () {

    });
});
