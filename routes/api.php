<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::namespace('Api\V1')->group(function () {
    Route::post("register", "UsersController@register");
    Route::post("login", "UsersController@login");
    Route::post("loginVerifyOtp", "UsersController@loginVerifyOtp");
});

Route::namespace("Api\V1")->middleware("auth:api")->group(function () {
    // WishList APIs //
    Route::post("add-treatments-to-whishlist", "UsersController@addWishList");
    Route::get('get-wishlist', 'UsersController@getWishlist');
    Route::post('remove-from-wishlist', 'UsersController@removeFromWishlist');
    // Favourite APIs //
    Route::post("add-favourite-requests", "UsersController@addFavouriteRequest");
    Route::get("get-my-favourite-requests", "UsersController@getMyFavouriteRequests");
    Route::post("remove-my-fav-by-doctor","UsersController@removeFavourite");
});
