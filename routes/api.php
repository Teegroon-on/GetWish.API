<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostLikeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WishController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\WishlistThemeController;
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

Route::prefix('user')->middleware('auth:sanctum')->group(function() {
    Route::get('/', [UserController::class, 'getMe']);
    Route::get('/wishes/reserved', [WishController::class, 'getReserved']);
    Route::get('/by/username/{username}', [UserController::class, 'getByUsername']);
    Route::get('/{id}', [UserController::class, 'get']);
    Route::get('/{id}/wishlists', [WishlistController::class, 'getByUser']);
    Route::get('/{id}/posts', [PostController::class, 'getByUser']);
    Route::post('/availability', [UserController::class, 'availability']);
    Route::post('/updatePhone', [UserController::class, 'updatePhone']);
    Route::post('/sendCode', [UserController::class, 'sendCode']);
    Route::post('/query', [UserController::class, 'search']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::post('/{id}/updateAvatar', [UserController::class, 'updateAvatar']);
    Route::post('/block', [UserController::class, 'block']);
    Route::post('/unblock', [UserController::class, 'unblock']);
    Route::delete('/{id}', [UserController::class, 'delete']);
    Route::post('/logout', [UserController::class, 'logout']);
});

Route::prefix('friend')->middleware('auth:sanctum')->group(function() {
    Route::get('/{id}/reservations/count', [FriendController::class, 'numberOfReservations']);
    Route::post('/query', [FriendController::class, 'search']);
    Route::delete('/', [FriendController::class, 'delete']);
    Route::post('/request', [FriendController::class, 'createRequest']);
    Route::post('/request/accept', [FriendController::class, 'acceptRequest']);
    Route::post('/request/cancel', [FriendController::class, 'cancelRequest']);
    Route::post('/request/incoming/query', [FriendController::class, 'searchIncomingRequests']);
    Route::post('/request/outgoing/query', [FriendController::class, 'searchOutgoingRequests']);
    Route::post('/request/outgoing/cancel', [FriendController::class, 'cancelOutgoingRequest']);
});

Route::prefix('post')->middleware('auth:sanctum')->group(function() {
    Route::get('/list', [PostController::class, 'list']);
    Route::get('/{id}', [PostController::class, 'get']);
    Route::get('/{id}/comments', [PostCommentController::class, 'list']);
    Route::get('/{id}/likes', [PostLikeController::class, 'list']);
    Route::put('/comment/{id}', [PostCommentController::class, 'update']);
    Route::put('/{id}', [PostController::class, 'update']);
    Route::post('/{id}/comment', [PostCommentController::class, 'create']);
    Route::post('/{id}/like', [PostLikeController::class, 'like']);
    Route::post('/{id}/unlike', [PostLikeController::class, 'unlike']);
    Route::post('/attachment/upload', [PostController::class, 'uploadAttachment']);
    Route::delete('/comment/{id}', [PostCommentController::class, 'delete']);
    Route::delete('/{id}', [PostController::class, 'delete']);
});

Route::prefix('wishlisttheme')->middleware('auth:sanctum')->group(function() {
    Route::get('/', [WishlistThemeController::class, 'getAll']);
    Route::put('/{id}', [WishlistThemeController::class, 'update']);
    Route::post('/', [WishlistThemeController::class, 'create']);
});

Route::prefix('wishlist')->middleware('auth:sanctum')->group(function() {
    Route::post('/', [WishlistController::class, 'create']);
    Route::post('/filter/my', [WishlistController::class, 'filter']);
    Route::get('/my', [WishlistController::class, 'getMy']);
    Route::get('/{id}', [WishlistController::class, 'get']);
    Route::put('/{id}', [WishlistController::class, 'update']);
    Route::delete('/{id}', [WishlistController::class, 'delete']);
});


Route::prefix('wish')->middleware('auth:sanctum')->group(function() {
    Route::post('/', [WishController::class, 'create']);
    Route::post('/{id}/reserve', [WishController::class, 'reservating']);
    Route::get('/{id}', [WishController::class, 'get']);
    Route::put('/{id}', [WishController::class, 'update']);
    Route::delete('/{id}', [WishController::class, 'delete']);
    Route::delete('/{id}/reserve', [WishController::class, 'deleteReservating']);
});

Route::prefix('auth')->group(function () {
    Route::post('/code/send', [AuthController::class, 'send']);
    Route::post('/code/check', [AuthController::class, 'check']);
    Route::post('/token/refresh', [AuthController::class, 'refresh']);
});

Route::get('post/report', [PostController::class, 'report']);
