<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DepartmentController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\ApprovalController;
use App\Http\Controllers\API\UserProfileController;
use App\Http\Controllers\ApiTestController;

// Public routes
Route::post('/login', [\App\Http\Controllers\API\AuthController::class, 'login'])->name('login');
Route::post('/register', [\App\Http\Controllers\API\AuthController::class, 'register']);

// Password reset routes
Route::post('/password/email', [\App\Http\Controllers\API\PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/password/reset', [\App\Http\Controllers\API\PasswordResetController::class, 'reset'])->name('password.reset');


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\API\AuthController::class, 'logout']);
    Route::get('/user', [\App\Http\Controllers\API\AuthController::class, 'user']);
    
    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', \App\Http\Controllers\API\UserController::class);
        // Route::apiResource('companies', \App\Http\Controllers\API\CompanyController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::put('companies/{company}/settings', [CompanyController::class, 'updateSettings']);
        
    });

    
    Route::apiResource('policies', \App\Http\Controllers\API\PolicyController::class);
    Route::post('/approvals', [App\Http\Controllers\API\ApprovalController::class, 'store']);

    Route::apiResource('approvals', ApprovalController::class)->only(['update']);

    Route::apiResource('companies', \App\Http\Controllers\API\CompanyController::class);
    
    // Trip routes (both admin and employee can access)
    Route::apiResource('trips', \App\Http\Controllers\API\TripController::class);
    
    // Booking routes (both admin and employee can access)
    Route::apiResource('bookings', \App\Http\Controllers\API\BookingController::class);

    // Expense routes (both admin and employee can access)
    Route::apiResource('expenses', \App\Http\Controllers\API\ExpenseController::class);

    // Profile routes
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
    Route::put('/profile/password', [UserProfileController::class, 'updatePassword']);
    Route::put('/profile/language', [UserProfileController::class, 'updateLanguage']);
    Route::put('/profile/notifications', [UserProfileController::class, 'updateNotificationPreferences']);

    
    Route::post('/flights/book', [App\Http\Controllers\API\FlightController::class, 'book']);

    // Route::get('/hotels/search', [App\Http\Controllers\API\HotelController::class, 'search']);

    Route::post('/hotels/book', [App\Http\Controllers\API\HotelController::class, 'book']);
    Route::get('/hotels/offers', [App\Http\Controllers\API\HotelController::class, 'getOffersByHotelIds']);

            Route::post('/notifications/send', [App\Http\Controllers\API\NotificationController::class, 'sendEmail']);
       
            Route::get('/test/amadeus', [ApiTestController::class, 'testAmadeus']);
            Route::get('/test/travelduqa', [ApiTestController::class, 'testTravelDuqa']);
});


Route::get('/hotels/search', [App\Http\Controllers\API\HotelController::class, 'searchByCity']);
Route::get('/flights/search', [App\Http\Controllers\API\FlightController::class, 'search']);
Route::post('/flights/search', [App\Http\Controllers\API\FlightController::class, 'search']);
    