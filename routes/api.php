<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/create-user', [UserController::class, 'createUser']);
Route::middleware('auth:api')->group(function () {
    Route::post('/change-status', [UserController::class, 'changeUserStatus']);
    Route::post('/get-distance', [UserController::class, 'getDistance']);
    Route::post('/get-user-listing', [UserController::class, 'getUserListing']);
});

