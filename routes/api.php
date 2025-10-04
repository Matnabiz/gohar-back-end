<?php

use App\Http\Controllers\Admin\CategoryManagementController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\CommentManagementController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WishlistController;
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

    Route::get('/checkout', [CheckoutController::class, 'index']);
    Route::post('/checkout/address', [CheckoutController::class, 'saveAddress']);
    Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/{productId}', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);


    Route::post('/comments', [CommentController::class, 'store']);
});
Route::get('/comments', [CommentController::class, 'index']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user', [UserController::class, 'update']);
    Route::post('/user/avatar', [UserController::class, 'updateAvatar']);
    Route::get('/user/orders', [UserController::class, 'orders']);
    Route::get('/user/wishlist', [UserController::class, 'wishlist']);
    Route::put('/user/address', [UserController::class, 'updateAddress']);
});



/*
|--------------------------------------------------------------------------
| PUBLIC PRODUCT ROUTES
|--------------------------------------------------------------------------
*/
Route::get('products', [ProductController::class, 'index']);
Route::get('/products/all-products', [ProductController::class, 'allProducts']);
Route::get('products/{product}', [ProductController::class, 'show']);
Route::get('products/{product}/related', [ProductController::class, 'related']);
Route::get('/products/by-slug/{slug}', [ProductController::class, 'showBySlug']);
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
Route::put('admin/categories/{id}', [CategoryManagementController::class, 'update']);
Route::delete('admin/categories/{id}', [CategoryManagementController::class, 'destroy']);
Route::post('/admin/categories/order', [CategoryManagementController::class, 'updateOrder']);
Route::get('admin/comments', [CommentManagementController::class, 'index']);
Route::patch('admin/comments/{id}/state', [CommentManagementController::class, 'updateState']);
Route::delete('admin/comments/{id}', [CommentManagementController::class, 'destroy']);


Route::get('/blogs', [BlogController::class, 'index']);          // List all blogs
Route::get('/blogs/{slug}', [BlogController::class, 'show']);    // Show a single blog by slug
Route::get('/blogs/id/{id}', [BlogController::class, 'showById']);
Route::post('/blogs', [BlogController::class, 'store']);         // Create a new blog
Route::put('/blogs/{id}', [BlogController::class, 'update']);    // Update a blog by ID
Route::delete('/blogs/{id}', [BlogController::class, 'destroy']); // Delete a blog by ID
Route::get('/blogs/id/{id}', [BlogController::class, 'showById']);

// Image upload route
Route::post('/blogs/upload-image', [BlogController::class, 'uploadImage']);
Route::post('/blogs/upload-video', [BlogController::class, 'uploadVideo']);


// User Management Route
Route::get('/users', [UserManagementController::class, 'index']);   // list users
Route::post('/users', [UserManagementController::class, 'store']);  // create user
Route::put('/users/{user}', [UserManagementController::class, 'update']); // update user
Route::delete('/users/{user}', [UserManagementController::class, 'destroy']); // delete user
Route::get('/locations/states', [AddressController::class, 'getStates']);
Route::get('/locations/cities/{stateId}', [AddressController::class, 'getCities']);
Route::get('/gifts', [CheckoutController::class, 'eligibleGifts']);


// Orders
Route::post('orders', [OrderController::class, 'store']);
Route::get('orders', [OrderController::class, 'index']);
Route::get('orders/{id}', [OrderController::class, 'show']);
Route::put('orders/{id}', [OrderController::class, 'update']);
Route::delete('admin/orders', [OrderManagementController::class, 'destroy']);
Route::delete('orders/{id}', [OrderController::class, 'destroy']);
Route::get('/orders/{order}/status', [OrderController::class, 'status']);
Route::get('/unpaid-count', [OrderController::class, 'unpaidCount']);
Route::get('/unpaid', [OrderController::class, 'unpaidList']);


Route::get('/search', [SearchController::class, 'search']);


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


Route::post('/payment/start', [PaymentController::class, 'start']);
Route::post('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

Route::post('/admin/maintenance', [MaintenanceController::class, 'toggle']);

