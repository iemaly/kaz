<?php

use Illuminate\Support\Facades\Route;

// ADMIN
Route::group(['middleware' => 'auth:admin_api', 'prefix' => 'admin'], function () {
    Route::get('/profile', [App\Http\Controllers\AdminController::class, 'show']);
    Route::put('/profile/update', [App\Http\Controllers\AdminController::class, 'update']);
    Route::post('/update_image', [App\Http\Controllers\AdminController::class, 'updateImage']);
    Route::delete('/delete_image', [App\Http\Controllers\AdminController::class, 'imageDelete']);

    // BARBER
    Route::resource('/barbers', 'App\Http\Controllers\BarberController');
    Route::put('/barbers/{barber}/active_deactive', [App\Http\Controllers\BarberController::class, 'activate']);
    
    // BARBER SERVICES
    Route::resource('/barbers/{barber}/services', 'App\Http\Controllers\BarberServiceController');
    Route::resource('/services/{barber_service}/slots', 'App\Http\Controllers\ServiceSlotController');
    
    // BARBER IMAGE
    Route::post('/barbers/{barber}/update_image', [App\Http\Controllers\BarberController::class, 'updateImage']);
    Route::delete('/barbers/{barber}/delete_image', [App\Http\Controllers\BarberController::class, 'imageDelete']);
    
    // USER
    Route::resource('/users', 'App\Http\Controllers\UserController');
    Route::put('/users/{user}/active_deactive', [App\Http\Controllers\UserController::class, 'activate']);
    
    // BOOKINGS
    Route::resource('/bookings', 'App\Http\Controllers\BookingController');
    // Route::get('/bookings', [App\Http\Controllers\BookingController::class, 'index']);
});

// USER
Route::group(['middleware' => 'auth:user_api', 'prefix' => 'user'], function () {
    Route::get('/profile', [App\Http\Controllers\UserController::class, 'show']);
    Route::put('/profile', [App\Http\Controllers\UserController::class, 'update']);
    Route::post('/profile/update_image', [App\Http\Controllers\UserController::class, 'updateImage']);
    Route::delete('/profile/delete_image', [App\Http\Controllers\UserController::class, 'imageDelete']);

    // BOOKING
    Route::post('/{barber_service}/booking', [App\Http\Controllers\UserController::class, 'pay']);
    Route::post('/bookings', [App\Http\Controllers\UserController::class, 'book']);
});

// UNIVERSAL ROUTES
Route::middleware(['throttle:60,1'])->group(function () {
    // AUTH
    Route::post('/login', [App\Http\Controllers\AdminController::class, 'login']);
    Route::post('/forget', [App\Http\Controllers\UserController::class, 'forget']);
    Route::post('/reset_pwd', [App\Http\Controllers\UserController::class, 'resetPwd']);
    
    // USER
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
    
    // BARBER
    Route::get('/barbers', [App\Http\Controllers\BarberController::class, 'index']);
    Route::get('/barbers/{barber}', [App\Http\Controllers\BarberController::class, 'show']);
    
    // BOOKING STORE
    Route::get('/booking', [App\Http\Controllers\UserController::class, 'payStore'])->name('users.services.booking');
});
