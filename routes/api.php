<?php

use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\API\AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\API\AuthController::class, 'logout']);
    Route::get('/user', [\App\Http\Controllers\API\AuthController::class, 'user']);
    
    // User routes
    Route::apiResource('users', \App\Http\Controllers\API\UserController::class);
    
    // Company routes
    Route::apiResource('companies', \App\Http\Controllers\API\CompanyController::class);
    
    // Policy routes
    Route::apiResource('policies', \App\Http\Controllers\API\PolicyController::class);
    
    // Trip routes
    Route::apiResource('trips', \App\Http\Controllers\API\TripController::class);
    
    // Booking routes
    Route::apiResource('bookings', \App\Http\Controllers\API\BookingController::class);
});