<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ADMIN
Route::group(['middleware' => 'auth:admin_api', 'prefix' => 'admin'], function () {
    Route::get('/profile', [App\Http\Controllers\AdminController::class, 'show']);
    Route::put('/profile/update', [App\Http\Controllers\AdminController::class, 'update']);
    Route::post('/update_image', [App\Http\Controllers\AdminController::class, 'updateImage']);
    Route::delete('/delete_image', [App\Http\Controllers\AdminController::class, 'imageDelete']);
    
    // USER
    Route::resource('/users', 'App\Http\Controllers\UserController');
    Route::put('/users/{user}/activate', [App\Http\Controllers\UserController::class, 'activate']);
    // IMAGES
    Route::post('/users/{user}/images', [App\Http\Controllers\UserController::class, 'storeImages']);
    Route::delete('/users/images/{user_image}', [App\Http\Controllers\UserController::class, 'imageDelete']);
    // QUALIFICATION
    Route::post('/users/{user}/qualifications', [App\Http\Controllers\UserController::class, 'storeQualifications']);
    Route::delete('/users/qualifications/{user_qualification}', [App\Http\Controllers\UserController::class, 'deleteQualification']);
});

// UNIVERSAL ROUTES
Route::middleware(['throttle:60,1'])->group(function () {
    // AUTH
    Route::post('/login', [App\Http\Controllers\AdminController::class, 'login']);
    Route::post('/forget', [App\Http\Controllers\AdminController::class, 'forgetPwdProcess']);
    Route::post('/reset_password', [App\Http\Controllers\AdminController::class, 'resetPwdProcess']);

    // SEARCH
    Route::get('/users/{unique_id}', [App\Http\Controllers\GuestController::class, 'show']);
    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
});
