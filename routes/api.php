<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SellerController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Harus membawa Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Fitur Seller
    Route::post('/seller/register', [SellerController::class, 'registerSeller']);
    Route::post('/seller/books', [SellerController::class, 'storeBook']);
    Route::get('/seller/books', [SellerController::class, 'myBooks']);
});