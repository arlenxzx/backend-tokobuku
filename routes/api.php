<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SellerController;
use Illuminate\Support\Facades\Route;

/**
 * @OA\Info(
 *   version="1.0",
 *     
 *   title=" API Documentation",
 *   descrption="API documentation for version 1.0 endpoints.",
 *   
 *   @OA\Contact(
 *     email="test@mail.com"
 *   ),
 * )
 * @OA\Get(
 *   path="/",
 *   summary="Health-check endpoint"
 *   
 *   @OA\Response(response=200, description="OK")
 * )
 */

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// UC-02: Katalog Buku Public
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{id}', [BookController::class, 'show']);

// Protected Routes (Harus membawa Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- SELLER ROUTES ---
    Route::post('/seller/register', [SellerController::class, 'registerSeller']);  // UC-01b
    Route::get('/seller/books', [SellerController::class, 'myBooks']);
    Route::post('/seller/books', [SellerController::class, 'storeBook']);          // UC-06 POST
    Route::put('/seller/books/{id}', [SellerController::class, 'updateBook']);      // UC-06 PUT
    Route::post('/seller/books/{id}', [SellerController::class, 'updateBook']);     // UC-06 PUT via Multipart
    Route::delete('/seller/books/{id}', [SellerController::class, 'destroyBook']);  // UC-06 DELETE
    Route::get('/seller/orders', [SellerController::class, 'incomingOrders']);      // UC-07

    // --- BUYER / ORDER ROUTES ---
    Route::post('/checkout', [OrderController::class, 'checkout']);                 // UC-03
    Route::post('/payments/upload', [OrderController::class, 'uploadPayment']);     // UC-04
    Route::get('/orders/my-orders', [OrderController::class, 'myOrders']);          // UC-05

    // --- ADMIN ROUTES ---
    // UC-08: Kelola User
    Route::get('/admin/users', [AdminController::class, 'indexUsers']);
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUserStatus']);

    // UC-09: Kelola Kategori
    Route::get('/admin/categories', [AdminController::class, 'indexCategories']);
    Route::post('/admin/categories', [AdminController::class, 'storeCategory']);
    Route::put('/admin/categories/{id}', [AdminController::class, 'updateCategory']);
    Route::delete('/admin/categories/{id}', [AdminController::class, 'destroyCategory']);

    // UC-10: Audit Log
    Route::get('/admin/logs', [AdminController::class, 'logs']);
});
