<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\ApprovalController;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\ApiTestController;

// Public Routes (No Authentication Required)
Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login'])->name('login');
Route::post('/register', [\App\Http\Controllers\API\AuthController::class, 'register']);
Route::post('/password/email', [\App\Http\Controllers\API\PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/password/reset', [\App\Http\Controllers\API\PasswordResetController::class, 'reset'])->name('password.reset');
Route::get('/hotels/search', [App\Http\Controllers\API\HotelController::class, 'searchByCity']);
Route::get('/flights/search', [App\Http\Controllers\API\FlightController::class, 'search']);
Route::post('/flights/search', [App\Http\Controllers\API\FlightController::class, 'search']);
Route::post('/flights/resolve_iata', [App\Http\Controllers\API\FlightController::class, 'resolveIataCode']);
Route::get('/hotels/autocomplete', [App\Http\Controllers\API\HotelController::class, 'autocomplete']);

// Protected Routes (Requires Authentication)
Route::middleware('auth:sanctum')->group(function () {
    // General Routes (Accessible to All Authenticated Users)
    Route::post('/logout', [\App\Http\Controllers\API\AuthController::class, 'logout']);
    Route::get('/user', [\App\Http\Controllers\API\AuthController::class, 'user']);

    // Employee Routes (Accessible to employee, travel_admin, and super_admin)
    Route::middleware('role:employee|travel_admin|super_admin')->group(function () {
        // Profile Management
        Route::get('/profile', [UserProfileController::class, 'show']);
        Route::put('/profile', [UserProfileController::class, 'update']);
        Route::put('/profile/password', [UserProfileController::class, 'updatePassword']);
        Route::put('/profile/language', [UserProfileController::class, 'updateLanguage']);
        Route::put('/profile/notifications', [UserProfileController::class, 'updateNotificationPreferences']);

        // Trip, Booking, and Expense Management
        Route::apiResource('trips', \App\Http\Controllers\API\TripController::class);
        Route::apiResource('bookings', \App\Http\Controllers\API\BookingController::class);
        Route::apiResource('expenses', \App\Http\Controllers\API\ExpenseController::class);

        // Booking Flights and Hotels
        Route::post('/flights/book', [App\Http\Controllers\API\FlightController::class, 'book']);
        Route::post('/hotels/book', [App\Http\Controllers\API\HotelController::class, 'book']);
        Route::get('/hotels/offers', [App\Http\Controllers\API\HotelController::class, 'getOffersByHotelIds']);

        // Notifications
        Route::post('/notifications/send', [App\Http\Controllers\API\NotificationController::class, 'sendEmail']);
    });

    // Travel Admin Routes (Accessible to travel_admin and super_admin)
    Route::middleware('role:travel_admin|super_admin')->group(function () {
        // Policy Management
        Route::apiResource('policies', \App\Http\Controllers\API\PolicyController::class);

        // Approval Management
        Route::post('/approvals', [App\Http\Controllers\API\ApprovalController::class, 'store']);
        Route::apiResource('approvals', ApprovalController::class)->only(['update']);

        // Company Settings (Partial Access for Travel Admins)
        Route::put('companies/{company}/settings', [CompanyController::class, 'updateSettings']);
    });

    // Super Admin Routes (Accessible only to super_admin)
    Route::middleware('role:super_admin')->group(function () {
        // User, Company, and Department Management
        Route::apiResource('users', \App\Http\Controllers\API\UserController::class);
        Route::apiResource('companies', \App\Http\Controllers\API\CompanyController::class);
        Route::apiResource('departments', DepartmentController::class);

        // Test Routes
        Route::get('/test/amadeus', [ApiTestController::class, 'testAmadeus']);
        Route::get('/test/travelduqa', [ApiTestController::class, 'testTravelDuqa']);
    });
});