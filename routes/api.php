<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::group([], function () {

    // Authentication routes
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'apiLogin']);
        Route::post('preregister', [RegisterController::class, 'preRegister']);
        Route::post('register', [RegisterController::class, 'register']);

        Route::post('verify-two-factor', [AuthController::class, 'verifyTwoFactor']);
        Route::post('password/reset/request', [AuthController::class, 'requestPasswordReset']);
        Route::post('password/reset/apply', [AuthController::class, 'verifyCodeAndResetPassword']);

        Route::group(['middleware' => 'auth:sanctum'], function () {
            Route::post('logout', [AuthController::class, 'apiLogout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::get('tenant', [AuthController::class, 'tenant']);
        });
    });

    // Protected routes for authenticated users
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::post('me/update-username', [UserController::class, 'updateUsername']);
        Route::post('me/update-email', [UserController::class, 'updateEmail']);
        Route::post('me/update-phone', [UserController::class, 'updatePhone']);
        Route::post('me/update-password', [UserController::class, 'updatePassword']);
        Route::post('me/update-tfa', [UserController::class, 'updateTfa']);

    });

})->middleware(['api']);
